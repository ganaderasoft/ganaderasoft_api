<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Animal\AnimalService;
use App\Services\Sanidad\EstadoAnimalService;
use App\Services\Animal\AnimalEtapaService;
use App\Services\Animal\AnimalImportService;
use App\Http\Requests\Animal\CSVRequest;
use App\Http\Resources\Animal\AnimalResource;
use App\Http\Resources\Sanidad\EstadoAnimalResource;
use App\Http\Resources\Animal\EtapaAnimalResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndex;
use App\Http\Middleware\Legacy\Animal\NormalizeStore;
use App\Http\Middleware\Legacy\Animal\NormalizeShow;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdate;
use App\Http\Middleware\Legacy\Animal\NormalizeCreateEstado;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateEstado;
use App\Http\Middleware\Legacy\Animal\NormalizeCreateEtapa;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateEtapa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AnimalController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta los servicios e introduce los middlewares de compatibilidad legacy.
     */
    public function __construct(
        private AnimalService $animalService,
        private EstadoAnimalService $estadoService,
        private AnimalEtapaService $etapaService,
        private AnimalImportService $animalImportService
    ) {
        $this->middleware(NormalizeIndex::class)->only('index');
        $this->middleware(NormalizeStore::class)->only('store');
        $this->middleware(NormalizeShow::class)->only('show');
        $this->middleware(NormalizeUpdate::class)->only('update');
        $this->middleware(NormalizeCreateEstado::class)->only('createEstadoAnimal');
        $this->middleware(NormalizeUpdateEstado::class)->only('updateEstadoAnimal');
        $this->middleware(NormalizeCreateEtapa::class)->only('createEtapaAnimal');
        $this->middleware(NormalizeUpdateEtapa::class)->only('updateEtapaAnimal');
    }

    /**
     * Devuelve una lista paginada de animales aplicando filtros y permisos.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $animals = $this->animalService->listAnimals(
                $request->only(['finca_id', 'rebano_id', 'sexo', 'nopaginate', 'archivado', 'incluir_archivados']),
                $request->user()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Lista de animales',
                'data' => $this->formatCollection(AnimalResource::class, $animals)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo animal en el sistema con sus datos iniciales.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if ($request->has('sexo')) {
            $sex = strtoupper((string) $request->sexo);
            $request->merge(['sexo' => $sex]);
        }

        $validator = Validator::make($request->all(), [
            'rebano_id' => 'required|exists:rebanos,id',
            'nombre' => 'nullable|string|max:25',
            'codigo_animal' => 'nullable|string|max:20|unique:animals,codigo_animal',
            'sexo' => 'required|in:M,H',
            'fecha_nacimiento' => 'required|date',
            'procedencia' => 'nullable|string|max:50',
            'composicion_raza_id' => 'required|exists:composicion_razas,id',
            'estado_inicial' => 'nullable|array',
            'estado_inicial.estado_salud_id' => 'required_with:estado_inicial|exists:estado_saluds,id',
            'estado_inicial.fecha_ini' => 'required_with:estado_inicial|date',
            'etapa_inicial' => 'nullable|array',
            'etapa_inicial.etapa_id' => 'required_with:etapa_inicial|exists:etapas,id',
            'etapa_inicial.fecha_ini' => 'required_with:etapa_inicial|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            [$animal, $clasificacion] = $this->animalService->storeAnimal($request->all(), $request->user());
            
            $animal->load([
                'rebano.finca.propietario.persona', 
                'composicionRaza',
                'estadoActual.estadoSalud',
                'etapaActual.etapa'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Animal creado exitosamente',
                'data' => $this->formatResource(AnimalResource::class, $animal),
                'clasificacion_etaria' => $clasificacion,
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Muestra el detalle completo de un animal específico.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $animal = $this->animalService->getAnimal((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de animal',
                'data' => $this->formatResource(AnimalResource::class, $animal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de un animal y recalcula su clasificación etaria.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if ($request->has('sexo')) {
            $sex = strtoupper((string) $request->sexo);
            $request->merge(['sexo' => $sex]);
        }

        $validator = Validator::make($request->all(), [
            'rebano_id' => 'sometimes|exists:rebanos,id',
            'nombre' => 'nullable|string|max:25',
            'codigo_animal' => 'nullable|string|max:20|unique:animals,codigo_animal,' . $id . ',id',
            'sexo' => 'sometimes|in:M,H',
            'fecha_nacimiento' => 'sometimes|date',
            'procedencia' => 'nullable|string|max:50',
            'composicion_raza_id' => 'sometimes|exists:composicion_razas,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            [$animal, $clasificacion] = $this->animalService->updateAnimal((int) $id, $request->all(), $request->user());
            
            $animal->load(['rebano.finca.propietario.persona', 'composicionRaza', 'etapaActual.etapa']);

            return response()->json([
                'success' => true,
                'message' => 'Animal actualizado exitosamente',
                'data' => $this->formatResource(AnimalResource::class, $animal),
                'clasificacion_etaria' => $clasificacion,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina definitivamente un animal de la base de datos.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->animalService->deleteAnimal((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Animal eliminado exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Archiva un animal específico.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive(Request $request, $id)
    {
        try {
            $animal = $this->animalService->archiveAnimal((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Animal archivado exitosamente',
                'data' => $this->formatResource(AnimalResource::class, $animal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Desarchiva un animal previamente archivado.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unarchive(Request $request, $id)
    {
        try {
            $animal = $this->animalService->unarchiveAnimal((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Animal desarchivado exitosamente',
                'data' => $this->formatResource(AnimalResource::class, $animal)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo estado de salud en el historial del animal.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEstadoAnimal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_ini' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_ini',
            'estado_salud_id' => 'required|exists:estado_saluds,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $request->all();
            $data['animal_id'] = (int) $id;
            $estadoAnimal = $this->estadoService->createEstado($data, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado animal creado exitosamente',
                'data' => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ], Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo cambio de etapa manual en el historial del animal.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEtapaAnimal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_ini' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_ini',
            'etapa_id' => 'required|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $etapaAnimal = $this->etapaService->createEtapa((int) $id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Etapa animal creada exitosamente',
                'data' => $this->formatResource(EtapaAnimalResource::class, $etapaAnimal)
            ], Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_CONFLICT);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un registro existente del historial de estados de salud del animal.
     *
     * @param Request $request
     * @param mixed $animalId
     * @param mixed $estadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEstadoAnimal(Request $request, $animalId, $estadoId)
    {
        $validator = Validator::make($request->all(), [
            'fecha_ini' => 'sometimes|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_ini',
            'estado_salud_id' => 'sometimes|exists:estado_saluds,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estadoAnimal = $this->estadoService->updateEstado((int) $estadoId, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado animal actualizado exitosamente',
                'data' => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal o registro de estado no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un registro existente del historial de etapas del animal.
     *
     * @param Request $request
     * @param mixed $animalId
     * @param mixed $etapaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEtapaAnimal(Request $request, $animalId, $etapaId)
    {
        $validator = Validator::make($request->all(), [
            'fecha_ini' => 'sometimes|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $etapaAnimal = $this->etapaService->updateEtapa((int) $etapaId, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Etapa animal actualizada exitosamente',
                'data' => $this->formatResource(EtapaAnimalResource::class, $etapaAnimal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal o registro de etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Importación masiva de animales a rebaños a partir de archivo delimitado (.csv / .txt).
     *
     * @param CSVRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cargarAnimalesMasivo(CSVRequest $request)
    {
        try {
            $result = $this->animalImportService->importFromCsv(
                $request->file('archivo'),
                (int) $request->finca_id,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => "Se importaron {$result['total_procesados']} animales exitosamente.",
                'data'    => [
                    'total_procesados' => $result['total_procesados'],
                    'rebanos_creados'  => $result['rebanos_creados'],
                    'finca'            => $result['finca'],
                    'animales'         => $this->formatCollection(AnimalResource::class, $result['animales']),
                ]
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación en los datos del archivo.',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al procesar el archivo: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finca\FincaService;
use App\Http\Resources\Finca\FincaResource;
use App\Http\Middleware\Legacy\Finca\NormalizeIndex;
use App\Http\Middleware\Legacy\Finca\NormalizeStore;
use App\Http\Middleware\Legacy\Finca\NormalizeShow;
use App\Http\Middleware\Legacy\Finca\NormalizeUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Finca\CSVFincaRequest;
use App\Services\Finca\FincaImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Validation\ValidationException;

class FincaController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de Finca y registra los middlewares de compatibilidad legacy.
     */
    public function __construct(
        private FincaService $fincaService,
        private FincaImportService $fincaImportService
    ) {
        $this->middleware(NormalizeIndex::class)->only('index');
        $this->middleware(NormalizeStore::class)->only('store');
        $this->middleware(NormalizeShow::class)->only('show');
        $this->middleware(NormalizeUpdate::class)->only('update');
    }

    /**
     * Display a listing of fincas.
     */
    public function index(Request $request)
    {
        try {
            $fincas = $this->fincaService->listFincas($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de fincas',
                'data' => $this->formatCollection(FincaResource::class, $fincas)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Store a newly created finca.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:25',
            'explotacion_tipo' => 'required|string|max:20',
            'propietario_id' => 'required|exists:propietarios,id',
            'terreno' => 'nullable|array',
            'terreno.superficie' => 'nullable|numeric|min:0',
            'terreno.relieve' => 'nullable|string|max:9',
            'terreno.suelo_textura' => 'nullable|string|max:25',
            'terreno.ph_suelo' => 'nullable|string|max:2',
            'terreno.precipitacion' => 'nullable|numeric|min:0',
            'terreno.velocidad_viento' => 'nullable|numeric|min:0',
            'terreno.temp_anual' => 'nullable|string|max:4',
            'terreno.temp_min' => 'nullable|string|max:4',
            'terreno.temp_max' => 'nullable|string|max:4',
            'terreno.radiacion' => 'nullable|numeric|min:0',
            'terreno.fuente_agua' => 'nullable|string|max:25',
            'terreno.caudal_disponible' => 'nullable|integer|min:0',
            'terreno.riego_metodo' => 'nullable|string|max:18',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $finca = $this->fincaService->storeFinca($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Finca creada exitosamente',
                'data' => $this->formatResource(FincaResource::class, $finca)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Display the specified finca.
     */
    public function show(Request $request, $id)
    {
        try {
            $finca = $this->fincaService->getFinca((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de finca',
                'data' => $this->formatResource(FincaResource::class, $finca)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Update the specified finca.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:25',
            'explotacion_tipo' => 'sometimes|string|max:20',
            'propietario_id' => 'sometimes|exists:propietarios,id',
            'terreno' => 'nullable|array',
            'terreno.superficie' => 'nullable|numeric|min:0',
            'terreno.relieve' => 'nullable|string|max:9',
            'terreno.suelo_textura' => 'nullable|string|max:25',
            'terreno.ph_suelo' => 'nullable|string|max:2',
            'terreno.precipitacion' => 'nullable|numeric|min:0',
            'terreno.velocidad_viento' => 'nullable|numeric|min:0',
            'terreno.temp_anual' => 'nullable|string|max:4',
            'terreno.temp_min' => 'nullable|string|max:4',
            'terreno.temp_max' => 'nullable|string|max:4',
            'terreno.radiacion' => 'nullable|numeric|min:0',
            'terreno.fuente_agua' => 'nullable|string|max:25',
            'terreno.caudal_disponible' => 'nullable|integer|min:0',
            'terreno.riego_metodo' => 'nullable|string|max:18',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $finca = $this->fincaService->updateFinca((int) $id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Finca actualizada exitosamente',
                'data' => $this->formatResource(FincaResource::class, $finca)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Remove the specified finca (hard delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->fincaService->deleteFinca((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Finca eliminada exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Archivar una finca específica.
     */
    public function archive(Request $request, $id)
    {
        try {
            $finca = $this->fincaService->archiveFinca((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Finca archivada exitosamente',
                'data' => $this->formatResource(FincaResource::class, $finca)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Desarchivar una finca específica.
     */
    public function unarchive(Request $request, $id)
    {
        try {
            $finca = $this->fincaService->unarchiveFinca((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Finca desarchivada exitosamente',
                'data' => $this->formatResource(FincaResource::class, $finca)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Importación masiva de fincas a partir de archivo delimitado (.csv / .txt).
     *
     * @param CSVFincaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importar(CSVFincaRequest $request)
    {
        try {
            $propietarioId = $request->input('propietario_id') ? (int) $request->input('propietario_id') : null;
            $result = $this->fincaImportService->importFincas(
                $request->file('archivo'),
                $propietarioId,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => "Se importaron {$result['total_creadas']} fincas exitosamente.",
                'data'    => $result,
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ValidationException $e) {
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

    /**
     * Descarga la plantilla oficial CSV para la importación masiva de fincas.
     *
     * @return \Illuminate\Http\Response
     */
    public function plantilla()
    {
        $csvContent = $this->fincaImportService->generateTemplate();

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_fincas.csv"',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Animal\MedidasCorporalesResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndexMedidasCorporales;
use App\Http\Middleware\Legacy\Animal\NormalizeShowMedidasCorporales;
use App\Http\Middleware\Legacy\Animal\NormalizeStoreMedidasCorporales;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateMedidasCorporales;
use App\Services\Animal\MedidasCorporalesService;
use App\Services\Animal\ZoometriaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MedidasCorporalesController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta los servicios de medidas y zoometría, y registra los middlewares legacy correspondientes.
     *
     * @param MedidasCorporalesService $medidasService
     * @param ZoometriaService $zoometriaService
     */
    public function __construct(
        protected MedidasCorporalesService $medidasService,
        protected ZoometriaService $zoometriaService
    ) {
        $this->middleware(NormalizeIndexMedidasCorporales::class)->only('index');
        $this->middleware(NormalizeShowMedidasCorporales::class)->only('show');
        $this->middleware(NormalizeStoreMedidasCorporales::class)->only('store');
        $this->middleware(NormalizeUpdateMedidasCorporales::class)->only('update');
    }

    /**
     * Devuelve el listado filtrado de medidas corporales.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['animal_id', 'etapa_id', 'nopaginate']);
            $paginator = $this->medidasService->listMedidas($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de medidas corporales obtenida exitosamente',
                'data'    => $this->formatCollection(MedidasCorporalesResource::class, $paginator)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra nuevas medidas corporales de un animal.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'altura_hc'       => 'nullable|numeric|min:0',
            'altura_hg'       => 'nullable|numeric|min:0',
            'perimetro_pt'    => 'nullable|numeric|min:0',
            'perimetro_pca'   => 'nullable|numeric|min:0',
            'longitud_lc'     => 'nullable|numeric|min:0',
            'longitud_lg'     => 'nullable|numeric|min:0',
            'anchura_ag'      => 'nullable|numeric|min:0',
            'animal_etapa_id' => 'required_without:animal_id|nullable|exists:animal_etapa,id',
            'animal_id'       => 'required_without:animal_etapa_id|nullable|exists:animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $medidas = $this->medidasService->createMedidas($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Medidas corporales registradas exitosamente',
                'data'    => $this->formatResource(MedidasCorporalesResource::class, $medidas)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de un registro específico de medidas corporales.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $medidas = $this->medidasService->getMedidasById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Medidas corporales obtenidas exitosamente',
                'data'    => $this->formatResource(MedidasCorporalesResource::class, $medidas)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un registro de medidas corporales.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'altura_hc'     => 'sometimes|numeric|min:0',
            'altura_hg'     => 'sometimes|numeric|min:0',
            'perimetro_pt'  => 'sometimes|numeric|min:0',
            'perimetro_pca' => 'sometimes|numeric|min:0',
            'longitud_lc'   => 'sometimes|numeric|min:0',
            'longitud_lg'   => 'sometimes|numeric|min:0',
            'anchura_ag'    => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $medidas = $this->medidasService->updateMedidas((int)$id, $request->all(), $request->user());
            $medidas->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

            return response()->json([
                'success' => true,
                'message' => 'Medidas corporales actualizadas exitosamente',
                'data'    => $this->formatResource(MedidasCorporalesResource::class, $medidas)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un registro de medidas corporales.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->medidasService->deleteMedidas((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Medidas corporales eliminadas exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve los 7 índices zoométricos calculados on-the-fly para una medición específica.
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function indices(Request $request, $id)
    {
        try {
            $data = $this->zoometriaService->getIndicesByMedidaId((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Índices corporales calculados exitosamente',
                'data'    => $data
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve la serie cronológica de medidas e índices calculados para un animal.
     *
     * @param Request $request
     * @param int|string $animalId
     * @return \Illuminate\Http\JsonResponse
     */
    public function evolucionIndicesPorAnimal(Request $request, $animalId)
    {
        try {
            $data = $this->zoometriaService->getEvolucionIndicesByAnimal((int) $animalId, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Evolución de índices corporales obtenida exitosamente',
                'data'    => $data
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
}

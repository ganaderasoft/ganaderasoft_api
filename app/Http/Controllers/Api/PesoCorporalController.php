<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Animal\PesoCorporalResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndexPesoCorporal;
use App\Http\Middleware\Legacy\Animal\NormalizeShowPesoCorporal;
use App\Http\Middleware\Legacy\Animal\NormalizeStorePesoCorporal;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdatePesoCorporal;
use App\Services\Animal\PesoCorporalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PesoCorporalController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de pesajes y registra los middlewares legacy correspondientes.
     *
     * @param PesoCorporalService $pesoService
     */
    public function __construct(
        protected PesoCorporalService $pesoService
    ) {
        $this->middleware(NormalizeIndexPesoCorporal::class)->only('index');
        $this->middleware(NormalizeShowPesoCorporal::class)->only('show');
        $this->middleware(NormalizeStorePesoCorporal::class)->only('store');
        $this->middleware(NormalizeUpdatePesoCorporal::class)->only('update');
    }

    /**
     * Devuelve el listado filtrado de pesajes corporales.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['animal_id', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
            $paginator = $this->pesoService->listPesos($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de peso corporal obtenida exitosamente',
                'data'    => $this->formatCollection(PesoCorporalResource::class, $paginator)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo pesaje de un animal.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_peso' => 'required|date',
            'peso'       => 'required|numeric|min:0',
            'comentario' => 'nullable|string|max:40',
            'animal_id'  => 'required|exists:animals,id',
            'etapa_id'   => 'nullable|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->pesoService->createPeso($request->all(), $request->user());

            return response()->json([
                'success'              => true,
                'message'              => 'Peso corporal registrado exitosamente',
                'data'                 => $this->formatResource(PesoCorporalResource::class, $result['peso']),
                'clasificacion_etaria' => $result['clasificacion_etaria'],
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Devuelve el detalle de un pesaje corporal específico.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $peso = $this->pesoService->getPesoById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Peso corporal obtenido exitosamente',
                'data'    => $this->formatResource(PesoCorporalResource::class, $peso)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Peso corporal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de un pesaje corporal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_peso' => 'sometimes|date',
            'peso'       => 'sometimes|numeric|min:0',
            'comentario' => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->pesoService->updatePeso((int)$id, $request->all(), $request->user());
            $result['peso']->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

            return response()->json([
                'success'              => true,
                'message'              => 'Peso corporal actualizado exitosamente',
                'data'                 => $this->formatResource(PesoCorporalResource::class, $result['peso']),
                'clasificacion_etaria' => $result['clasificacion_etaria'],
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Peso corporal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un registro de pesaje corporal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->pesoService->deletePeso((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Peso corporal eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Peso corporal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Animal\CambiosAnimalResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndexCambiosAnimal;
use App\Http\Middleware\Legacy\Animal\NormalizeShowCambiosAnimal;
use App\Http\Middleware\Legacy\Animal\NormalizeStoreCambiosAnimal;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateCambiosAnimal;
use App\Services\Animal\CambiosAnimalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CambiosAnimalController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de cambios y registra los middlewares legacy por método.
     *
     * @param CambiosAnimalService $cambiosService
     */
    public function __construct(
        protected CambiosAnimalService $cambiosService
    ) {
        $this->middleware(NormalizeIndexCambiosAnimal::class)->only('index');
        $this->middleware(NormalizeShowCambiosAnimal::class)->only('show');
        $this->middleware(NormalizeStoreCambiosAnimal::class)->only('store');
        $this->middleware(NormalizeUpdateCambiosAnimal::class)->only('update');
    }

    /**
     * Devuelve el listado filtrado de cambios de animal.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['animal_id', 'etapa_id', 'etapa_cambio', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
            $paginator = $this->cambiosService->listCambios($filters, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Lista de cambios de animal obtenida exitosamente',
                'data' => $this->formatCollection(CambiosAnimalResource::class, $paginator)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo cambio de animal.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_cambio'    => 'nullable|date',
            'etapa_cambio'    => 'nullable|string|max:10',
            'peso'            => 'required|numeric|min:0',
            'altura'          => 'required|numeric|min:0',
            'comentario'      => 'nullable|string|max:60',
            'animal_etapa_id' => 'required|exists:animal_etapa,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cambio = $this->cambiosService->createCambio($request->all(), $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Cambios de animal registrados exitosamente',
                'data' => $this->formatResource(CambiosAnimalResource::class, $cambio)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de un registro específico de cambios de animal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $cambio = $this->cambiosService->getCambioById((int)$id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Cambios de animal obtenidos exitosamente',
                'data' => $this->formatResource(CambiosAnimalResource::class, $cambio)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cambios de animal no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de un registro de cambios de animal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Buscamos para validar permisos antes de validar el body
            $this->cambiosService->getCambioById((int)$id, $request->user());
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cambios de animal no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'fecha_cambio' => 'sometimes|date',
            'etapa_cambio' => 'sometimes|string|max:10',
            'peso'         => 'sometimes|numeric|min:0',
            'altura'       => 'sometimes|numeric|min:0',
            'comentario'   => 'nullable|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $updated = $this->cambiosService->updateCambio((int)$id, $request->all(), $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Cambios de animal actualizados exitosamente',
                'data' => $this->formatResource(CambiosAnimalResource::class, $updated)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un registro de cambios de animal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->cambiosService->deleteCambio((int)$id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Cambios de animal eliminados exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cambios de animal no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

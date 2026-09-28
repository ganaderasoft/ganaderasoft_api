<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Personal\TipoTrabajadorResource;
use App\Services\Personal\TipoTrabajadorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TipoTrabajadorController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de tipos de trabajador.
     *
     * @param TipoTrabajadorService $tipoTrabajadorService
     */
    public function __construct(
        protected TipoTrabajadorService $tipoTrabajadorService
    ) {}

    /**
     * Devuelve el listado de tipos de trabajador.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'nopaginate']);
            $paginator = $this->tipoTrabajadorService->listTipos($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de tipos de trabajador obtenida exitosamente',
                'data' => $this->formatCollection(TipoTrabajadorResource::class, $paginator)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo tipo de trabajador en el catálogo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tipoTrabajador = $this->tipoTrabajadorService->createTipo($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de trabajador creado exitosamente',
                'data' => $this->formatResource(TipoTrabajadorResource::class, $tipoTrabajador)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de un tipo de trabajador específico.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $tipoTrabajador = $this->tipoTrabajadorService->getTipoById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de trabajador obtenido exitosamente',
                'data' => $this->formatResource(TipoTrabajadorResource::class, $tipoTrabajador)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de trabajador no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de un tipo de trabajador.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tipoTrabajador = $this->tipoTrabajadorService->updateTipo((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de trabajador actualizado exitosamente',
                'data' => $this->formatResource(TipoTrabajadorResource::class, $tipoTrabajador)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de trabajador no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un tipo de trabajador del catálogo.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $this->tipoTrabajadorService->deleteTipo((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de trabajador eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de trabajador no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}

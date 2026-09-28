<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\EstadoSaludResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexEstadoSalud;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowEstadoSalud;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStoreEstadoSalud;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdateEstadoSalud;
use App\Services\Sanidad\EstadoSaludService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EstadoSaludController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio y registra los middlewares legacy correspondientes.
     *
     * @param EstadoSaludService $estadoService
     */
    public function __construct(
        protected EstadoSaludService $estadoService
    ) {
        $this->middleware(NormalizeIndexEstadoSalud::class)->only('index');
        $this->middleware(NormalizeShowEstadoSalud::class)->only('show');
        $this->middleware(NormalizeStoreEstadoSalud::class)->only('store');
        $this->middleware(NormalizeUpdateEstadoSalud::class)->only('update');
    }

    /**
     * Devuelve el listado de estados de salud.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'nopaginate']);
        $paginator = $this->estadoService->listEstados($filters, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de estados de salud obtenida exitosamente',
            'data'    => $this->formatCollection(EstadoSaludResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra un nuevo estado de salud en el catálogo.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estado = $this->estadoService->createEstado($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de salud creado exitosamente',
                'data'    => $this->formatResource(EstadoSaludResource::class, $estado)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de un estado de salud específico.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $estado = $this->estadoService->getEstadoById((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de salud obtenido exitosamente',
                'data'    => $this->formatResource(EstadoSaludResource::class, $estado)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de salud no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Actualiza los datos de un estado de salud.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estado = $this->estadoService->updateEstado((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de salud actualizado exitosamente',
                'data'    => $this->formatResource(EstadoSaludResource::class, $estado)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de salud no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un estado de salud del catálogo.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->estadoService->deleteEstado((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de salud eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de salud no encontrado'
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
}
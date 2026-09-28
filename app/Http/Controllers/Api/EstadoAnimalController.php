<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\EstadoAnimalResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexEstadoAnimal;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowEstadoAnimal;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStoreEstadoAnimal;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdateEstadoAnimal;
use App\Services\Sanidad\EstadoAnimalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EstadoAnimalController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio y registra los middlewares legacy correspondientes.
     *
     * @param EstadoAnimalService $estadoAnimalService
     */
    public function __construct(
        protected EstadoAnimalService $estadoAnimalService
    ) {
        $this->middleware(NormalizeIndexEstadoAnimal::class)->only('index');
        $this->middleware(NormalizeShowEstadoAnimal::class)->only('show');
        $this->middleware(NormalizeStoreEstadoAnimal::class)->only('store');
        $this->middleware(NormalizeUpdateEstadoAnimal::class)->only('update');
    }

    /**
     * Devuelve el listado filtrado de estados de animales.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['animal_id', 'estado_id', 'active', 'nopaginate']);
            $paginator = $this->estadoAnimalService->listEstados($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de estados de animales obtenida exitosamente',
                'data'    => $this->formatCollection(EstadoAnimalResource::class, $paginator)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo estado de salud para un animal.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_ini'       => 'required|date',
            'fecha_fin'       => 'nullable|date|after_or_equal:fecha_ini',
            'estado_salud_id' => 'required|exists:estado_saluds,id',
            'animal_id'       => 'required|exists:animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estadoAnimal = $this->estadoAnimalService->createEstado($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de animal creado exitosamente',
                'data'    => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de un registro específico de estado de animal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $estadoAnimal = $this->estadoAnimalService->getEstadoById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de estado de animal',
                'data'    => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de un registro de estado de animal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_ini'       => 'sometimes|date',
            'fecha_fin'       => 'nullable|date|after_or_equal:fecha_ini',
            'estado_salud_id' => 'sometimes|exists:estado_saluds,id',
            'animal_id'       => 'sometimes|exists:animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estadoAnimal = $this->estadoAnimalService->updateEstado((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de animal actualizado exitosamente',
                'data'    => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un registro de estado de animal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->estadoAnimalService->deleteEstado((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado de animal eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
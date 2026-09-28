<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Animal\TipoAnimalResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndexTipoAnimal;
use App\Http\Middleware\Legacy\Animal\NormalizeShowTipoAnimal;
use App\Http\Middleware\Legacy\Animal\NormalizeStoreTipoAnimal;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateTipoAnimal;
use App\Services\Animal\TipoAnimalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TipoAnimalController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de especies y registra los middlewares legacy por método.
     *
     * @param TipoAnimalService $tipoAnimalService
     */
    public function __construct(
        protected TipoAnimalService $tipoAnimalService
    ) {
        $this->middleware(NormalizeIndexTipoAnimal::class)->only('index');
        $this->middleware(NormalizeShowTipoAnimal::class)->only('show');
        $this->middleware(NormalizeStoreTipoAnimal::class)->only('store');
        $this->middleware(NormalizeUpdateTipoAnimal::class)->only('update');
    }

    /**
     * Devuelve el listado de especies (tipos de animal).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'nopaginate']);
        $paginator = $this->tipoAnimalService->listTipos($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de tipos de animal obtenida exitosamente',
            'data' => $this->formatCollection(TipoAnimalResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra una nueva especie en el catálogo.
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
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tipoAnimal = $this->tipoAnimalService->createTipo($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de animal creado exitosamente',
                'data' => $this->formatResource(TipoAnimalResource::class, $tipoAnimal)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de una especie específica.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $tipoAnimal = $this->tipoAnimalService->getTipoById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de animal obtenido exitosamente',
                'data' => $this->formatResource(TipoAnimalResource::class, $tipoAnimal)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Actualiza los datos de una especie.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
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
            $tipoAnimal = $this->tipoAnimalService->updateTipo((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de animal actualizado exitosamente',
                'data' => $this->formatResource(TipoAnimalResource::class, $tipoAnimal)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina una especie del catálogo.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->tipoAnimalService->deleteTipo((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de animal eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
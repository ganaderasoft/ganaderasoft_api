<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\FoliculoResource;
use App\Services\Sanidad\FoliculoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FoliculoController extends Controller
{
    /**
     * Inyecta el servicio de folículos.
     */
    public function __construct(
        protected FoliculoService $foliculoService
    ) {}

    /**
     * Devuelve la lista de folículos.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'nopaginate']);
        $paginator = $this->foliculoService->listFoliculos($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de folículos obtenida exitosamente',
            'data'    => $this->formatCollection(FoliculoResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra un nuevo folículo.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:40',
            'siglas' => 'nullable|string|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $foliculo = $this->foliculoService->createFoliculo($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Folículo creado exitosamente',
                'data'    => $this->formatResource(FoliculoResource::class, $foliculo)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Obtiene el detalle de un folículo por su ID.
     */
    public function show(Request $request, $id)
    {
        try {
            $foliculo = $this->foliculoService->getFoliculoById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Folículo obtenido exitosamente',
                'data'    => $this->formatResource(FoliculoResource::class, $foliculo)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Folículo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un folículo existente.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:40',
            'siglas' => 'nullable|string|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $foliculo = $this->foliculoService->updateFoliculo((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Folículo actualizado exitosamente',
                'data'    => $this->formatResource(FoliculoResource::class, $foliculo)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Folículo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un folículo.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->foliculoService->deleteFoliculo((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Folículo eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Folículo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

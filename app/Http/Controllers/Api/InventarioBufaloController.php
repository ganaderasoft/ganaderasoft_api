<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventario\InventarioBufaloResource;
use App\Services\Inventario\InventarioBufaloService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InventarioBufaloController extends Controller
{
    protected InventarioBufaloService $service;

    public function __construct(InventarioBufaloService $service)
    {
        $this->service = $service;

        // Apply legacy normalization middlewares
        $this->middleware(\App\Http\Middleware\Legacy\InventarioBufalo\NormalizeIndex::class)->only('index');
        $this->middleware(\App\Http\Middleware\Legacy\InventarioBufalo\NormalizeShow::class)->only('show');
        $this->middleware(\App\Http\Middleware\Legacy\InventarioBufalo\NormalizeStore::class)->only('store');
        $this->middleware(\App\Http\Middleware\Legacy\InventarioBufalo\NormalizeUpdate::class)->only('update');
    }

    /**
     * Display a listing of inventario bufalo.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['finca_id', 'id_finca', 'nopaginate']);
            $inventarios = $this->service->listInventarioBufalo($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de inventarios de búfalo',
                'data' => $this->formatCollection(InventarioBufaloResource::class, $inventarios)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Store a newly created inventario bufalo.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'required|exists:fincas,id',
            'num_becerro' => 'nullable|integer|min:0',
            'num_anojo' => 'nullable|integer|min:0',
            'num_bubilla' => 'nullable|integer|min:0',
            'num_bufalo' => 'nullable|integer|min:0',
            'fecha_inventario' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $inventario = $this->service->storeInventarioBufalo($validator->validated(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Inventario de búfalo creado exitosamente',
                'data' => $this->formatResource(InventarioBufaloResource::class, $inventario)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Display the specified inventario bufalo.
     */
    public function show(Request $request, $id)
    {
        try {
            $inventario = $this->service->getInventarioBufalo($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de inventario de búfalo',
                'data' => $this->formatResource(InventarioBufaloResource::class, $inventario)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario de búfalo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Update the specified inventario bufalo.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'nullable|exists:fincas,id',
            'num_becerro' => 'nullable|integer|min:0',
            'num_anojo' => 'nullable|integer|min:0',
            'num_bubilla' => 'nullable|integer|min:0',
            'num_bufalo' => 'nullable|integer|min:0',
            'fecha_inventario' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $inventario = $this->service->updateInventarioBufalo($id, $validator->validated(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Inventario de búfalo actualizado exitosamente',
                'data' => $this->formatResource(InventarioBufaloResource::class, $inventario)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario de búfalo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Remove the specified inventario bufalo.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->service->deleteInventarioBufalo($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Inventario de búfalo eliminado exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario de búfalo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
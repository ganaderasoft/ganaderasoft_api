<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventario\InventarioGeneralResource;
use App\Services\Inventario\InventarioGeneralService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class InventarioGeneralController extends Controller
{
    protected InventarioGeneralService $inventarioGeneralService;

    public function __construct(InventarioGeneralService $inventarioGeneralService)
    {
        $this->inventarioGeneralService = $inventarioGeneralService;

        $this->middleware(\App\Http\Middleware\Legacy\InventarioGeneral\NormalizeIndex::class)->only('index');
        $this->middleware(\App\Http\Middleware\Legacy\InventarioGeneral\NormalizeShow::class)->only('show');
        $this->middleware(\App\Http\Middleware\Legacy\InventarioGeneral\NormalizeStore::class)->only('store');
        $this->middleware(\App\Http\Middleware\Legacy\InventarioGeneral\NormalizeUpdate::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['finca_id', 'id_finca', 'fecha_inicio', 'fecha_fin', 'nopaginate']);

        try {
            $records = $this->inventarioGeneralService->listInventarioGeneral($filters, $request->user());
            return response()->json([
                'success' => true,
                'message' => 'Lista de inventarios generales obtenida exitosamente',
                'data' => $this->formatCollection(InventarioGeneralResource::class, $records)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id'         => 'required|exists:fincas,id',
            'num_personal'     => 'nullable|integer|min:0',
            'fecha_inventario' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $inv = $this->inventarioGeneralService->storeInventarioGeneral($validator->validated(), $request->user());
            return response()->json([
                'success' => true,
                'message' => 'Inventario general creado',
                'data' => $this->formatResource(InventarioGeneralResource::class, $inv)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $inv = $this->inventarioGeneralService->getInventarioGeneral($id, $request->user());
            return response()->json([
                'success' => true,
                'data' => $this->formatResource(InventarioGeneralResource::class, $inv)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'finca_id'         => 'nullable|exists:fincas,id',
            'num_personal'     => 'nullable|integer|min:0',
            'fecha_inventario' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $inv = $this->inventarioGeneralService->updateInventarioGeneral($id, $validator->validated(), $request->user());
            return response()->json([
                'success' => true,
                'message' => 'Inventario general actualizado',
                'data' => $this->formatResource(InventarioGeneralResource::class, $inv)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $this->inventarioGeneralService->deleteInventarioGeneral($id, $request->user());
            return response()->json([
                'success' => true,
                'message' => 'Inventario general eliminado'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

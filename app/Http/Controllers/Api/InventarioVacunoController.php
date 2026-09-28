<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventario\InventarioVacunoResource;
use App\Services\Inventario\InventarioVacunoService;
use App\Http\Middleware\Legacy\InventarioVacuno\NormalizeIndex;
use App\Http\Middleware\Legacy\InventarioVacuno\NormalizeShow;
use App\Http\Middleware\Legacy\InventarioVacuno\NormalizeStore;
use App\Http\Middleware\Legacy\InventarioVacuno\NormalizeUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InventarioVacunoController extends Controller
{
    protected $service;

    public function __construct(InventarioVacunoService $service)
    {
        $this->service = $service;

        // Middlewares para retrocompatibilidad
        $this->middleware(NormalizeIndex::class)->only('index');
        $this->middleware(NormalizeStore::class)->only('store');
        $this->middleware(NormalizeShow::class)->only('show');
        $this->middleware(NormalizeUpdate::class)->only('update');
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->all();
            $records = $this->service->listInventarioVacuno($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Inventarios vacunos',
                'data' => $this->formatCollection(InventarioVacunoResource::class, $records)
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
            'num_becerra'      => 'nullable|integer|min:0',
            'num_mauta'        => 'nullable|integer|min:0',
            'num_novilla'      => 'nullable|integer|min:0',
            'num_vaca'         => 'nullable|integer|min:0',
            'num_becerro'      => 'nullable|integer|min:0',
            'num_maute'        => 'nullable|integer|min:0',
            'num_torete'       => 'nullable|integer|min:0',
            'num_toro'         => 'nullable|integer|min:0',
            'fecha_inventario' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $inv = $this->service->storeInventarioVacuno($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Inventario vacuno creado',
                'data' => $this->formatResource(InventarioVacunoResource::class, $inv)
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
            $inv = $this->service->getInventarioVacuno($id, $request->user());
            return response()->json([
                'success' => true,
                'data' => $this->formatResource(InventarioVacunoResource::class, $inv)
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
            'num_becerra'      => 'nullable|integer|min:0',
            'num_mauta'        => 'nullable|integer|min:0',
            'num_novilla'      => 'nullable|integer|min:0',
            'num_vaca'         => 'nullable|integer|min:0',
            'num_becerro'      => 'nullable|integer|min:0',
            'num_maute'        => 'nullable|integer|min:0',
            'num_torete'       => 'nullable|integer|min:0',
            'num_toro'         => 'nullable|integer|min:0',
            'fecha_inventario' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $inv = $this->service->updateInventarioVacuno($id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Inventario actualizado',
                'data' => $this->formatResource(InventarioVacunoResource::class, $inv)
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
            $this->service->deleteInventarioVacuno($id, $request->user());
            return response()->json([
                'success' => true, 
                'message' => 'Inventario eliminado'
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\VacunaResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexVacuna;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowVacuna;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStoreVacuna;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdateVacuna;
use App\Services\Sanidad\VacunaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VacunaController extends Controller
{
    public function __construct(
        protected VacunaService $vacunaService
    ) {
        $this->middleware(NormalizeIndexVacuna::class)->only('index');
        $this->middleware(NormalizeShowVacuna::class)->only('show');
        $this->middleware(NormalizeStoreVacuna::class)->only('store');
        $this->middleware(NormalizeUpdateVacuna::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['nombre', 'activa', 'nopaginate']);
        
        $records = $this->vacunaService->getPaginatedVacunas($filters, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Catálogo de vacunas',
            'data'    => $this->formatCollection(VacunaResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'      => 'required|string|max:80',
            'descripcion' => 'nullable|string',
            'activa'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vacuna = $this->vacunaService->createVacuna($request->all(), request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Vacuna creada exitosamente',
            'data'    => $this->formatResource(VacunaResource::class, $vacuna),
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        try {
            $vacuna = $this->vacunaService->getVacunaById((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Vacuna obtenida exitosamente',
                'data'    => $this->formatResource(VacunaResource::class, $vacuna)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Vacuna no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre'      => 'sometimes|string|max:80',
            'descripcion' => 'nullable|string',
            'activa'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $vacuna = $this->vacunaService->updateVacuna((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Vacuna actualizada exitosamente',
                'data'    => $this->formatResource(VacunaResource::class, $vacuna),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Vacuna no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy($id)
    {
        try {
            $this->vacunaService->deleteVacuna((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Vacuna eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Vacuna no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

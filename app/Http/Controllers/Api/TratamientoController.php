<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\TratamientoResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexTratamiento;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowTratamiento;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStoreTratamiento;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdateTratamiento;
use App\Services\Sanidad\TratamientoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class TratamientoController extends Controller
{
    public function __construct(
        protected TratamientoService $tratamientoService
    ) {
        $this->middleware(NormalizeIndexTratamiento::class)->only('index');
        $this->middleware(NormalizeShowTratamiento::class)->only('show');
        $this->middleware(NormalizeStoreTratamiento::class)->only('store');
        $this->middleware(NormalizeUpdateTratamiento::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['diagnostico_id', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
        
        $records = $this->tratamientoService->getPaginatedTratamientos($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tratamientos obtenidos exitosamente',
            'data'    => $this->formatCollection(TratamientoResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan'           => 'nullable|string|max:255',
            'fecha_ini'      => 'required|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_ini',
            'diagnostico_id' => 'required|exists:diagnosticos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tratamiento = $this->tratamientoService->createTratamiento($request->only([
                'plan', 'fecha_ini', 'fecha_fin', 'diagnostico_id'
            ]), $request->user());

            return response()->json([
                'success' => true, 
                'message' => 'Tratamiento registrado exitosamente', 
                'data'    => $this->formatResource(TratamientoResource::class, $tratamiento)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function show($id)
    {
        try {
            $tratamiento = $this->tratamientoService->getTratamientoById((int)$id, request()->user());
            
            return response()->json([
                'success' => true, 
                'message' => 'Tratamiento obtenido exitosamente',
                'data'    => $this->formatResource(TratamientoResource::class, $tratamiento)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Tratamiento no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'plan'           => 'nullable|string|max:255',
            'fecha_ini'      => 'sometimes|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_ini',
            'diagnostico_id' => 'nullable|exists:diagnosticos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tratamiento = $this->tratamientoService->updateTratamiento((int)$id, $request->only([
                'plan', 'fecha_ini', 'fecha_fin', 'diagnostico_id'
            ]), $request->user());

            return response()->json([
                'success' => true, 
                'message' => 'Tratamiento actualizado exitosamente', 
                'data'    => $this->formatResource(TratamientoResource::class, $tratamiento)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Tratamiento no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function destroy($id)
    {
        try {
            $this->tratamientoService->deleteTratamiento((int)$id, request()->user());
            
            return response()->json([
                'success' => true, 
                'message' => 'Tratamiento eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Tratamiento no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
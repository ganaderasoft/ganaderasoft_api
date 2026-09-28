<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\DiagnosticoResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexDiagnostico;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowDiagnostico;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStoreDiagnostico;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdateDiagnostico;
use App\Services\Sanidad\DiagnosticoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class DiagnosticoController extends Controller
{
    public function __construct(
        protected DiagnosticoService $diagnosticoService
    ) {
        $this->middleware(NormalizeIndexDiagnostico::class)->only('index');
        $this->middleware(NormalizeShowDiagnostico::class)->only('show');
        $this->middleware(NormalizeStoreDiagnostico::class)->only('store');
        $this->middleware(NormalizeUpdateDiagnostico::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
        
        $records = $this->diagnosticoService->getPaginatedDiagnosticos($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Diagnósticos obtenidos exitosamente',
            'data'    => $this->formatCollection(DiagnosticoResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion'     => 'nullable|string',
            'tipo'            => 'nullable|string|max:30',
            'fecha'           => 'nullable|date',
            'animal_etapa_id' => 'required_without_all:animal_id,etapa_id|exists:animal_etapa,id',
            'animal_id'       => 'required_without:animal_etapa_id|exists:animals,id',
            'etapa_id'        => 'required_without:animal_etapa_id|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $diagnostico = $this->diagnosticoService->createDiagnostico($request->only([
                'descripcion', 'tipo', 'fecha', 'animal_etapa_id', 'animal_id', 'etapa_id'
            ]), $request->user());

            return response()->json([
                'success' => true, 
                'message' => 'Diagnóstico registrado exitosamente', 
                'data'    => $this->formatResource(DiagnosticoResource::class, $diagnostico)
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
            $diagnostico = $this->diagnosticoService->getDiagnosticoById((int)$id, request()->user());
            
            return response()->json([
                'success' => true, 
                'message' => 'Diagnóstico obtenido exitosamente',
                'data'    => $this->formatResource(DiagnosticoResource::class, $diagnostico)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Diagnóstico no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion'     => 'nullable|string',
            'tipo'            => 'nullable|string|max:30',
            'fecha'           => 'nullable|date',
            'animal_etapa_id' => 'nullable|exists:animal_etapa,id',
            'animal_id'       => 'nullable|exists:animals,id',
            'etapa_id'        => 'nullable|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $diagnostico = $this->diagnosticoService->updateDiagnostico((int)$id, $request->only([
                'descripcion', 'tipo', 'fecha', 'animal_etapa_id', 'animal_id', 'etapa_id'
            ]), $request->user());

            return response()->json([
                'success' => true, 
                'message' => 'Diagnóstico actualizado exitosamente', 
                'data'    => $this->formatResource(DiagnosticoResource::class, $diagnostico)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Diagnóstico no encontrado'
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
            $this->diagnosticoService->deleteDiagnostico((int)$id, request()->user());
            
            return response()->json([
                'success' => true, 
                'message' => 'Diagnóstico eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Diagnóstico no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

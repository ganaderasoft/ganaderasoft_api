<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\PalpacionResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexPalpacion;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowPalpacion;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStorePalpacion;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdatePalpacion;
use App\Services\Sanidad\PalpacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class PalpacionController extends Controller
{
    public function __construct(
        protected PalpacionService $palpacionService
    ) {
        $this->middleware(NormalizeIndexPalpacion::class)->only('index');
        $this->middleware(NormalizeShowPalpacion::class)->only('show');
        $this->middleware(NormalizeStorePalpacion::class)->only('store');
        $this->middleware(NormalizeUpdatePalpacion::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'nopaginate', 'finca_id', 'rebano_id']);
        
        $records = $this->palpacionService->getPaginatedPalpaciones($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Palpaciones obtenidas exitosamente',
            'data'    => $this->formatCollection(PalpacionResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personal_finca_id' => 'nullable|exists:personal_fincas,id',
            'tipo'              => 'nullable|string|max:16',
            'fecha'             => 'nullable|date',
            'animal_etapa_id'   => 'required_without_all:animal_id,etapa_id|exists:animal_etapa,id',
            'animal_id'         => 'required_without:animal_etapa_id|exists:animals,id',
            'etapa_id'          => 'required_without:animal_etapa_id|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $palpacion = $this->palpacionService->createPalpacion($request->only([
                'personal_finca_id', 'tipo', 'fecha', 'animal_etapa_id', 'animal_id', 'etapa_id'
            ]), $request->user());

            return response()->json([
                'success' => true, 
                'message' => 'Palpación registrada exitosamente', 
                'data'    => $this->formatResource(PalpacionResource::class, $palpacion)
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
            $palpacion = $this->palpacionService->getPalpacionById((int)$id, request()->user());
            
            return response()->json([
                'success' => true, 
                'message' => 'Palpación obtenida exitosamente',
                'data'    => $this->formatResource(PalpacionResource::class, $palpacion)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Palpación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'personal_finca_id' => 'nullable|exists:personal_fincas,id',
            'tipo'              => 'nullable|string|max:16',
            'fecha'             => 'nullable|date',
            'animal_etapa_id'   => 'nullable|exists:animal_etapa,id',
            'animal_id'         => 'nullable|exists:animals,id',
            'etapa_id'          => 'nullable|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $palpacion = $this->palpacionService->updatePalpacion((int)$id, $request->only([
                'personal_finca_id', 'tipo', 'fecha', 'animal_etapa_id', 'animal_id', 'etapa_id'
            ]), $request->user());

            return response()->json([
                'success' => true, 
                'message' => 'Palpación actualizada exitosamente', 
                'data'    => $this->formatResource(PalpacionResource::class, $palpacion)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Palpación no encontrada'
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
            $this->palpacionService->deletePalpacion((int)$id, request()->user());
            
            return response()->json([
                'success' => true, 
                'message' => 'Palpación eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Palpación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

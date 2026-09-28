<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Produccion\LactanciaResource;
use App\Http\Middleware\Legacy\Produccion\NormalizeIndexLactancia;
use App\Http\Middleware\Legacy\Produccion\NormalizeShowLactancia;
use App\Http\Middleware\Legacy\Produccion\NormalizeStoreLactancia;
use App\Http\Middleware\Legacy\Produccion\NormalizeUpdateLactancia;
use App\Services\Produccion\LactanciaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class LactanciaController extends Controller
{
    public function __construct(
        protected LactanciaService $lactanciaService
    ) {
        $this->middleware(NormalizeIndexLactancia::class)->only('index');
        $this->middleware(NormalizeShowLactancia::class)->only('show');
        $this->middleware(NormalizeStoreLactancia::class)->only('store');
        $this->middleware(NormalizeUpdateLactancia::class)->only('update');
    }

    /**
     * Display a listing of lactancia.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'activa', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
        
        $records = $this->lactanciaService->getPaginatedLactancias($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de lactancias obtenida exitosamente',
            'data'    => $this->formatCollection(LactanciaResource::class, $records),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created lactancia.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'animal_etapa_id' => 'required_without_all:animal_id,etapa_id|exists:animal_etapa,id',
            'animal_id'       => 'required_without:animal_etapa_id|exists:animals,id',
            'etapa_id'        => 'required_without:animal_etapa_id|exists:etapas,id',
            'fecha_inicio'    => 'required|date',
            'fecha_fin'       => 'nullable|date|after:fecha_inicio',
            'secado'          => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $lactancia = $this->lactanciaService->createLactancia($request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Lactancia registrada exitosamente',
                'data'    => $this->formatResource(LactanciaResource::class, $lactancia)
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified lactancia.
     */
    public function show($id)
    {
        try {
            $lactancia = $this->lactanciaService->getLactanciaById((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Lactancia obtenida exitosamente',
                'data'    => $this->formatResource(LactanciaResource::class, $lactancia)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified lactancia.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'animal_etapa_id' => 'sometimes|exists:animal_etapa,id',
            'animal_id'       => 'sometimes|exists:animals,id',
            'etapa_id'        => 'sometimes|exists:etapas,id',
            'fecha_inicio'    => 'sometimes|date',
            'fecha_fin'       => 'nullable|date|after:fecha_inicio',
            'secado'          => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $lactancia = $this->lactanciaService->updateLactancia((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Lactancia actualizada exitosamente',
                'data'    => $this->formatResource(LactanciaResource::class, $lactancia)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Remove the specified lactancia.
     */
    public function destroy($id)
    {
        try {
            $this->lactanciaService->deleteLactancia((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Lactancia eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

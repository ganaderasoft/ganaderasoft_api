<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\CasaComercialResource;
use App\Services\Sanidad\CasaComercialService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CasaComercialController extends Controller
{
    public function __construct(
        protected CasaComercialService $casaComercialService
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['laboratorio', 'activa', 'nopaginate']);
        
        $records = $this->casaComercialService->getPaginatedCasasComerciales($filters, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Casas comerciales obtenidas exitosamente',
            'data'    => $this->formatCollection(CasaComercialResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laboratorio'     => 'required|string|max:60',
            'marca_comercial' => 'required|string|max:60',
            'activa'          => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $casa = $this->casaComercialService->createCasaComercial($request->all(), request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Casa comercial creada exitosamente',
            'data'    => $this->formatResource(CasaComercialResource::class, $casa),
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        try {
            $casa = $this->casaComercialService->getCasaComercialById((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Casa comercial obtenida exitosamente',
                'data'    => $this->formatResource(CasaComercialResource::class, $casa)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Casa comercial no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'laboratorio'     => 'sometimes|string|max:60',
            'marca_comercial' => 'sometimes|string|max:60',
            'activa'          => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $casa = $this->casaComercialService->updateCasaComercial((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Casa comercial actualizada exitosamente',
                'data'    => $this->formatResource(CasaComercialResource::class, $casa),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Casa comercial no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy($id)
    {
        try {
            $this->casaComercialService->deleteCasaComercial((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Casa comercial eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Casa comercial no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

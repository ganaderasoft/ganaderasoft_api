<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reproduccion\RegistroCeloResource;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeIndexRegistroCelo;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeShowRegistroCelo;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeStoreRegistroCelo;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeUpdateRegistroCelo;
use App\Services\Reproduccion\RegistroCeloService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class RegistroCeloController extends Controller
{
    public function __construct(
        protected RegistroCeloService $celoService
    ) {
        $this->middleware(NormalizeIndexRegistroCelo::class)->only('index');
        $this->middleware(NormalizeShowRegistroCelo::class)->only('show');
        $this->middleware(NormalizeStoreRegistroCelo::class)->only('store');
        $this->middleware(NormalizeUpdateRegistroCelo::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
        
        $records = $this->celoService->getPaginatedCelos($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Registros de celo obtenidos exitosamente',
            'data'    => $this->formatCollection(RegistroCeloResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha'           => 'required|date',
            'observacion'     => 'nullable|string|max:100',
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
            $celo = $this->celoService->createCelo($request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de celo creado exitosamente',
                'data'    => $this->formatResource(RegistroCeloResource::class, $celo),
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function show($id)
    {
        try {
            $celo = $this->celoService->getCeloById((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Registro de celo obtenido exitosamente',
                'data'    => $this->formatResource(RegistroCeloResource::class, $celo)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Registro de celo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha'           => 'sometimes|date',
            'observacion'     => 'nullable|string|max:100',
            'animal_etapa_id' => 'sometimes|exists:animal_etapa,id',
            'animal_id'       => 'sometimes|exists:animals,id',
            'etapa_id'        => 'sometimes|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $celo = $this->celoService->updateCelo((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de celo actualizado exitosamente',
                'data'    => $this->formatResource(RegistroCeloResource::class, $celo),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Registro de celo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        try {
            $this->celoService->deleteCelo((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Registro de celo eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Registro de celo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

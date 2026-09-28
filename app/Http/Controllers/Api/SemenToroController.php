<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reproduccion\SemenToroResource;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeIndexSemenToro;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeShowSemenToro;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeStoreSemenToro;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeUpdateSemenToro;
use App\Services\Reproduccion\SemenToroService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class SemenToroController extends Controller
{
    public function __construct(
        protected SemenToroService $semenService
    ) {
        $this->middleware(NormalizeIndexSemenToro::class)->only('index');
        $this->middleware(NormalizeShowSemenToro::class)->only('show');
        $this->middleware(NormalizeStoreSemenToro::class)->only('store');
        $this->middleware(NormalizeUpdateSemenToro::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['toro_id', 'animal_id', 'activo', 'nopaginate', 'finca_id', 'rebano_id', 'fecha_inicio', 'fecha_fin']);
        
        $records = $this->semenService->getPaginatedSemen($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Registros de semen de toro obtenidos exitosamente',
            'data'    => $this->formatCollection(SemenToroResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'animal_id'         => 'required|exists:animals,id',
            'estado'            => 'nullable|boolean',
            'fecha'             => 'nullable|date',
            'cantidad_pajuelas' => 'nullable|integer|min:0|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $semen = $this->semenService->createSemen($request->all(), request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Semen registrado exitosamente',
                'data'    => $this->formatResource(SemenToroResource::class, $semen),
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
            $semen = $this->semenService->getSemenById((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Registro obtenido exitosamente',
                'data'    => $this->formatResource(SemenToroResource::class, $semen)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Registro no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'animal_id'         => 'sometimes|exists:animals,id',
            'estado'            => 'nullable|boolean',
            'fecha'             => 'nullable|date',
            'cantidad_pajuelas' => 'nullable|integer|min:0|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $semen = $this->semenService->updateSemen((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Semen actualizado exitosamente',
                'data'    => $this->formatResource(SemenToroResource::class, $semen),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Registro no encontrado'
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
            $this->semenService->deleteSemen((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Registro eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Registro no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

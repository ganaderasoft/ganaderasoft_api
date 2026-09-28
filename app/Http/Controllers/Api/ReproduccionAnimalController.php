<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reproduccion\ReproduccionAnimalResource;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeIndexReproduccionAnimal;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeShowReproduccionAnimal;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeStoreReproduccionAnimal;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeUpdateReproduccionAnimal;
use App\Services\Reproduccion\ReproduccionAnimalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ReproduccionAnimalController extends Controller
{
    public function __construct(
        protected ReproduccionAnimalService $reproduccionService
    ) {
        $this->middleware(NormalizeIndexReproduccionAnimal::class)->only('index');
        $this->middleware(NormalizeShowReproduccionAnimal::class)->only('show');
        $this->middleware(NormalizeStoreReproduccionAnimal::class)->only('store');
        $this->middleware(NormalizeUpdateReproduccionAnimal::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'nopaginate', 'finca_id', 'rebano_id']);
        
        $records = $this->reproduccionService->getPaginatedReproducciones($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Registros de reproducción obtenidos exitosamente',
            'data'    => $this->formatCollection(ReproduccionAnimalResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_reproduccion' => 'required|date',
            'tipo_reproduccion'  => 'nullable|string|max:16',
            'observacion'        => 'nullable|string|max:100',
            'animal_etapa_id'    => 'required_without_all:animal_id,etapa_id|exists:animal_etapa,id',
            'animal_id'          => 'required_without:animal_etapa_id|exists:animals,id',
            'etapa_id'           => 'required_without:animal_etapa_id|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $repro = $this->reproduccionService->createReproduccion($request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Reproducción registrada exitosamente',
                'data'    => $this->formatResource(ReproduccionAnimalResource::class, $repro),
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
            $repro = $this->reproduccionService->getReproduccionById((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Registro obtenido exitosamente',
                'data'    => $this->formatResource(ReproduccionAnimalResource::class, $repro)
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
            'fecha_reproduccion' => 'sometimes|date',
            'tipo_reproduccion'  => 'nullable|string|max:16',
            'observacion'        => 'nullable|string|max:100',
            'animal_etapa_id'    => 'nullable|exists:animal_etapa,id',
            'animal_id'          => 'nullable|exists:animals,id',
            'etapa_id'           => 'nullable|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $repro = $this->reproduccionService->updateReproduccion((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Reproducción actualizada exitosamente',
                'data'    => $this->formatResource(ReproduccionAnimalResource::class, $repro),
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
            $this->reproduccionService->deleteReproduccion((int)$id, request()->user());

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

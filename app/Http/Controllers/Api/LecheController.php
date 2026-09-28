<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Produccion\LecheResource;
use App\Http\Middleware\Legacy\Produccion\NormalizeIndexLeche;
use App\Http\Middleware\Legacy\Produccion\NormalizeShowLeche;
use App\Http\Middleware\Legacy\Produccion\NormalizeStoreLeche;
use App\Http\Middleware\Legacy\Produccion\NormalizeUpdateLeche;
use App\Services\Produccion\LecheService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class LecheController extends Controller
{
    public function __construct(
        protected LecheService $lecheService
    ) {
        $this->middleware(NormalizeIndexLeche::class)->only('index');
        $this->middleware(NormalizeShowLeche::class)->only('show');
        $this->middleware(NormalizeStoreLeche::class)->only('store');
        $this->middleware(NormalizeUpdateLeche::class)->only('update');
    }

    /**
     * Display a listing of leche.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['lactancia_id', 'fecha_inicio', 'fecha_fin', 'produccion_minima', 'nopaginate']);
        
        $records = $this->lecheService->getPaginatedLeche($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de registros de leche obtenida exitosamente',
            'data'    => $this->formatCollection(LecheResource::class, $records),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created leche record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_pesaje' => 'required|date',
            'pesaje_total' => 'required|numeric|min:0',
            'lactancia_id' => 'required|exists:lactancias,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $leche = $this->lecheService->createLeche($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de leche creado exitosamente',
                'data'    => $this->formatResource(LecheResource::class, $leche)
            ], Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified leche record.
     */
    public function show(Request $request, $id)
    {
        try {
            $leche = $this->lecheService->getLecheById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de leche obtenido exitosamente',
                'data'    => $this->formatResource(LecheResource::class, $leche)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de leche no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Update the specified leche record.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_pesaje' => 'sometimes|date',
            'pesaje_total' => 'sometimes|numeric|min:0',
            'lactancia_id' => 'sometimes|exists:lactancias,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $leche = $this->lecheService->updateLeche((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de leche actualizado exitosamente',
                'data'    => $this->formatResource(LecheResource::class, $leche)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de leche no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Remove the specified leche record.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->lecheService->deleteLeche((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de leche eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de leche no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

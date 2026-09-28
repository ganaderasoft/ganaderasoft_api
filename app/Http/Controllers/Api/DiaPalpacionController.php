<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\DiaPalpacionResource;
use App\Services\Sanidad\DiaPalpacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DiaPalpacionController extends Controller
{
    /**
     * Inyecta el servicio de días de palpación.
     */
    public function __construct(
        protected DiaPalpacionService $diaPalpacionService
    ) {}

    /**
     * Devuelve la lista de días de palpación.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'nopaginate']);
        $paginator = $this->diaPalpacionService->listDias($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de días de palpación obtenida exitosamente',
            'data'    => $this->formatCollection(DiaPalpacionResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra un nuevo día de palpación.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dias' => 'required|integer|min:1|unique:dias_palpacions,dias'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $dia = $this->diaPalpacionService->createDia($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Día de palpación creado exitosamente',
                'data'    => $this->formatResource(DiaPalpacionResource::class, $dia)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Obtiene el detalle de un día de palpación por su ID.
     */
    public function show(Request $request, $id)
    {
        try {
            $dia = $this->diaPalpacionService->getDiaById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Día de palpación obtenido exitosamente',
                'data'    => $this->formatResource(DiaPalpacionResource::class, $dia)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Día de palpación no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un día de palpación existente.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'dias' => 'required|integer|min:1|unique:dias_palpacions,dias,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $dia = $this->diaPalpacionService->updateDia((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Día de palpación actualizado exitosamente',
                'data'    => $this->formatResource(DiaPalpacionResource::class, $dia)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Día de palpación no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un día de palpación.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->diaPalpacionService->deleteDia((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Día de palpación eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Día de palpación no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

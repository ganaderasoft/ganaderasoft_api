<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\OvarioResource;
use App\Services\Sanidad\OvarioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OvarioController extends Controller
{
    /**
     * Inyecta el servicio de ovarios.
     */
    public function __construct(
        protected OvarioService $ovarioService
    ) {}

    /**
     * Devuelve la lista de ovarios.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['palpacion_id', 'nopaginate']);
        $paginator = $this->ovarioService->listOvarios($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de ovarios obtenida exitosamente',
            'data'    => $this->formatCollection(OvarioResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra un nuevo ovario.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'palpacion_id' => 'nullable|exists:palpacions,id',
            'tamano'       => 'nullable|string|max:40',
            'lado'         => 'nullable|string|in:IZQUIERDO,DERECHO,AMBOS,I,D',
            'medida'       => 'nullable|string|max:40',
            'foliculo_ids' => 'nullable|array',
            'foliculo_ids.*' => 'exists:foliculos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $ovario = $this->ovarioService->createOvario($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de ovario creado exitosamente',
                'data'    => $this->formatResource(OvarioResource::class, $ovario)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Obtiene el detalle de un ovario por su ID.
     */
    public function show(Request $request, $id)
    {
        try {
            $ovario = $this->ovarioService->getOvarioById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de ovario obtenido exitosamente',
                'data'    => $this->formatResource(OvarioResource::class, $ovario)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de ovario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un ovario existente.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'palpacion_id' => 'nullable|exists:palpacions,id',
            'tamano'       => 'nullable|string|max:40',
            'lado'         => 'nullable|string|in:IZQUIERDO,DERECHO,AMBOS,I,D',
            'medida'       => 'nullable|string|max:40',
            'foliculo_ids' => 'nullable|array',
            'foliculo_ids.*' => 'exists:foliculos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $ovario = $this->ovarioService->updateOvario((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de ovario actualizado exitosamente',
                'data'    => $this->formatResource(OvarioResource::class, $ovario)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de ovario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un ovario.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->ovarioService->deleteOvario((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de ovario eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de ovario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

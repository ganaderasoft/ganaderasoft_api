<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\CuernoResource;
use App\Services\Sanidad\CuernoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CuernoController extends Controller
{
    /**
     * Inyecta el servicio de cuernos uterinos.
     */
    public function __construct(
        protected CuernoService $cuernoService
    ) {}

    /**
     * Devuelve la lista de cuernos uterinos.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['palpacion_id', 'nopaginate']);
        $paginator = $this->cuernoService->listCuernos($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lista de cuernos uterinos obtenida exitosamente',
            'data'    => $this->formatCollection(CuernoResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra un nuevo cuerno uterino.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'palpacion_id'          => 'nullable|exists:palpacions,id',
            'tamano'                => 'nullable|string|max:40',
            'medicion'              => 'nullable|string|max:40',
            'lado'                  => 'nullable|string|in:IZQUIERDO,DERECHO,AMBOS,I,D',
            'iu_plano'              => 'nullable|string|max:40',
            'estado_sano'           => 'nullable|boolean',
            'patologia_nombre'      => 'nullable|string|max:80',
            'patologia_descripcion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cuerno = $this->cuernoService->createCuerno($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de cuerno uterino creado exitosamente',
                'data'    => $this->formatResource(CuernoResource::class, $cuerno)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Obtiene el detalle de un cuerno uterino por su ID.
     */
    public function show(Request $request, $id)
    {
        try {
            $cuerno = $this->cuernoService->getCuernoById((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de cuerno uterino obtenido exitosamente',
                'data'    => $this->formatResource(CuernoResource::class, $cuerno)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de cuerno uterino no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un cuerno uterino existente.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'palpacion_id'          => 'nullable|exists:palpacions,id',
            'tamano'                => 'nullable|string|max:40',
            'medicion'              => 'nullable|string|max:40',
            'lado'                  => 'nullable|string|in:IZQUIERDO,DERECHO,AMBOS,I,D',
            'iu_plano'              => 'nullable|string|max:40',
            'estado_sano'           => 'nullable|boolean',
            'patologia_nombre'      => 'nullable|string|max:80',
            'patologia_descripcion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cuerno = $this->cuernoService->updateCuerno((int)$id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de cuerno uterino actualizado exitosamente',
                'data'    => $this->formatResource(CuernoResource::class, $cuerno)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de cuerno uterino no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina un cuerno uterino.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->cuernoService->deleteCuerno((int)$id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de cuerno uterino eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de cuerno uterino no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

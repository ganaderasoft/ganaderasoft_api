<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\User\UserFincaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\User\UserFincaResource;
use Exception;
use App\Models\User;

class UserFincaController extends Controller
{
    protected $userFincaService;

    public function __construct(UserFincaService $userFincaService)
    {
        $this->userFincaService = $userFincaService;
    }

    /**
     * Listar fincas de un usuario específico
     */
    public function index(Request $request, $userId)
    {
        try {
            $user = User::with('fincas')->findOrFail($userId);
            
            if ($request->user()->cannot('read', $user)) {
                throw new AuthorizationException('No tienes permisos para ver las fincas de este usuario.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Fincas del usuario obtenidas exitosamente',
                'data' => $this->formatCollection(UserFincaResource::class, $user->fincas)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Vincular una o varias fincas al usuario
     */
    public function store(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'fincas' => 'required|array',
            'fincas.*.id' => 'required|exists:fincas,id',
            'fincas.*.access_level' => 'nullable|string|in:owner,operator,viewer',
            'fincas.*.is_default' => 'nullable|boolean',
            'fincas.*.status' => 'nullable|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->userFincaService->assignFincas($userId, $request->input('fincas'), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Fincas vinculadas exitosamente al usuario'
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar los permisos o el estado de una finca para el usuario
     */
    public function update(Request $request, $userId, $fincaId)
    {
        $validator = Validator::make($request->all(), [
            'access_level' => 'sometimes|string|in:owner,operator,viewer',
            'is_default' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->userFincaService->updateFincaAccess($userId, $fincaId, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Accesos a la finca actualizados exitosamente'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o finca no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Desvincular una finca del usuario
     */
    public function destroy(Request $request, $userId, $fincaId)
    {
        try {
            $this->userFincaService->removeFinca($userId, $fincaId, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Finca desvinculada exitosamente del usuario'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o finca no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Desactivar lógicamente el acceso a una finca para el usuario
     */
    public function disableAccess(Request $request, $userId, $fincaId)
    {
        try {
            $this->userFincaService->disableAccess($userId, $fincaId, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Acceso a la finca desactivado exitosamente'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o finca no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Activar lógicamente el acceso a una finca para el usuario
     */
    public function enableAccess(Request $request, $userId, $fincaId)
    {
        try {
            $this->userFincaService->enableAccess($userId, $fincaId, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Acceso a la finca activado exitosamente'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o finca no encontrados'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

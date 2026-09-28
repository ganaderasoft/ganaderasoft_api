<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\User\UserService;
use App\Http\Resources\User\UserResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Listar todos los usuarios.
     * Permite filtrar por name, email y status.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'email', 'status', 'nopaginate', 'search', 'role']);
            $users = $this->userService->listUsers($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de usuarios obtenida exitosamente',
                'data' => $this->formatCollection(UserResource::class, $users)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Crear un nuevo usuario y su persona asociada.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => ['required', 'string', 'max:20', 'regex:/^[VGEJ][0-9]+$/'],
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'required|string|email|max:255|unique:users,email|unique:personas,correo',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,code',
            'status' => 'nullable|string|in:active,suspended'
        ], [
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.regex' => 'La cédula debe comenzar con V, E, J o G seguido de números (ej: V12345678).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $this->userService->storeUser($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $this->formatResource(UserResource::class, $user)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener los detalles de un usuario específico.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $this->userService->getUser($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario obtenido exitosamente',
                'data' => $this->formatResource(UserResource::class, $user)
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
     * Actualizar los datos de un usuario existente.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => ['sometimes', 'string', 'max:20', 'regex:/^[VGEJ][0-9]+$/'],
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:50',
            // En update permitimos el mismo correo si es el mismo usuario. Asumiremos que el frontend o servicio controla esto, pero idealmente:
            'correo' => 'sometimes|string|email|max:255|unique:users,email,'.$id, 
            'password' => 'sometimes|string|min:8|confirmed',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'string|exists:roles,code',
            'status' => 'sometimes|string|in:active,suspended'
        ], [
            'cedula.regex' => 'La cédula debe comenzar con V, E, J o G seguido de números (ej: V12345678).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $this->userService->updateUser($id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $this->formatResource(UserResource::class, $user)
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un usuario del sistema.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->userService->deleteUser($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Desactivar (borrado lógico) un usuario.
     */
    public function disable(Request $request, $id)
    {
        try {
            $user = $this->userService->disableUser($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado exitosamente',
                'data' => $this->formatResource(UserResource::class, $user)
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Activar (restaurar lógicamente) un usuario.
     */
    public function enable(Request $request, $id)
    {
        try {
            $user = $this->userService->enableUser($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario activado exitosamente',
                'data' => $this->formatResource(UserResource::class, $user)
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

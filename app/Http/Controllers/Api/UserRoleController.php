<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Resources\User\RoleResource;
use App\Http\Resources\User\PermissionResource;
use App\Services\User\UserRoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserRoleController extends Controller
{
    protected $userRoleService;

    public function __construct(UserRoleService $userRoleService)
    {
        $this->userRoleService = $userRoleService;
    }

    public function index(Request $request, User $user)
    {
        $roles = $this->userRoleService->getUserRoles($user, $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Roles del usuario',
            'data' => $this->formatCollection(RoleResource::class, $roles)
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'role_code' => 'required|string|exists:roles,code'
        ]);

        $roles = $this->userRoleService->assignRole($user, $request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Rol asignado exitosamente',
            'data' => $this->formatCollection(RoleResource::class, $roles)
        ]);
    }

    public function destroy(Request $request, User $user, Role $role)
    {
        $this->userRoleService->removeRole($user, $role, $request->user());
        return response()->json(['success' => true, 'message' => 'Rol removido exitosamente']);
    }

    /**
     * Endpoint adicional para consultar permisos directos (heredados de los roles) de un usuario.
     * Solo lectura.
     */
    public function getPermissions(Request $request, User $user)
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        // Obtener permisos de todos sus roles (sin duplicados)
        $permissions = collect();
        foreach ($user->roles()->with('permissions')->get() as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        return $this->formatCollection(PermissionResource::class, $permissions->unique('id')->values());
    }
}

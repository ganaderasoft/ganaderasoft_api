<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Http\Resources\User\PermissionResource;
use App\Services\User\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RolePermissionController extends Controller
{
    protected $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    public function index(Request $request, Role $role)
    {
        $permissions = $this->rolePermissionService->getRolePermissions($role, $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Permisos del rol',
            'data' => $this->formatCollection(PermissionResource::class, $permissions)
        ]);
    }

    public function store(Request $request, Role $role)
    {
        $request->validate([
            'permission_code' => 'required|string|exists:permissions,code'
        ]);

        $permissions = $this->rolePermissionService->assignPermission($role, $request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Permiso asignado exitosamente al rol',
            'data' => $this->formatCollection(PermissionResource::class, $permissions)
        ]);
    }

    public function destroy(Request $request, Role $role, Permission $permission)
    {
        $this->rolePermissionService->removePermission($role, $permission, $request->user());
        return response()->json(['success' => true, 'message' => 'Permiso removido exitosamente del rol']);
    }
}

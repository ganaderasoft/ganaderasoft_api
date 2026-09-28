<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Http\Resources\User\PermissionResource;
use App\Services\User\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        $permissions = $this->permissionService->getPaginatedPermissions($request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Lista de permisos',
            'data' => $this->formatCollection(PermissionResource::class, $permissions)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:permissions,code',
            'module' => 'required|string',
            'action' => 'required|string'
        ]);

        $permission = $this->permissionService->createPermission($request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Permiso creado exitosamente',
            'data' => $this->formatResource(PermissionResource::class, $permission)
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Permission $permission)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detalle del permiso',
            'data' => $this->formatResource(PermissionResource::class, $permission)
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'code' => 'required|string|unique:permissions,code,' . $permission->id,
            'module' => 'required|string',
            'action' => 'required|string'
        ]);

        $permission = $this->permissionService->updatePermission($permission, $request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Permiso actualizado exitosamente',
            'data' => $this->formatResource(PermissionResource::class, $permission)
        ]);
    }

    public function destroy(Request $request, Permission $permission)
    {
        $this->permissionService->deletePermission($permission, $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Permiso eliminado exitosamente'
        ]);
    }
}

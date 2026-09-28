<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Http\Resources\User\RoleResource;
use App\Services\User\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(Request $request)
    {
        $roles = $this->roleService->getPaginatedRoles($request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Lista de roles',
            'data' => $this->formatCollection(RoleResource::class, $roles)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:roles,code',
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $role = $this->roleService->createRole($request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Rol creado exitosamente',
            'data' => $this->formatResource(RoleResource::class, $role)
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Role $role)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detalle del rol',
            'data' => $this->formatResource(RoleResource::class, $role)
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'code' => 'required|string|unique:roles,code,' . $role->id,
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $role = $this->roleService->updateRole($role, $request->all(), $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente',
            'data' => $this->formatResource(RoleResource::class, $role)
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        $this->roleService->deleteRole($role, $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado exitosamente'
        ]);
    }
}

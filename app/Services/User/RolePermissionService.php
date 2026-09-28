<?php

namespace App\Services\User;

use App\Services\BaseService;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RolePermissionService extends BaseService
{
    public function getRolePermissions(Role $role, User $adminUser)
    {
        if ($adminUser->cannot('read', clone $role)) {
            throw new AuthorizationException('No autorizado para ver estos permisos.');
        }
        return $role->permissions;
    }

    public function assignPermission(Role $role, array $data, User $adminUser)
    {
        if ($adminUser->cannot('assignPermission', clone $role)) {
            throw new AuthorizationException('No tienes permisos para asignar permisos a roles.');
        }

        $permission = Permission::where('code', $data['permission_code'])->firstOrFail();

        // Evitar duplicados
        if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }

        return $role->permissions;
    }

    public function removePermission(Role $role, Permission $permission, User $adminUser)
    {
        if ($adminUser->cannot('removePermission', clone $role)) {
            throw new AuthorizationException('No tienes permisos para remover permisos a roles.');
        }

        $role->permissions()->detach($permission->id);
    }
}

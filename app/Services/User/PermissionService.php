<?php

namespace App\Services\User;

use App\Services\BaseService;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class PermissionService extends BaseService
{
    public function getPaginatedPermissions(array $filters, User $user)
    {
        if ($user->cannot('readAny', Permission::class)) {
            throw new AuthorizationException('No tiene permisos para ver permisos.');
        }

        $query = Permission::query();
        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->get();
        }
        return $query->paginate(15);
    }

    public function createPermission(array $data, User $user)
    {
        if ($user->cannot('create', Permission::class)) {
            throw new AuthorizationException('No tiene permisos para crear permisos.');
        }

        return Permission::create($data);
    }

    public function updatePermission(Permission $permission, array $data, User $user)
    {
        if ($user->cannot('update', $permission)) {
            throw new AuthorizationException('No tiene permisos para actualizar este permiso.');
        }

        $permission->update($data);
        return $permission;
    }

    public function deletePermission(Permission $permission, User $user)
    {
        if ($user->cannot('delete', $permission)) {
            throw new AuthorizationException('No tiene permisos para eliminar este permiso.');
        }

        $permission->delete();
    }
}

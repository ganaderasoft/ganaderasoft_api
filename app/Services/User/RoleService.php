<?php

namespace App\Services\User;

use App\Services\BaseService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RoleService extends BaseService
{
    public function getPaginatedRoles(array $filters, User $user)
    {
        if ($user->cannot('readAny', Role::class)) {
            throw new AuthorizationException('No tiene permisos para ver roles.');
        }

        $query = Role::query();
        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->get();
        }
        return $query->paginate(15);
    }

    public function createRole(array $data, User $user)
    {
        if ($user->cannot('create', Role::class)) {
            throw new AuthorizationException('No tiene permisos para crear roles.');
        }

        return Role::create($data);
    }

    public function updateRole(Role $role, array $data, User $user)
    {
        if ($user->cannot('update', $role)) {
            throw new AuthorizationException('No tiene permisos para actualizar este rol.');
        }

        $role->update($data);
        return $role;
    }

    public function deleteRole(Role $role, User $user)
    {
        if ($user->cannot('delete', $role)) {
            throw new AuthorizationException('No tiene permisos para eliminar este rol.');
        }

        $role->delete();
    }
}

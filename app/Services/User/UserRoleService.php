<?php

namespace App\Services\User;

use App\Services\BaseService;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;

class UserRoleService extends BaseService
{
    public function getUserRoles(User $targetUser, User $adminUser)
    {
        if ($adminUser->cannot('read', $targetUser)) {
            throw new AuthorizationException('No autorizado para ver estos roles.');
        }
        return $targetUser->roles;
    }

    public function assignRole(User $targetUser, array $data, User $adminUser)
    {
        if ($adminUser->cannot('assignRole', clone $targetUser)) {
            throw new AuthorizationException('No tienes permisos para asignar roles.');
        }

        $role = Role::where('code', $data['role_code'])->firstOrFail();

        // Evitar duplicados
        if (!$targetUser->roles()->where('role_id', $role->id)->exists()) {
            $targetUser->roles()->attach($role->id);
        }

        return $targetUser->roles;
    }

    public function removeRole(User $targetUser, Role $role, User $adminUser)
    {
        if ($adminUser->cannot('removeRole', clone $targetUser)) {
            throw new AuthorizationException('No tienes permisos para remover roles.');
        }

        $targetUser->roles()->detach($role->id);
    }
}

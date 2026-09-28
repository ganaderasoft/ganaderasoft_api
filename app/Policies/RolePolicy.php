<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy extends BasePolicy
{
    public function readAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function read(User $user, Role $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Role $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Role $model): bool
    {
        return $user->isAdmin();
    }

    public function assignPermission(User $user, Role $role): bool
    {
        return $user->isAdmin();
    }

    public function removePermission(User $user, Role $role): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy extends BasePolicy
{
    public function readAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function read(User $user, Permission $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Permission $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Permission $model): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cuerno;

class CuernoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de cuerno uterino.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('cuerno.read');
    }

    /**
     * Determina si el usuario puede ver un registro específico de cuerno uterino.
     */
    public function read(User $user, Cuerno $model): bool
    {
        return $user->hasPermissionTo('cuerno.read');
    }

    /**
     * Determina si el usuario puede crear registros de cuernos uterinos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('cuerno.create');
    }

    /**
     * Determina si el usuario puede actualizar un registro de cuerno uterino.
     */
    public function update(User $user, Cuerno $model): bool
    {
        return $user->hasPermissionTo('cuerno.update');
    }

    /**
     * Determina si el usuario puede eliminar un registro de cuerno uterino.
     */
    public function delete(User $user, Cuerno $model): bool
    {
        return $user->hasPermissionTo('cuerno.delete');
    }
}

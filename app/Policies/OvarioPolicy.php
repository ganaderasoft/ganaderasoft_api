<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ovario;

class OvarioPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de ovario.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('ovario.read');
    }

    /**
     * Determina si el usuario puede ver un registro específico de ovario.
     */
    public function read(User $user, Ovario $model): bool
    {
        return $user->hasPermissionTo('ovario.read');
    }

    /**
     * Determina si el usuario puede crear registros de ovarios.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ovario.create');
    }

    /**
     * Determina si el usuario puede actualizar un registro de ovario.
     */
    public function update(User $user, Ovario $model): bool
    {
        return $user->hasPermissionTo('ovario.update');
    }

    /**
     * Determina si el usuario puede eliminar un registro de ovario.
     */
    public function delete(User $user, Ovario $model): bool
    {
        return $user->hasPermissionTo('ovario.delete');
    }
}

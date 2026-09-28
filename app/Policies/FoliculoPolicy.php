<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Foliculo;

class FoliculoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de folículo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('foliculo.read');
    }

    /**
     * Determina si el usuario puede ver un registro específico de folículo.
     */
    public function read(User $user, Foliculo $model): bool
    {
        return $user->hasPermissionTo('foliculo.read');
    }

    /**
     * Determina si el usuario puede crear registros de folículos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('foliculo.create');
    }

    /**
     * Determina si el usuario puede actualizar un registro de folículo.
     */
    public function update(User $user, Foliculo $model): bool
    {
        return $user->hasPermissionTo('foliculo.update');
    }

    /**
     * Determina si el usuario puede eliminar un registro de folículo.
     */
    public function delete(User $user, Foliculo $model): bool
    {
        return $user->hasPermissionTo('foliculo.delete');
    }
}

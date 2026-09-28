<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Etapa;

class EtapaPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('etapa.read');
    }

    /**
     * Determina si el usuario puede ver el modelo.
     */
    public function read(User $user, Etapa $model): bool
    {
        return $user->hasPermissionTo('etapa.read');
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('etapa.create');
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, Etapa $model): bool
    {
        return $user->hasPermissionTo('etapa.update');
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, Etapa $model): bool
    {
        return $user->hasPermissionTo('etapa.delete');
    }
}

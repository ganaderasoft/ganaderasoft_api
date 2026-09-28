<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TipoAnimal;

class TipoAnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('tipo_animal.read');
    }

    /**
     * Determina si el usuario puede ver el modelo.
     */
    public function read(User $user, TipoAnimal $model): bool
    {
        return $user->hasPermissionTo('tipo_animal.read');
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tipo_animal.create');
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, TipoAnimal $model): bool
    {
        return $user->hasPermissionTo('tipo_animal.update');
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, TipoAnimal $model): bool
    {
        return $user->hasPermissionTo('tipo_animal.delete');
    }
}

<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\User;

class PersonaPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver la lista de modelos.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('persona.read');
    }

    /**
     * Determina si el usuario puede ver el modelo.
     */
    public function read(User $user, Persona $model): bool
    {
        return $user->hasPermissionTo('persona.read') || $user->personas->contains('id', $model->id);
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('persona.create');
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, Persona $model): bool
    {
        return $user->hasPermissionTo('persona.update');
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, Persona $model): bool
    {
        return $user->hasPermissionTo('persona.delete');
    }

    /**
     * Determina si el usuario puede desactivar el modelo.
     */
    public function disable(User $user, Persona $model): bool
    {
        return $user->hasPermissionTo('persona.update');
    }

    /**
     * Determina si el usuario puede activar el modelo.
     */
    public function enable(User $user, Persona $model): bool
    {
        return $user->hasPermissionTo('persona.update');
    }
}

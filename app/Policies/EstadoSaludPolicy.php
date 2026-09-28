<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EstadoSalud;

class EstadoSaludPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar estados de salud.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('estado_salud.read');
    }

    /**
     * Determina si el usuario puede ver un estado de salud.
     */
    public function read(User $user, EstadoSalud $estadoSalud): bool
    {
        return $user->hasPermissionTo('estado_salud.read');
    }

    /**
     * Determina si el usuario puede crear un estado de salud.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('estado_salud.create');
    }

    /**
     * Determina si el usuario puede actualizar un estado de salud.
     */
    public function update(User $user, EstadoSalud $estadoSalud): bool
    {
        return $user->hasPermissionTo('estado_salud.update');
    }

    /**
     * Determina si el usuario puede eliminar un estado de salud.
     */
    public function delete(User $user, EstadoSalud $estadoSalud): bool
    {
        return $user->hasPermissionTo('estado_salud.delete');
    }
}

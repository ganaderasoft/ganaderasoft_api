<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TipoTrabajador;

class TipoTrabajadorPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver la lista de tipos de trabajador.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('tipo_trabajador.read');
    }

    /**
     * Determina si el usuario puede ver un tipo de trabajador específico.
     */
    public function read(User $user, TipoTrabajador $model): bool
    {
        return $user->hasPermissionTo('tipo_trabajador.read');
    }

    /**
     * Determina si el usuario puede crear tipos de trabajador.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tipo_trabajador.create');
    }

    /**
     * Determina si el usuario puede actualizar el tipo de trabajador.
     */
    public function update(User $user, TipoTrabajador $model): bool
    {
        return $user->hasPermissionTo('tipo_trabajador.update');
    }

    /**
     * Determina si el usuario puede eliminar el tipo de trabajador.
     */
    public function delete(User $user, TipoTrabajador $model): bool
    {
        return $user->hasPermissionTo('tipo_trabajador.delete');
    }
}

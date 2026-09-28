<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacuna;

class VacunaPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar vacunas.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('vacuna.read');
    }

    /**
     * Determina si el usuario puede ver una vacuna.
     */
    public function read(User $user, Vacuna $vacuna): bool
    {
        return $user->hasPermissionTo('vacuna.read');
    }

    /**
     * Determina si el usuario puede crear una vacuna.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vacuna.create');
    }

    /**
     * Determina si el usuario puede actualizar una vacuna.
     */
    public function update(User $user, Vacuna $vacuna): bool
    {
        return $user->hasPermissionTo('vacuna.update');
    }

    /**
     * Determina si el usuario puede eliminar una vacuna.
     */
    public function delete(User $user, Vacuna $vacuna): bool
    {
        return $user->hasPermissionTo('vacuna.delete');
    }
}

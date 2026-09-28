<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Terreno;

class TerrenoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier terreno.
     */
    public function readAny(User $user): bool
    {
        if (!$user->hasPermissionTo('terreno.read')) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede ver un terreno en específico.
     */
    public function read(User $user, Terreno $terreno): bool
    {
        if (!$user->hasPermissionTo('terreno.read')) {
            return false;
        }

        // El terreno pertenece directamente a una Finca
        return $this->checkFincaAccess($user, $terreno->finca_id);
    }

    /**
     * Determina si el usuario puede crear un terreno.
     */
    public function create(User $user, int $fincaId): bool
    {
        if (!$user->hasPermissionTo('terreno.create')) {
            return false;
        }

        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede actualizar el terreno.
     */
    public function update(User $user, Terreno $terreno): bool
    {
        if (!$user->hasPermissionTo('terreno.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $terreno->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el terreno.
     */
    public function delete(User $user, Terreno $terreno): bool
    {
        if (!$user->hasPermissionTo('terreno.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $terreno->finca_id);
    }
}

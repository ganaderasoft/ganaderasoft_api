<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Rebano;

class RebanoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier rebaño.
     */
    public function readAny(User $user): bool
    {
        if (!$user->hasPermissionTo('rebano.read')) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede ver un rebaño en específico.
     */
    public function read(User $user, Rebano $rebano): bool
    {
        if (!$user->hasPermissionTo('rebano.read')) {
            return false;
        }

        // El rebaño pertenece directamente a una Finca
        return $this->checkFincaAccess($user, $rebano->finca_id);
    }

    /**
     * Determina si el usuario puede crear un rebaño.
     */
    public function create(User $user, int $fincaId): bool
    {
        if (!$user->hasPermissionTo('rebano.create')) {
            return false;
        }

        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede actualizar el rebaño.
     */
    public function update(User $user, Rebano $rebano): bool
    {
        if (!$user->hasPermissionTo('rebano.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $rebano->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar definitivamente el rebaño.
     */
    public function delete(User $user, Rebano $rebano): bool
    {
        if (!$user->hasPermissionTo('rebano.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $rebano->finca_id);
    }
}

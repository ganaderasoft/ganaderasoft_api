<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PersonalFinca;

class PersonalFincaPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de personal.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('personal_finca.read');
    }

    /**
     * Determina si el usuario puede ver un registro de personal en específico.
     */
    public function read(User $user, PersonalFinca $personalFinca): bool
    {
        if (!$user->hasPermissionTo('personal_finca.read')) {
            return false;
        }

        // El personal pertenece directamente a una Finca
        return $this->checkFincaAccess($user, $personalFinca->finca_id);
    }

    /**
     * Determina si el usuario puede crear un registro de personal.
     */
    public function create(User $user, int $fincaId): bool
    {
        if (!$user->hasPermissionTo('personal_finca.create')) {
            return false;
        }

        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede actualizar el registro de personal.
     */
    public function update(User $user, PersonalFinca $personalFinca): bool
    {
        if (!$user->hasPermissionTo('personal_finca.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $personalFinca->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el registro de personal.
     */
    public function delete(User $user, PersonalFinca $personalFinca): bool
    {
        if (!$user->hasPermissionTo('personal_finca.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $personalFinca->finca_id);
    }
}

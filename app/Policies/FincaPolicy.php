<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Finca;

class FincaPolicy extends BasePolicy
{
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('finca.read');
    }

    /**
     * Determina si el usuario puede ver una finca en específico.
     */
    public function read(User $user, Finca $finca): bool
    {
        if (!$user->hasPermissionTo('finca.read')) {
            return false;
        }

        return $this->checkFincaAccess($user, $finca->id);
    }

    /**
     * Determina si el usuario puede crear una finca.
     */
    public function create(User $user, ?int $propietarioId = null): bool
    {
        if (!$user->hasPermissionTo('finca.create')) {
            return false;
        }

        if (!$user->propietario || $user->propietario->id !== $propietarioId) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede actualizar la finca.
     */
    public function update(User $user, Finca $finca, ?int $nuevoPropietarioId = null): bool
    {
        if (!$user->hasPermissionTo('finca.update')) {
            return false;
        }

        if ($nuevoPropietarioId !== null && $nuevoPropietarioId !== $finca->propietario_id) {
            return false;
        }

        return $this->checkFincaAccess($user, $finca->id);
    }

    /**
     * Determina si el usuario puede eliminar definitivamente la finca.
     */
    public function delete(User $user, Finca $finca): bool
    {
        if (!$user->hasPermissionTo('finca.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $finca->id);
    }
}

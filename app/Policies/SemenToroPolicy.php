<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SemenToro;
use App\Models\Animal;

class SemenToroPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar los registros de semen de toro.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('semen_toro.read');
    }

    /**
     * Determina si el usuario puede ver un registro de semen de toro.
     */
    public function read(User $user, SemenToro $semenToro): bool
    {
        if (!$user->hasPermissionTo('semen_toro.read')) {
            return false;
        }

        $toro = $semenToro->toro;
        return $toro ? $this->checkFincaAccess($user, optional($toro->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de semen para un toro.
     */
    public function create(User $user, int $toroId = null): bool
    {
        if (!$user->hasPermissionTo('semen_toro.create')) {
            return false;
        }

        if ($toroId) {
            $toro = Animal::with('rebano')->find($toroId);
            return $toro ? $this->checkFincaAccess($user, optional($toro->rebano)->finca_id) : false;
        }

        return false;
    }

    /**
     * Determina si el usuario puede actualizar un registro de semen de toro.
     */
    public function update(User $user, SemenToro $semenToro): bool
    {
        if (!$user->hasPermissionTo('semen_toro.update')) {
            return false;
        }

        $toro = $semenToro->toro;
        return $toro ? $this->checkFincaAccess($user, optional($toro->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de semen de toro.
     */
    public function delete(User $user, SemenToro $semenToro): bool
    {
        if (!$user->hasPermissionTo('semen_toro.delete')) {
            return false;
        }

        $toro = $semenToro->toro;
        return $toro ? $this->checkFincaAccess($user, optional($toro->rebano)->finca_id) : false;
    }
}

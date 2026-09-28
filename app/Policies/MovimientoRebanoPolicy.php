<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MovimientoRebano;
use App\Models\Rebano;
use App\Models\Finca;

class MovimientoRebanoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar los movimientos de rebaño.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('movimiento_rebano.read');
    }

    /**
     * Determina si el usuario puede ver un movimiento de rebaño.
     */
    public function read(User $user, MovimientoRebano $movimientoRebano): bool
    {
        if (!$user->hasPermissionTo('movimiento_rebano.read')) {
            return false;
        }

        return $this->checkFincaAccess($user, $movimientoRebano->finca_id) || $this->checkFincaAccess($user, $movimientoRebano->finca_destino_id);
    }

    /**
     * Determina si el usuario puede crear un movimiento de rebaño.
     */
    public function create(User $user, int $fincaId = null, int $fincaDestinoId = null): bool
    {
        if (!$user->hasPermissionTo('movimiento_rebano.create')) {
            return false;
        }

        if ($fincaId && $fincaDestinoId) {
            return $this->checkFincaAccess($user, $fincaId) && $this->checkFincaAccess($user, $fincaDestinoId);
        }

        return false;
    }

    /**
     * Determina si el usuario puede actualizar un movimiento de rebaño.
     */
    public function update(User $user, MovimientoRebano $movimientoRebano): bool
    {
        if (!$user->hasPermissionTo('movimiento_rebano.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $movimientoRebano->finca_id) && $this->checkFincaAccess($user, $movimientoRebano->finca_destino_id);
    }

    /**
     * Determina si el usuario puede eliminar un movimiento de rebaño.
     */
    public function delete(User $user, MovimientoRebano $movimientoRebano): bool
    {
        if (!$user->hasPermissionTo('movimiento_rebano.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $movimientoRebano->finca_id) && $this->checkFincaAccess($user, $movimientoRebano->finca_destino_id);
    }
}

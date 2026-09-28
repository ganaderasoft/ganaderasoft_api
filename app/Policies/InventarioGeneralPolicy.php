<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InventarioGeneral;

class InventarioGeneralPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier inventario general.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('inventario_general.read');
    }

    /**
     * Determina si el usuario puede ver un inventario general en específico.
     */
    public function read(User $user, InventarioGeneral $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_general.read')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }

    /**
     * Determina si el usuario puede crear un inventario general.
     */
    public function create(User $user, int $fincaId): bool
    {
        if (!$user->hasPermissionTo('inventario_general.create')) {
            return false;
        }

        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede actualizar el inventario general.
     */
    public function update(User $user, InventarioGeneral $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_general.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el inventario general.
     */
    public function delete(User $user, InventarioGeneral $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_general.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }
}

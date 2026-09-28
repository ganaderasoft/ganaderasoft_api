<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InventarioBufalo;

class InventarioBufaloPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier inventario de búfalos.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('inventario_bufalo.read');
    }

    /**
     * Determina si el usuario puede ver un inventario de búfalos en específico.
     */
    public function read(User $user, InventarioBufalo $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_bufalo.read')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }

    /**
     * Determina si el usuario puede crear un inventario de búfalos.
     */
    public function create(User $user, int $fincaId): bool
    {
        if (!$user->hasPermissionTo('inventario_bufalo.create')) {
            return false;
        }

        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede actualizar el inventario de búfalos.
     */
    public function update(User $user, InventarioBufalo $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_bufalo.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el inventario de búfalos.
     */
    public function delete(User $user, InventarioBufalo $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_bufalo.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }
}

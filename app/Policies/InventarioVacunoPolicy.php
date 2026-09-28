<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InventarioVacuno;

class InventarioVacunoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier inventario vacuno.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('inventario_vacuno.read');
    }

    /**
     * Determina si el usuario puede ver un inventario vacuno en específico.
     */
    public function read(User $user, InventarioVacuno $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_vacuno.read')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }

    /**
     * Determina si el usuario puede crear un inventario vacuno.
     */
    public function create(User $user, int $fincaId): bool
    {
        if (!$user->hasPermissionTo('inventario_vacuno.create')) {
            return false;
        }

        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede actualizar el inventario vacuno.
     */
    public function update(User $user, InventarioVacuno $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_vacuno.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el inventario vacuno.
     */
    public function delete(User $user, InventarioVacuno $inventario): bool
    {
        if (!$user->hasPermissionTo('inventario_vacuno.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $inventario->finca_id);
    }
}

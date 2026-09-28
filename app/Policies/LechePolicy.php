<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Leche;
use App\Models\Lactancia;

class LechePolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de leche.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('leche.read');
    }

    /**
     * Determina si el usuario puede ver un registro de leche en específico.
     */
    public function read(User $user, Leche $leche): bool
    {
        if (!$user->hasPermissionTo('leche.read')) {
            return false;
        }

        // Leche -> Lactancia -> Animal -> Rebaño -> Finca
        $lactancia = $leche->lactancia;
        $animal = $lactancia ? $lactancia->animal : null;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de leche.
     */
    public function create(User $user, int $lactanciaId): bool
    {
        if (!$user->hasPermissionTo('leche.create')) {
            return false;
        }

        $lactancia = Lactancia::with('animal.rebano')->find($lactanciaId);
        $animal = $lactancia ? $lactancia->animal : null;
        
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar el registro de leche.
     */
    public function update(User $user, Leche $leche): bool
    {
        if (!$user->hasPermissionTo('leche.update')) {
            return false;
        }

        $lactancia = $leche->lactancia;
        $animal = $lactancia ? $lactancia->animal : null;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar el registro de leche.
     */
    public function delete(User $user, Leche $leche): bool
    {
        if (!$user->hasPermissionTo('leche.delete')) {
            return false;
        }

        $lactancia = $leche->lactancia;
        $animal = $lactancia ? $lactancia->animal : null;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

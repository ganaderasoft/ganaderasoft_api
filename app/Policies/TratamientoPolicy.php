<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tratamiento;
use App\Models\Diagnostico;

class TratamientoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar tratamientos.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('tratamiento.read');
    }

    /**
     * Determina si el usuario puede ver un registro de tratamiento.
     */
    public function read(User $user, Tratamiento $tratamiento): bool
    {
        if (!$user->hasPermissionTo('tratamiento.read')) {
            return false;
        }

        $animal = optional($tratamiento->diagnostico)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un tratamiento para un diagnóstico específico.
     */
    public function create(User $user, int $diagnosticoId): bool
    {
        if (!$user->hasPermissionTo('tratamiento.create')) {
            return false;
        }

        $diagnostico = Diagnostico::with('animal.rebano')->find($diagnosticoId);
        $animal = $diagnostico ? $diagnostico->animal : null;

        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar un tratamiento.
     */
    public function update(User $user, Tratamiento $tratamiento): bool
    {
        if (!$user->hasPermissionTo('tratamiento.update')) {
            return false;
        }

        $animal = optional($tratamiento->diagnostico)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un tratamiento.
     */
    public function delete(User $user, Tratamiento $tratamiento): bool
    {
        if (!$user->hasPermissionTo('tratamiento.delete')) {
            return false;
        }

        $animal = optional($tratamiento->diagnostico)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

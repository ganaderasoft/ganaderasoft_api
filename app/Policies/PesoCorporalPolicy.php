<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PesoCorporal;
use App\Models\Animal;

class PesoCorporalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar los registros de peso corporal.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('peso_corporal.read');
    }

    /**
     * Determina si el usuario puede ver un registro de peso corporal.
     */
    public function read(User $user, PesoCorporal $pesoCorporal): bool
    {
        if (!$user->hasPermissionTo('peso_corporal.read')) {
            return false;
        }

        $animal = optional($pesoCorporal->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de peso para un animal.
     */
    public function create(User $user, int $animalId = null): bool
    {
        if (!$user->hasPermissionTo('peso_corporal.create')) {
            return false;
        }

        if ($animalId) {
            $animal = Animal::with('rebano')->find($animalId);
            return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
        }

        return false;
    }

    /**
     * Determina si el usuario puede actualizar un registro de peso corporal.
     */
    public function update(User $user, PesoCorporal $pesoCorporal): bool
    {
        if (!$user->hasPermissionTo('peso_corporal.update')) {
            return false;
        }

        $animal = optional($pesoCorporal->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de peso corporal.
     */
    public function delete(User $user, PesoCorporal $pesoCorporal): bool
    {
        if (!$user->hasPermissionTo('peso_corporal.delete')) {
            return false;
        }

        $animal = optional($pesoCorporal->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

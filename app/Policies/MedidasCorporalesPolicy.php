<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MedidasCorporales;
use App\Models\Animal;

class MedidasCorporalesPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar las medidas corporales.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('medidas_corporales.read');
    }

    /**
     * Determina si el usuario puede ver un registro de medidas corporales.
     */
    public function read(User $user, MedidasCorporales $medidasCorporales): bool
    {
        if (!$user->hasPermissionTo('medidas_corporales.read')) {
            return false;
        }

        $animal = optional($medidasCorporales->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de medidas corporales.
     */
    public function create(User $user, int $animalId = null): bool
    {
        if (!$user->hasPermissionTo('medidas_corporales.create')) {
            return false;
        }

        if ($animalId) {
            $animal = Animal::with('rebano')->find($animalId);
            return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
        }

        return false;
    }

    /**
     * Determina si el usuario puede actualizar un registro de medidas corporales.
     */
    public function update(User $user, MedidasCorporales $medidasCorporales): bool
    {
        if (!$user->hasPermissionTo('medidas_corporales.update')) {
            return false;
        }

        $animal = optional($medidasCorporales->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de medidas corporales.
     */
    public function delete(User $user, MedidasCorporales $medidasCorporales): bool
    {
        if (!$user->hasPermissionTo('medidas_corporales.delete')) {
            return false;
        }

        $animal = optional($medidasCorporales->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

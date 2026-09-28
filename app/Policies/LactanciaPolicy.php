<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lactancia;
use App\Models\EtapaAnimal;
use App\Models\Animal;

class LactanciaPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de lactancia.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('lactancia.read');
    }

    /**
     * Determina si el usuario puede ver un registro de lactancia en específico.
     */
    public function read(User $user, Lactancia $lactancia): bool
    {
        if (!$user->hasPermissionTo('lactancia.read')) {
            return false;
        }

        // Lactancia -> Animal -> Rebaño -> Finca
        $animal = $lactancia->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de lactancia para un animal o etapa_animal específico.
     * Aquí recibimos el ID de animal_etapa_id o animal_id para validar su finca.
     */
    public function create(User $user, int $animalId = null, int $animalEtapaId = null): bool
    {
        if (!$user->hasPermissionTo('lactancia.create')) {
            return false;
        }

        $animal = null;
        if ($animalId) {
            $animal = Animal::find($animalId);
        } elseif ($animalEtapaId) {
            $etapaAnimal = EtapaAnimal::with('animal')->find($animalEtapaId);
            $animal = $etapaAnimal ? $etapaAnimal->animal : null;
        }

        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar el registro de lactancia.
     */
    public function update(User $user, Lactancia $lactancia): bool
    {
        if (!$user->hasPermissionTo('lactancia.update')) {
            return false;
        }

        $animal = $lactancia->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar el registro de lactancia.
     */
    public function delete(User $user, Lactancia $lactancia): bool
    {
        if (!$user->hasPermissionTo('lactancia.delete')) {
            return false;
        }

        $animal = $lactancia->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReproduccionAnimal;
use App\Models\Animal;
use App\Models\EtapaAnimal;

class ReproduccionAnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar registros de reproducción.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('reproduccion_animal.read');
    }

    /**
     * Determina si el usuario puede ver un registro de reproducción.
     */
    public function read(User $user, ReproduccionAnimal $reproduccionAnimal): bool
    {
        if (!$user->hasPermissionTo('reproduccion_animal.read')) {
            return false;
        }

        $animal = optional($reproduccionAnimal->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de reproducción para un animal.
     */
    public function create(User $user, int $animalId = null, int $animalEtapaId = null): bool
    {
        if (!$user->hasPermissionTo('reproduccion_animal.create')) {
            return false;
        }

        $animal = null;
        if ($animalId) {
            $animal = Animal::with('rebano')->find($animalId);
        } elseif ($animalEtapaId) {
            $etapaAnimal = EtapaAnimal::with('animal.rebano')->find($animalEtapaId);
            $animal = $etapaAnimal ? $etapaAnimal->animal : null;
        }

        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar un registro de reproducción.
     */
    public function update(User $user, ReproduccionAnimal $reproduccionAnimal): bool
    {
        if (!$user->hasPermissionTo('reproduccion_animal.update')) {
            return false;
        }

        $animal = optional($reproduccionAnimal->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de reproducción.
     */
    public function delete(User $user, ReproduccionAnimal $reproduccionAnimal): bool
    {
        if (!$user->hasPermissionTo('reproduccion_animal.delete')) {
            return false;
        }

        $animal = optional($reproduccionAnimal->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

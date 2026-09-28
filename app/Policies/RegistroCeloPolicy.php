<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RegistroCelo;
use App\Models\Animal;
use App\Models\EtapaAnimal;

class RegistroCeloPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar registros de celo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('registro_celo.read');
    }

    /**
     * Determina si el usuario puede ver un registro de celo.
     */
    public function read(User $user, RegistroCelo $registroCelo): bool
    {
        if (!$user->hasPermissionTo('registro_celo.read')) {
            return false;
        }

        $animal = optional($registroCelo->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de celo para un animal.
     */
    public function create(User $user, int $animalId = null, int $animalEtapaId = null): bool
    {
        if (!$user->hasPermissionTo('registro_celo.create')) {
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
     * Determina si el usuario puede actualizar un registro de celo.
     */
    public function update(User $user, RegistroCelo $registroCelo): bool
    {
        if (!$user->hasPermissionTo('registro_celo.update')) {
            return false;
        }

        $animal = optional($registroCelo->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de celo.
     */
    public function delete(User $user, RegistroCelo $registroCelo): bool
    {
        if (!$user->hasPermissionTo('registro_celo.delete')) {
            return false;
        }

        $animal = optional($registroCelo->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

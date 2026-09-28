<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Palpacion;
use App\Models\Animal;
use App\Models\EtapaAnimal;

class PalpacionPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar palpaciones.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('palpacion.read');
    }

    /**
     * Determina si el usuario puede ver un registro de palpación.
     */
    public function read(User $user, Palpacion $palpacion): bool
    {
        if (!$user->hasPermissionTo('palpacion.read')) {
            return false;
        }

        $animal = optional($palpacion->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear una palpación para un animal.
     */
    public function create(User $user, int $animalId = null, int $animalEtapaId = null): bool
    {
        if (!$user->hasPermissionTo('palpacion.create')) {
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
     * Determina si el usuario puede actualizar una palpación.
     */
    public function update(User $user, Palpacion $palpacion): bool
    {
        if (!$user->hasPermissionTo('palpacion.update')) {
            return false;
        }

        $animal = optional($palpacion->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar una palpación.
     */
    public function delete(User $user, Palpacion $palpacion): bool
    {
        if (!$user->hasPermissionTo('palpacion.delete')) {
            return false;
        }

        $animal = optional($palpacion->etapaAnimal)->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

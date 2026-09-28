<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Diagnostico;
use App\Models\Animal;
use App\Models\EtapaAnimal;

class DiagnosticoPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar diagnósticos.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('diagnostico.read');
    }

    /**
     * Determina si el usuario puede ver un registro de diagnóstico.
     */
    public function read(User $user, Diagnostico $diagnostico): bool
    {
        if (!$user->hasPermissionTo('diagnostico.read')) {
            return false;
        }

        $animal = $diagnostico->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un diagnóstico para un animal.
     */
    public function create(User $user, int $animalId = null, int $animalEtapaId = null): bool
    {
        if (!$user->hasPermissionTo('diagnostico.create')) {
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
     * Determina si el usuario puede actualizar un diagnóstico.
     */
    public function update(User $user, Diagnostico $diagnostico): bool
    {
        if (!$user->hasPermissionTo('diagnostico.update')) {
            return false;
        }

        $animal = $diagnostico->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un diagnóstico.
     */
    public function delete(User $user, Diagnostico $diagnostico): bool
    {
        if (!$user->hasPermissionTo('diagnostico.delete')) {
            return false;
        }

        $animal = $diagnostico->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

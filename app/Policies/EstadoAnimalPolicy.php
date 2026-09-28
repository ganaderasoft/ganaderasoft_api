<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EstadoAnimal;
use App\Models\Animal;

class EstadoAnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar estados de los animales.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('estado_animal.read');
    }

    /**
     * Determina si el usuario puede ver un registro de estado animal.
     */
    public function read(User $user, EstadoAnimal $estadoAnimal): bool
    {
        if (!$user->hasPermissionTo('estado_animal.read')) {
            return false;
        }

        $animal = $estadoAnimal->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede asignar un estado a un animal específico.
     */
    public function create(User $user, int $animalId): bool
    {
        if (!$user->hasPermissionTo('estado_animal.create')) {
            return false;
        }

        $animal = Animal::with('rebano')->find($animalId);
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar un registro de estado animal.
     */
    public function update(User $user, EstadoAnimal $estadoAnimal): bool
    {
        if (!$user->hasPermissionTo('estado_animal.update')) {
            return false;
        }

        $animal = $estadoAnimal->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de estado animal.
     */
    public function delete(User $user, EstadoAnimal $estadoAnimal): bool
    {
        if (!$user->hasPermissionTo('estado_animal.delete')) {
            return false;
        }

        $animal = $estadoAnimal->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

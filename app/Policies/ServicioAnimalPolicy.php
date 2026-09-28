<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServicioAnimal;
use App\Models\Animal;

class ServicioAnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar los servicios.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('servicio_animal.read');
    }

    /**
     * Determina si el usuario puede ver un registro de servicio.
     */
    public function read(User $user, ServicioAnimal $servicioAnimal): bool
    {
        if (!$user->hasPermissionTo('servicio_animal.read')) {
            return false;
        }

        $animal = $servicioAnimal->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede crear un registro de servicio.
     */
    public function create(User $user, int $animalId = null): bool
    {
        if (!$user->hasPermissionTo('servicio_animal.create')) {
            return false;
        }

        if ($animalId) {
            $animal = Animal::with('rebano')->find($animalId);
            return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
        }

        return false;
    }

    /**
     * Determina si el usuario puede actualizar un registro de servicio.
     */
    public function update(User $user, ServicioAnimal $servicioAnimal): bool
    {
        if (!$user->hasPermissionTo('servicio_animal.update')) {
            return false;
        }

        $animal = $servicioAnimal->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede eliminar un registro de servicio.
     */
    public function delete(User $user, ServicioAnimal $servicioAnimal): bool
    {
        if (!$user->hasPermissionTo('servicio_animal.delete')) {
            return false;
        }

        $animal = $servicioAnimal->animal;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }
}

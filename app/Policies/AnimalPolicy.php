<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Animal;
use App\Models\Rebano;

class AnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier animal.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('animal.read');
    }

    /**
     * Determina si el usuario puede ver un animal en específico.
     */
    public function read(User $user, Animal $animal): bool
    {
        if (!$user->hasPermissionTo('animal.read')) {
            return false;
        }

        // El animal pertenece a un Rebaño que a su vez pertenece a una Finca
        return $this->checkFincaAccess($user, $animal->rebano->finca_id);
    }

    /**
     * Determina si el usuario puede crear un animal en un rebaño o finca determinada.
     */
    public function create(User $user, mixed $target = null): bool
    {
        if (!$user->hasPermissionTo('animal.create')) {
            return false;
        }

        if ($target instanceof \App\Models\Finca) {
            return $this->checkFincaAccess($user, $target->id);
        }

        if ($target instanceof Rebano) {
            return $this->checkFincaAccess($user, $target->finca_id);
        }

        if (is_numeric($target)) {
            $rebano = Rebano::find((int) $target);
            return $rebano ? $this->checkFincaAccess($user, $rebano->finca_id) : false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede actualizar el animal.
     */
    public function update(User $user, Animal $animal): bool
    {
        if (!$user->hasPermissionTo('animal.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $animal->rebano->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar definitivamente el animal.
     */
    public function delete(User $user, Animal $animal): bool
    {
        if (!$user->hasPermissionTo('animal.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $animal->rebano->finca_id);
    }
}

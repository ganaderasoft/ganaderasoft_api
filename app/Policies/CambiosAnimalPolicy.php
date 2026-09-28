<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CambiosAnimal;
use App\Models\Animal;

class CambiosAnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('cambios_animal.read');
    }

    /**
     * Determina si el usuario puede ver el modelo.
     */
    public function read(User $user, CambiosAnimal $model): bool
    {
        if (!$user->hasPermissionTo('cambios_animal.read')) return false;
        return $this->checkFincaAccess($user, optional(optional($model->animal)->rebano)->finca_id);
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user, ?\App\Models\Animal $animal = null): bool
    {
        if (!$user->hasPermissionTo('cambios_animal.create')) return false;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, CambiosAnimal $model): bool
    {
        if (!$user->hasPermissionTo('cambios_animal.update')) return false;
        return $this->checkFincaAccess($user, optional(optional($model->animal)->rebano)->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, CambiosAnimal $model): bool
    {
        if (!$user->hasPermissionTo('cambios_animal.delete')) return false;
        return $this->checkFincaAccess($user, optional(optional($model->animal)->rebano)->finca_id);
    }
}

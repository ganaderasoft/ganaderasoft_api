<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ArbolGen;

class ArbolGenPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('arbol_gen.read');
    }

    /**
     * Determina si el usuario puede ver el modelo.
     */
    public function read(User $user, ArbolGen $model): bool
    {
        if (!$user->hasPermissionTo('arbol_gen.read')) return false;
        return $this->checkFincaAccess($user, optional(optional($model->animal)->rebano)->finca_id);
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user, ?\App\Models\Animal $animal = null): bool
    {
        if (!$user->hasPermissionTo('arbol_gen.create')) return false;
        return $animal ? $this->checkFincaAccess($user, optional($animal->rebano)->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, ArbolGen $model): bool
    {
        if (!$user->hasPermissionTo('arbol_gen.update')) return false;
        return $this->checkFincaAccess($user, optional(optional($model->animal)->rebano)->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, ?ArbolGen $model = null, ?\App\Models\Animal $animal = null): bool
    {
        if (!$user->hasPermissionTo('arbol_gen.delete')) return false;
        
        if ($model) {
            return $this->checkFincaAccess($user, optional(optional($model->animal)->rebano)->finca_id);
        }
        
        if ($animal) {
            return $this->checkFincaAccess($user, optional($animal->rebano)->finca_id);
        }

        return false;
    }
}

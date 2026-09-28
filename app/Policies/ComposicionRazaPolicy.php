<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ComposicionRaza;

class ComposicionRazaPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('composicion_raza.read');
    }

    /**
     * Determina si el usuario puede ver el modelo.
     */
    public function read(User $user, ComposicionRaza $model): bool
    {
        if (!$user->hasPermissionTo('composicion_raza.read')) return false;
        if (!$model->finca_id) return true; // Es global
        return $this->checkFincaAccess($user, $model->finca_id);
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user, ?int $fincaId = null): bool
    {
        if (!$user->hasPermissionTo('composicion_raza.create')) return false;
        
        if ($fincaId) {
            return $this->checkFincaAccess($user, $fincaId);
        }
        
        return true;
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, ComposicionRaza $model): bool
    {
        if (!$user->hasPermissionTo('composicion_raza.update')) return false;
        
        if ($model->finca_id) {
            return $this->checkFincaAccess($user, $model->finca_id);
        }
        
        return false;
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, ComposicionRaza $model): bool
    {
        if (!$user->hasPermissionTo('composicion_raza.delete')) return false;
        
        if ($model->finca_id) {
            return $this->checkFincaAccess($user, $model->finca_id);
        }
        
        return false;
    }
}

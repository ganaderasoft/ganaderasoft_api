<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CasaComercial;

class CasaComercialPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar casas comerciales.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('casa_comercial.read');
    }

    /**
     * Determina si el usuario puede ver una casa comercial.
     */
    public function read(User $user, CasaComercial $casaComercial): bool
    {
        return $user->hasPermissionTo('casa_comercial.read');
    }

    /**
     * Determina si el usuario puede crear una casa comercial.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('casa_comercial.create');
    }

    /**
     * Determina si el usuario puede actualizar una casa comercial.
     */
    public function update(User $user, CasaComercial $casaComercial): bool
    {
        return $user->hasPermissionTo('casa_comercial.update');
    }

    /**
     * Determina si el usuario puede eliminar una casa comercial.
     */
    public function delete(User $user, CasaComercial $casaComercial): bool
    {
        return $user->hasPermissionTo('casa_comercial.delete');
    }
}

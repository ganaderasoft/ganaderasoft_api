<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DiaPalpacion;

class DiaPalpacionPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de día de palpación.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('dia_palpacion.read');
    }

    /**
     * Determina si el usuario puede ver un registro específico de día de palpación.
     */
    public function read(User $user, DiaPalpacion $model): bool
    {
        return $user->hasPermissionTo('dia_palpacion.read');
    }

    /**
     * Determina si el usuario puede crear registros de día de palpación.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('dia_palpacion.create');
    }

    /**
     * Determina si el usuario puede actualizar un registro de día de palpación.
     */
    public function update(User $user, DiaPalpacion $model): bool
    {
        return $user->hasPermissionTo('dia_palpacion.update');
    }

    /**
     * Determina si el usuario puede eliminar un registro de día de palpación.
     */
    public function delete(User $user, DiaPalpacion $model): bool
    {
        return $user->hasPermissionTo('dia_palpacion.delete');
    }
}

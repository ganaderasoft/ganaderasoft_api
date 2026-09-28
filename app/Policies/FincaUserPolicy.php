<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FincaUser;

class FincaUserPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo de FincaUser.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('finca_user.read');
    }

    /**
     * Determina si el usuario puede ver el modelo específico de FincaUser.
     */
    public function read(User $user, FincaUser $model): bool
    {
        return $user->hasPermissionTo('finca_user.read') || $user->id === $model->user_id;
    }

    /**
     * Determina si el usuario puede crear modelos (asignar fincas).
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('finca_user.create');
    }

    /**
     * Determina si el usuario puede actualizar el modelo (editar acceso a fincas).
     */
    public function update(User $user, FincaUser $model): bool
    {
        return $user->hasPermissionTo('finca_user.update');
    }

    /**
     * Determina si el usuario puede eliminar el modelo (desvincular fincas).
     */
    public function delete(User $user, FincaUser $model): bool
    {
        return $user->hasPermissionTo('finca_user.delete');
    }
}

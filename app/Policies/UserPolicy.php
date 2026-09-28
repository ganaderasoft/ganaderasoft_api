<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier modelo.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('usuario.read');
    }

    /**
     * Determina si el usuario puede ver el modelo específico.
     */
    public function read(User $user, User $model): bool
    {
        return $user->hasPermissionTo('usuario.read') || $user->id === $model->id;
    }

    /**
     * Determina si el usuario puede crear modelos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('usuario.create');
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('usuario.update') || $user->id === $model->id;
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('usuario.delete');
    }

    /**
     * Determina si el usuario puede restaurar el modelo.
     */
    public function restore(User $user, User $model): bool
    {
        //
    }

    /**
     * Determina si el usuario puede eliminar permanentemente el modelo.
     */
    public function forceDelete(User $user, User $model): bool
    {
        //
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Propietario;

class PropietarioPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver la lista de propietarios.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('propietario.read') || $user->propietario !== null;
    }

    /**
     * Determina si el usuario puede ver la lista COMPLETA de propietarios.
     */
    public function viewAll(User $user): bool
    {
        return $user->hasPermissionTo('propietario.read');
    }

    /**
     * Determina si el usuario puede ver un propietario específico.
     */
    public function read(User $user, Propietario $propietario): bool
    {
        $propietarioUser = $propietario->persona->users->first();
        return $propietarioUser && $user->id === $propietarioUser->id;
    }

    /**
     * Determina si el usuario puede crear un propietario.
     */
    public function create(User $user, int $targetUserId): bool
    {
        // Solo un usuario puede crear su propio perfil (si no es admin)
        return $user->id === $targetUserId;
    }

    /**
     * Determina si el usuario puede actualizar el propietario.
     */
    public function update(User $user, Propietario $propietario): bool
    {
        $propietarioUser = $propietario->persona->users->first();
        return $propietarioUser && $user->id === $propietarioUser->id;
    }

    /**
     * Determina si el usuario puede eliminar/archivar el propietario.
     */
    public function delete(User $user, Propietario $propietario): bool
    {
        // Solo los administradores pueden eliminar propietarios.
        // Como el Admin es aprobado en el before(), aquí retornamos false para los demás.
        return false;
    }
}

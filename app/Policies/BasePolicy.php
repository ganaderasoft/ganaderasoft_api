<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Interceptar comprobaciones para conceder acceso total a Administradores.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null; // Deja que los demás métodos hagan su trabajo si no es admin
    }

    /**
     * Valida si el usuario tiene acceso a la finca especificada.
     * 
     * @param User $user
     * @param int|null $fincaId
     * @return bool
     */
    protected function checkFincaAccess(User $user, ?int $fincaId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$fincaId) {
            return false;
        }

        return in_array($fincaId, $user->getAllowedFincasIds());
    }

    /**
     * Valida si el usuario tiene acceso a uno o varios animales especificados (según sus fincas).
     * 
     * @param User $user
     * @param int|array|null $animalIds
     * @return bool
     */
    protected function checkAnimalsAccess(User $user, int|array|null $animalIds): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (empty($animalIds)) {
            return true;
        }

        $ids = is_array($animalIds) ? $animalIds : [$animalIds];
        $fincasPermitidas = $user->getAllowedFincasIds();

        return Animal::whereIn('id', $ids)
            ->whereHas('rebano', fn($q) => $q->whereIn('finca_id', $fincasPermitidas))
            ->count() === count($ids);
    }
}

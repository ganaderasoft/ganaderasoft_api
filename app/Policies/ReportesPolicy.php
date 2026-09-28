<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Rebano;
use App\Models\User;

class ReportesPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver los reportes del sistema en general.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('reportes.read');
    }

    /**
     * Determina si el usuario puede ver reportes para un recurso específico (Finca, Rebaño, Animal o Finca ID).
     *
     * @param User $user
     * @param mixed $target Puede ser una Finca, Rebano, Animal, int (finca_id) o null.
     * @return bool
     */
    public function read(User $user, mixed $target = null): bool
    {
        if (!$user->hasPermissionTo('reportes.read')) {
            return false;
        }

        if ($target instanceof Finca) {
            return $this->checkFincaAccess($user, $target->id);
        }

        if ($target instanceof Rebano) {
            return $this->checkFincaAccess($user, $target->finca_id);
        }

        if ($target instanceof Animal) {
            return $this->checkFincaAccess($user, optional($target->rebano)->finca_id);
        }

        if (is_numeric($target)) {
            return $this->checkFincaAccess($user, (int) $target);
        }

        return true;
    }
}

<?php

namespace App\Services;

abstract class BaseService
{
    /**
     * Aplica el filtro de fincas permitidas a una consulta Eloquent.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query La consulta a filtrar.
     * @param mixed $user El usuario que hace la petición.
     * @param string|null $relationPath La ruta de la relación para llegar a finca_id (ej: 'rebano'). Usa null si la tabla ya tiene la columna directa.
     * @param string $columnName El nombre de la columna si se usa relación null.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFincaFilter($query, $user, ?string $relationPath = 'finca', string $columnName = 'finca_id')
    {
        if (!$user->isAdmin()) {
            $fincasPermitidas = $user->getAllowedFincasIds();
            
            if ($relationPath === null) {
                // La tabla tiene la columna directamente (finca_id o id)
                $query->whereIn($columnName, $fincasPermitidas);
            } else {
                // La tabla llega a finca_id a través de relaciones
                $query->whereHas($relationPath, function ($q) use ($fincasPermitidas) {
                    $q->whereIn('finca_id', $fincasPermitidas);
                });
            }
        }
        
        return $query;
    }

    /**
     * Valida si el usuario tiene acceso a la finca especificada.
     * 
     * @param mixed $user
     * @param int|null $fincaId
     * @return bool
     */
    protected function checkFincaAccess($user, ?int $fincaId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$fincaId) {
            return false;
        }

        return in_array($fincaId, $user->getAllowedFincasIds());
    }
}

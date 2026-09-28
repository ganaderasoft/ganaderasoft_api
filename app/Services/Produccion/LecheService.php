<?php

namespace App\Services\Produccion;

use App\Models\Leche;
use App\Models\Lactancia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use App\Services\BaseService;

class LecheService extends BaseService
{
    /**
     * Obtener registros de leche paginados con filtros y autorización aplicada.
     */
    public function getPaginatedLeche(array $filters, $user, $perPage = 15)
    {
        if ($user->cannot('readAny', Leche::class)) {
            throw new AuthorizationException('Sin permisos para listar registros de leche.');
        }

        $query = Leche::with(['lactancia.animal', 'lactancia.etapa']);

        if (isset($filters['lactancia_id'])) {
            $query->forLactancia($filters['lactancia_id']);
        }

        if (isset($filters['fecha_inicio'])) {
            $endDate = $filters['fecha_fin'] ?? null;
            $query->byDateRange($filters['fecha_inicio'], $endDate);
        }

        if (isset($filters['produccion_minima'])) {
            $query->minProduction($filters['produccion_minima']);
        }

        $this->applyFincaFilter($query, $user, 'lactancia.animal.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crear un nuevo registro de Leche validando permisos.
     */
    public function createLeche(array $data, $user)
    {
        $lactanciaId = (int) $data['lactancia_id'];

        if ($user->cannot('create', [Leche::class, $lactanciaId])) {
            throw new AuthorizationException('No tiene permisos para registrar leche a esta lactancia');
        }

        $leche = Leche::create([
            'lactancia_id' => $data['lactancia_id'],
            'fecha_pesaje' => $data['fecha_pesaje'],
            'pesaje_total' => $data['pesaje_total'],
        ]);

        return $leche->load(['lactancia.animal', 'lactancia.etapa']);
    }

    /**
     * Obtener un registro específico de Leche por ID validando permisos.
     */
    public function getLecheById($id, $user)
    {
        $leche = Leche::with(['lactancia.animal', 'lactancia.etapa'])->findOrFail($id);

        if ($user->cannot('read', $leche)) {
            throw new AuthorizationException('No tiene permisos para ver este registro de leche.');
        }

        return $leche;
    }

    /**
     * Actualizar un registro existente de Leche.
     */
    public function updateLeche($id, array $data, $user)
    {
        $leche = Leche::findOrFail($id);

        if ($user->cannot('update', $leche)) {
            throw new AuthorizationException('No tiene permisos para editar este registro de leche.');
        }

        $leche->update($data);

        return $leche->load(['lactancia.animal', 'lactancia.etapa']);
    }

    /**
     * Eliminar un registro de Leche.
     */
    public function deleteLeche($id, $user)
    {
        $leche = Leche::findOrFail($id);

        if ($user->cannot('delete', $leche)) {
            throw new AuthorizationException('No tiene permisos para eliminar este registro de leche.');
        }

        return $leche->delete();
    }
}

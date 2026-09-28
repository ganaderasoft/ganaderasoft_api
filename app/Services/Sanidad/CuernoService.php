<?php

namespace App\Services\Sanidad;

use App\Models\Cuerno;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

class CuernoService extends BaseService
{
    /**
     * Obtiene una lista paginada o completa de cuernos uterinos.
     */
    public function listCuernos(array $filters, $user, $perPage = 15)
    {
        if ($user->cannot('readAny', Cuerno::class)) {
            throw new AuthorizationException('Sin permisos para consultar cuernos uterinos.');
        }

        $query = Cuerno::with('palpacion');

        if (isset($filters['palpacion_id'])) {
            $query->where('palpacion_id', $filters['palpacion_id']);
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtiene un registro de cuerno uterino por su ID.
     */
    public function getCuernoById(int $id, $user): Cuerno
    {
        $cuerno = Cuerno::with('palpacion')->findOrFail($id);

        if ($user->cannot('read', $cuerno)) {
            throw new AuthorizationException('Sin permisos para ver este registro.');
        }

        return $cuerno;
    }

    /**
     * Crea un nuevo registro de cuerno uterino.
     */
    public function createCuerno(array $data, $user): Cuerno
    {
        if ($user->cannot('create', Cuerno::class)) {
            throw new AuthorizationException('Sin permisos para registrar cuernos uterinos.');
        }

        return Cuerno::create($data);
    }

    /**
     * Actualiza un registro de cuerno uterino existente.
     */
    public function updateCuerno(int $id, array $data, $user): Cuerno
    {
        $cuerno = Cuerno::findOrFail($id);

        if ($user->cannot('update', $cuerno)) {
            throw new AuthorizationException('Sin permisos para actualizar este registro.');
        }

        $cuerno->update($data);
        return $cuerno;
    }

    /**
     * Elimina un registro de cuerno uterino.
     */
    public function deleteCuerno(int $id, $user): bool
    {
        $cuerno = Cuerno::findOrFail($id);

        if ($user->cannot('delete', $cuerno)) {
            throw new AuthorizationException('Sin permisos para eliminar este registro.');
        }

        return (bool) $cuerno->delete();
    }
}

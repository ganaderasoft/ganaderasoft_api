<?php

namespace App\Services\Sanidad;

use App\Models\DiaPalpacion;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

class DiaPalpacionService extends BaseService
{
    /**
     * Obtiene una lista paginada o completa de días de palpación.
     */
    public function listDias(array $filters, $user, $perPage = 15)
    {
        if ($user->cannot('readAny', DiaPalpacion::class)) {
            throw new AuthorizationException('Sin permisos para consultar días de palpación.');
        }

        $query = DiaPalpacion::query();

        if (isset($filters['search'])) {
            $query->where('dias', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtiene un día de palpación por su ID.
     */
    public function getDiaById(int $id, $user): DiaPalpacion
    {
        $dia = DiaPalpacion::findOrFail($id);

        if ($user->cannot('read', $dia)) {
            throw new AuthorizationException('Sin permisos para ver este registro.');
        }

        return $dia;
    }

    /**
     * Crea un nuevo registro de día de palpación.
     */
    public function createDia(array $data, $user): DiaPalpacion
    {
        if ($user->cannot('create', DiaPalpacion::class)) {
            throw new AuthorizationException('Sin permisos para crear días de palpación.');
        }

        return DiaPalpacion::create($data);
    }

    /**
     * Actualiza un día de palpación existente.
     */
    public function updateDia(int $id, array $data, $user): DiaPalpacion
    {
        $dia = DiaPalpacion::findOrFail($id);

        if ($user->cannot('update', $dia)) {
            throw new AuthorizationException('Sin permisos para actualizar este registro.');
        }

        $dia->update($data);
        return $dia;
    }

    /**
     * Elimina un día de palpación.
     */
    public function deleteDia(int $id, $user): bool
    {
        $dia = DiaPalpacion::findOrFail($id);

        if ($user->cannot('delete', $dia)) {
            throw new AuthorizationException('Sin permisos para eliminar este registro.');
        }

        return (bool) $dia->delete();
    }
}

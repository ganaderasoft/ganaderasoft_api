<?php

namespace App\Services\Sanidad;

use App\Models\Ovario;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

class OvarioService extends BaseService
{
    /**
     * Obtiene una lista paginada o completa de ovarios.
     */
    public function listOvarios(array $filters, $user, $perPage = 15)
    {
        if ($user->cannot('readAny', Ovario::class)) {
            throw new AuthorizationException('Sin permisos para consultar ovarios.');
        }

        $query = Ovario::with(['foliculos', 'palpacion']);

        if (isset($filters['palpacion_id'])) {
            $query->where('palpacion_id', $filters['palpacion_id']);
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtiene un registro de ovario por su ID.
     */
    public function getOvarioById(int $id, $user): Ovario
    {
        $ovario = Ovario::with(['foliculos', 'palpacion'])->findOrFail($id);

        if ($user->cannot('read', $ovario)) {
            throw new AuthorizationException('Sin permisos para ver este registro.');
        }

        return $ovario;
    }

    /**
     * Crea un nuevo registro de ovario.
     */
    public function createOvario(array $data, $user): Ovario
    {
        if ($user->cannot('create', Ovario::class)) {
            throw new AuthorizationException('Sin permisos para registrar ovarios.');
        }

        $ovario = Ovario::create($data);

        if (!empty($data['foliculo_ids']) && is_array($data['foliculo_ids'])) {
            $ovario->foliculos()->sync($data['foliculo_ids']);
        }

        return $ovario->load('foliculos');
    }

    /**
     * Actualiza un registro de ovario existente.
     */
    public function updateOvario(int $id, array $data, $user): Ovario
    {
        $ovario = Ovario::findOrFail($id);

        if ($user->cannot('update', $ovario)) {
            throw new AuthorizationException('Sin permisos para actualizar este registro.');
        }

        $ovario->update($data);

        if (isset($data['foliculo_ids']) && is_array($data['foliculo_ids'])) {
            $ovario->foliculos()->sync($data['foliculo_ids']);
        }

        return $ovario->load('foliculos');
    }

    /**
     * Elimina un registro de ovario.
     */
    public function deleteOvario(int $id, $user): bool
    {
        $ovario = Ovario::findOrFail($id);

        if ($user->cannot('delete', $ovario)) {
            throw new AuthorizationException('Sin permisos para eliminar este registro.');
        }

        return (bool) $ovario->delete();
    }
}

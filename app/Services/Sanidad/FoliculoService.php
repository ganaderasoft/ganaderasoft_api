<?php

namespace App\Services\Sanidad;

use App\Models\Foliculo;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

class FoliculoService extends BaseService
{
    /**
     * Obtiene una lista paginada o completa de folículos.
     */
    public function listFoliculos(array $filters, $user, $perPage = 15)
    {
        if ($user->cannot('readAny', Foliculo::class)) {
            throw new AuthorizationException('Sin permisos para consultar folículos.');
        }

        $query = Foliculo::query();

        if (isset($filters['search'])) {
            $query->where('nombre', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('siglas', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtiene un folículo por su ID.
     */
    public function getFoliculoById(int $id, $user): Foliculo
    {
        $foliculo = Foliculo::findOrFail($id);

        if ($user->cannot('read', $foliculo)) {
            throw new AuthorizationException('Sin permisos para ver este registro.');
        }

        return $foliculo;
    }

    /**
     * Crea un nuevo registro de folículo.
     */
    public function createFoliculo(array $data, $user): Foliculo
    {
        if ($user->cannot('create', Foliculo::class)) {
            throw new AuthorizationException('Sin permisos para crear folículos.');
        }

        return Foliculo::create($data);
    }

    /**
     * Actualiza un folículo existente.
     */
    public function updateFoliculo(int $id, array $data, $user): Foliculo
    {
        $foliculo = Foliculo::findOrFail($id);

        if ($user->cannot('update', $foliculo)) {
            throw new AuthorizationException('Sin permisos para actualizar este registro.');
        }

        $foliculo->update($data);
        return $foliculo;
    }

    /**
     * Elimina un folículo.
     */
    public function deleteFoliculo(int $id, $user): bool
    {
        $foliculo = Foliculo::findOrFail($id);

        if ($user->cannot('delete', $foliculo)) {
            throw new AuthorizationException('Sin permisos para eliminar este registro.');
        }

        return (bool) $foliculo->delete();
    }
}

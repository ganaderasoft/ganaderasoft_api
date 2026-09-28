<?php

namespace App\Services\Animal;

use App\Models\TipoAnimal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\User;
use App\Services\BaseService;

class TipoAnimalService extends BaseService
{
    /**
     * Obtiene el listado de tipos de animal.
     *
     * @param array $filters Filtros.
     * @return LengthAwarePaginator
     */
    public function listTipos(array $filters, User $user)
    {
        if ($user->cannot('readAny', TipoAnimal::class)) {
            throw new AuthorizationException('No tiene permisos para ver tipos de animal.');
        }

        $query = TipoAnimal::query();

        if (!empty($filters['search'])) {
            $query->byName($filters['search']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo tipo de animal (solo administradores).
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return TipoAnimal
     * @throws AuthorizationException
     */
    public function createTipo(array $data, $user): TipoAnimal
    {
        if ($user->cannot('create', TipoAnimal::class)) {
            throw new AuthorizationException('No tiene permisos para crear tipos de animal.');
        }

        return TipoAnimal::create([
            'nombre' => $data['nombre']
        ]);
    }

    /**
     * Obtiene un tipo de animal por su ID.
     *
     * @param int $id ID.
     * @return TipoAnimal
     * @throws ModelNotFoundException
     */
    public function getTipoById(int $id, User $user): TipoAnimal
    {
        $tipoAnimal = TipoAnimal::findOrFail($id);

        if ($user->cannot('read', $tipoAnimal)) {
            throw new AuthorizationException('No tiene permisos para ver tipos de animal.');
        }

        return $tipoAnimal;
    }

    /**
     * Actualiza un tipo de animal (solo administradores).
     *
     * @param int $id ID.
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return TipoAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateTipo(int $id, array $data, $user): TipoAnimal
    {
        $tipoAnimal = TipoAnimal::findOrFail($id);

        if ($user->cannot('update', $tipoAnimal)) {
            throw new AuthorizationException('No tiene permisos para actualizar tipos de animal.');
        }

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];

        $tipoAnimal->update($payload);

        return $tipoAnimal;
    }

    /**
     * Elimina un tipo de animal (solo administradores).
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteTipo(int $id, $user): bool
    {
        $tipoAnimal = TipoAnimal::findOrFail($id);

        if ($user->cannot('delete', $tipoAnimal)) {
            throw new AuthorizationException('No tiene permisos para eliminar tipos de animal.');
        }

        return $tipoAnimal->delete();
    }
}

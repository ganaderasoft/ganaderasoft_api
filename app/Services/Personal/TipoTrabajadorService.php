<?php

namespace App\Services\Personal;

use App\Models\TipoTrabajador;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TipoTrabajadorService extends BaseService
{
    /**
     * Obtiene el listado de tipos de trabajador.
     *
     * @param array $filters Filtros.
     * @param User $user
     * @return mixed
     * @throws AuthorizationException
     */
    public function listTipos(array $filters, User $user)
    {
        if ($user->cannot('readAny', TipoTrabajador::class)) {
            throw new AuthorizationException('No tiene permisos para ver tipos de trabajador.');
        }

        $query = TipoTrabajador::query();

        if (!empty($filters['search'])) {
            $query->byName($filters['search']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo tipo de trabajador (solo administradores).
     *
     * @param array $data Datos.
     * @param User $user Usuario.
     * @return TipoTrabajador
     * @throws AuthorizationException
     */
    public function createTipo(array $data, User $user): TipoTrabajador
    {
        if ($user->cannot('create', TipoTrabajador::class)) {
            throw new AuthorizationException('No tiene permisos para crear tipos de trabajador.');
        }

        return TipoTrabajador::create([
            'nombre' => $data['nombre']
        ]);
    }

    /**
     * Obtiene un tipo de trabajador por su ID.
     *
     * @param int $id ID.
     * @param User $user
     * @return TipoTrabajador
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getTipoById(int $id, User $user): TipoTrabajador
    {
        $tipoTrabajador = TipoTrabajador::findOrFail($id);

        if ($user->cannot('read', $tipoTrabajador)) {
            throw new AuthorizationException('No tiene permisos para ver tipos de trabajador.');
        }

        return $tipoTrabajador;
    }

    /**
     * Actualiza un tipo de trabajador (solo administradores).
     *
     * @param int $id ID.
     * @param array $data Datos.
     * @param User $user Usuario.
     * @return TipoTrabajador
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateTipo(int $id, array $data, User $user): TipoTrabajador
    {
        $tipoTrabajador = TipoTrabajador::findOrFail($id);

        if ($user->cannot('update', $tipoTrabajador)) {
            throw new AuthorizationException('No tiene permisos para actualizar tipos de trabajador.');
        }

        $payload = [];
        if (array_key_exists('nombre', $data)) {
            $payload['nombre'] = $data['nombre'];
        }

        $tipoTrabajador->update($payload);

        return $tipoTrabajador;
    }

    /**
     * Elimina un tipo de trabajador (solo administradores).
     *
     * @param int $id ID.
     * @param User $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws Exception
     */
    public function deleteTipo(int $id, User $user): bool
    {
        $tipoTrabajador = TipoTrabajador::findOrFail($id);

        if ($user->cannot('delete', $tipoTrabajador)) {
            throw new AuthorizationException('No tiene permisos para eliminar tipos de trabajador.');
        }

        if ($tipoTrabajador->personalFincas()->exists()) {
            throw new Exception('No se puede eliminar el tipo de trabajador porque tiene personal asignado.');
        }

        return $tipoTrabajador->delete();
    }
}

<?php

namespace App\Services\Sanidad;

use App\Models\EstadoSalud;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use App\Services\BaseService;

class EstadoSaludService extends BaseService
{
    /**
     * Obtiene la lista de estados de salud con paginación.
     *
     * @param array $filters Filtros.
     * @return \Illuminate\Support\Collection|LengthAwarePaginator
     */
    public function listEstados(array $filters, $user)
    {

        if ($user->cannot('readAny', EstadoSalud::class)) {
            throw new AuthorizationException('Sin permisos para listar estados de salud.');
        }

        $query = EstadoSalud::query();

        if (!empty($filters['search'])) {
            $query->byName($filters['search']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo estado de salud.
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return EstadoSalud
     * @throws AuthorizationException
     */
    public function createEstado(array $data, $user): EstadoSalud
    {

        if ($user->cannot('create', EstadoSalud::class)) {
            throw new AuthorizationException('No tiene permisos para crear estados de salud.');
        }

        return EstadoSalud::create([
            'nombre' => $data['nombre']
        ]);
    }

    /**
     * Obtiene un estado de salud por su ID.
     *
     * @param int $id ID del estado.
     * @return EstadoSalud
     * @throws ModelNotFoundException
     */
    public function getEstadoById(int $id, $user): EstadoSalud
    {
        $estadoSalud = EstadoSalud::with(['estadosAnimal.animal'])->findOrFail($id);

        if ($user->cannot('read', $estadoSalud)) {
            throw new AuthorizationException('No tiene permisos para ver este estado de salud.');
        }

        return $estadoSalud;
    }

    /**
     * Actualiza los datos de un estado de salud.
     *
     * @param int $id ID.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return EstadoSalud
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateEstado(int $id, array $data, $user): EstadoSalud
    {
        $estadoSalud = EstadoSalud::findOrFail($id);

        if ($user->cannot('update', $estadoSalud)) {
            throw new AuthorizationException('No tiene permisos para actualizar estados de salud.');
        }

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];

        $estadoSalud->update($payload);

        return $estadoSalud;
    }

    /**
     * Elimina un estado de salud.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function deleteEstado(int $id, $user): bool
    {
        $estadoSalud = EstadoSalud::findOrFail($id);

        if ($user->cannot('delete', $estadoSalud)) {
            throw new AuthorizationException('No tiene permisos para eliminar estados de salud.');
        }

        if ($estadoSalud->estadosAnimal()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar el estado de salud porque está siendo utilizado.');
        }

        return $estadoSalud->delete();
    }
}

<?php

namespace App\Services\Sanidad;

use App\Models\EstadoAnimal;
use App\Models\Animal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Services\BaseService;

class EstadoAnimalService extends BaseService
{
    /**
     * Obtiene el listado de asignaciones de estado de salud con filtros.
     *
     * @param array $filters Filtros.
     * @param mixed $user Usuario.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listEstados(array $filters, $user)
    {

        if ($user->cannot('readAny', EstadoAnimal::class)) {
            throw new AuthorizationException('Sin permisos para listar estados de los animales.');
        }

        $query = EstadoAnimal::with(['animal.rebano.finca', 'estadoSalud']);

        if (!empty($filters['animal_id'])) {
            $query->where('animal_id', $filters['animal_id']);
        }

        if (!empty($filters['estado_id'])) {
            $query->where('estado_salud_id', $filters['estado_id']);
        }

        if (isset($filters['active']) && $filters['active'] === 'true') {
            $query->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now()->toDateString());
        }

        $this->applyFincaFilter($query, $user, 'animal.rebano');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo estado de salud para un animal.
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return EstadoAnimal
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function createEstado(array $data, $user): EstadoAnimal
    {
        $animalId = (int) $data['animal_id'];

        if ($user->cannot('create', [EstadoAnimal::class, $animalId])) {
            throw new AuthorizationException('No tiene permisos para modificar el estado de salud de este animal.');
        }

        $animal = Animal::findOrFail($animalId);

        // Regla biológica: Cerrar el estado activo anterior si el nuevo estado es activo (sin fecha_fin)
        if (empty($data['fecha_fin'])) {
            EstadoAnimal::where('animal_id', $animal->id)
                ->whereNull('fecha_fin')
                ->update(['fecha_fin' => now()->toDateString()]);
        }

        $estadoAnimal = EstadoAnimal::create([
            'fecha_ini'       => $data['fecha_ini'],
            'fecha_fin'       => $data['fecha_fin'] ?? null,
            'estado_salud_id' => $data['estado_salud_id'],
            'animal_id'       => $animal->id,
        ]);

        return $estadoAnimal->load(['estadoSalud', 'animal.rebano.finca']);
    }

    /**
     * Obtiene un registro histórico de estado por su ID.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return EstadoAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getEstadoById(int $id, $user): EstadoAnimal
    {
        $estadoAnimal = EstadoAnimal::with(['animal.rebano.finca', 'estadoSalud'])->findOrFail($id);

        if ($user->cannot('read', $estadoAnimal)) {
            throw new AuthorizationException('No tiene permisos para ver este estado de animal.');
        }

        return $estadoAnimal;
    }

    /**
     * Actualiza un registro histórico de estado de salud.
     *
     * @param int $id ID.
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return EstadoAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateEstado(int $id, array $data, $user): EstadoAnimal
    {
        $estadoAnimal = EstadoAnimal::findOrFail($id);

        if ($user->cannot('update', $estadoAnimal)) {
            throw new AuthorizationException('No tiene permisos para actualizar este estado de animal.');
        }

        // Si intenta cambiar de animal, verificar permisos sobre el nuevo
        if (!empty($data['animal_id']) && $data['animal_id'] != $estadoAnimal->animal_id) {
            if ($user->cannot('create', [EstadoAnimal::class, (int) $data['animal_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar estado a ese nuevo animal.');
            }
        }

        $payload = [];
        if (array_key_exists('fecha_ini', $data)) $payload['fecha_ini'] = $data['fecha_ini'];
        if (array_key_exists('fecha_fin', $data)) $payload['fecha_fin'] = $data['fecha_fin'];
        if (array_key_exists('estado_salud_id', $data)) $payload['estado_salud_id'] = $data['estado_salud_id'];
        if (array_key_exists('animal_id', $data)) $payload['animal_id'] = $data['animal_id'];

        $estadoAnimal->update($payload);

        return $estadoAnimal->load(['estadoSalud', 'animal.rebano.finca']);
    }

    /**
     * Elimina un registro del historial de estados de salud.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteEstado(int $id, $user): bool
    {
        $estadoAnimal = EstadoAnimal::findOrFail($id);

        if ($user->cannot('delete', $estadoAnimal)) {
            throw new AuthorizationException('No tiene permisos para eliminar este estado de animal.');
        }

        return $estadoAnimal->delete();
    }
}

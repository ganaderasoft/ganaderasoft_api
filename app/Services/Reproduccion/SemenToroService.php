<?php

namespace App\Services\Reproduccion;

use App\Models\SemenToro;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use App\Services\BaseService;

class SemenToroService extends BaseService
{
    /**
     * Obtiene una lista paginada de registros de semen basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedSemen(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', SemenToro::class)) {
            throw new AuthorizationException('Sin permisos para listar semen de toros.');
        }

        $query = SemenToro::with([
            'toro.rebano.finca',
            'toro.composicionRaza',
            'servicios.animal'
        ]);

        if (!empty($filters['toro_id'])) {
            $query->forToro($filters['toro_id']);
        } elseif (!empty($filters['animal_id'])) {
            $query->forToro($filters['animal_id']);
        }

        if (!empty($filters['finca_id'])) {
            $query->whereHas('toro.rebano', function($q) use ($filters) {
                $q->where('finca_id', $filters['finca_id']);
            });
        }

        if (!empty($filters['rebano_id'])) {
            $query->whereHas('toro', function($q) use ($filters) {
                $q->where('rebano_id', $filters['rebano_id']);
            });
        }

        if (isset($filters['activo']) && $filters['activo'] !== '' && $filters['activo'] !== null) {
            if ($filters['activo'] == '1' || $filters['activo'] === true || $filters['activo'] === 'true') {
                $query->activo();
            } else {
                $query->where('estado', false);
            }
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->where('fecha', '>=', $filters['fecha_inicio']);
        }
        if (!empty($filters['fecha_fin'])) {
            $query->where('fecha', '<=', $filters['fecha_fin']);
        }

        $this->applyFincaFilter($query, $user, 'toro.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de SemenToro.
     */
    public function createSemen(array $data, $user)
    {
        $animalId = $data['animal_id'] ?? null;

        if ($user->cannot('create', [SemenToro::class, $animalId])) {
            throw new AuthorizationException('No tiene permisos para registrar semen a este toro.');
        }

        if (isset($data['animal_id'])) {
            $this->validateToro($data['animal_id']);
        }

        $semen = SemenToro::create([
            'animal_id'         => $data['animal_id'],
            'estado'            => $data['estado'] ?? true,
            'fecha'             => $data['fecha'] ?? null,
            'cantidad_pajuelas' => isset($data['cantidad_pajuelas']) ? (int) $data['cantidad_pajuelas'] : 1,
        ]);

        return $semen->load([
            'toro.rebano.finca',
            'toro.composicionRaza',
            'servicios.animal'
        ]);
    }

    /**
     * Obtiene un registro de SemenToro específico por su ID.
     */
    public function getSemenById($id, $user)
    {
        $semen = SemenToro::with([
            'toro.rebano.finca',
            'toro.composicionRaza',
            'servicios.animal'
        ])->findOrFail($id);

        if ($user->cannot('read', $semen)) {
            throw new AuthorizationException('No tiene permisos para ver este registro de semen.');
        }

        return $semen;
    }

    /**
     * Actualiza un registro de SemenToro existente.
     */
    public function updateSemen($id, array $data, $user)
    {
        $semen = SemenToro::findOrFail($id);

        if ($user->cannot('update', $semen)) {
            throw new AuthorizationException('No tiene permisos para actualizar este registro de semen.');
        }

        if (isset($data['animal_id']) && $data['animal_id'] != $semen->animal_id) {
            if ($user->cannot('create', [SemenToro::class, (int) $data['animal_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar semen a ese nuevo toro.');
            }
            $this->validateToro($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_id', $data)) {
            $updatePayload['animal_id'] = $data['animal_id'];
        }
        if (array_key_exists('estado', $data)) {
            $updatePayload['estado'] = $data['estado'];
        }
        if (array_key_exists('fecha', $data)) {
            $updatePayload['fecha'] = $data['fecha'];
        }
        if (array_key_exists('cantidad_pajuelas', $data)) {
            $updatePayload['cantidad_pajuelas'] = $data['cantidad_pajuelas'] !== null ? (int) $data['cantidad_pajuelas'] : null;
        }

        $semen->update($updatePayload);

        return $semen->load([
            'toro.rebano.finca',
            'toro.composicionRaza',
            'servicios.animal'
        ]);
    }

    /**
     * Elimina un registro de SemenToro existente.
     */
    public function deleteSemen($id, $user)
    {
        $semen = SemenToro::findOrFail($id);

        if ($user->cannot('delete', $semen)) {
            throw new AuthorizationException('No tiene permisos para eliminar este registro de semen.');
        }

        return $semen->delete();
    }

    /**
     * Valida que el animal sea macho/toro antes de crear o actualizar.
     */
    private function validateToro($animalId)
    {
        $animal = Animal::find($animalId);

        if (!$animal) {
            throw new ModelNotFoundException("El toro especificado no existe.");
        }

        $sexo = strtoupper(trim((string)($animal->sexo ?? $animal->Sexo ?? '')));
        $isMale = in_array($sexo, ['M', 'MACHO', 'MASCULINO', '1'], true);

        if (!$isMale) {
            throw ValidationException::withMessages([
                'animal_id' => ['El animal especificado debe ser un macho para registrar semen.']
            ]);
        }
    }
}

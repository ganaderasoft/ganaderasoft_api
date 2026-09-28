<?php

namespace App\Services\Sanidad;

use App\Models\Animal;
use App\Models\Vacunacion;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacunacionService extends BaseService
{
    /**
     * Obtiene una lista de vacunaciones con filtros completos, paginación y aislamiento multi-finca.
     *
     * @param array $filters
     * @param mixed $user
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     * @throws AuthorizationException
     */
    public function getPaginatedVacunaciones(array $filters, $user, int $perPage = 15): LengthAwarePaginator|Collection
    {
        if ($user->cannot('readAny', Vacunacion::class)) {
            throw new AuthorizationException('No tienes permisos para listar vacunaciones.');
        }

        $query = Vacunacion::with(['animal.rebano.finca', 'vacuna', 'aplicador']);

        // Filtro por animal específico
        if (!empty($filters['animal_id'])) {
            $query->forAnimal((int) $filters['animal_id']);
        }

        // Filtro por vacuna
        if (!empty($filters['vacuna_id'])) {
            $query->forVacuna((int) $filters['vacuna_id']);
        }

        // Filtro por finca
        if (!empty($filters['finca_id'])) {
            $query->forFinca((int) $filters['finca_id']);
        }

        // Filtro por rebaño
        if (!empty($filters['rebano_id'])) {
            $query->forRebano((int) $filters['rebano_id']);
        }

        // Filtro por sexo del animal (M / H)
        if (!empty($filters['sexo'])) {
            $query->whereHas('animal', function ($q) use ($filters) {
                $q->where('sexo', $filters['sexo']);
            });
        }

        // Filtro por etapa productiva actual del animal
        if (!empty($filters['etapa_id'])) {
            $etapaId = (int) $filters['etapa_id'];
            $query->whereHas('animal.etapaAnimales', function ($q) use ($etapaId) {
                $q->where('etapa_id', $etapaId)
                  ->where(function ($sq) {
                      $sq->whereNull('fecha_fin')
                         ->orWhere('fecha_fin', '>', now()->toDateString());
                  });
            });
        }

        // Filtro de archivado:
        // - 'incluir_archivados' => true (activos + archivados, sin filtro)
        // - 'archivado' => true / 1 => solo de animales archivados
        // - por defecto => solo de animales activos
        $incluirArchivados = filter_var($filters['incluir_archivados'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$incluirArchivados) {
            $archivado = filter_var($filters['archivado'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('animal', fn($q) => $q->where('archivado', $archivado));
        }

        // Filtro por rango de fechas
        if (!empty($filters['fecha_inicio']) || !empty($filters['fecha_fin'])) {
            $query->betweenDates($filters['fecha_inicio'] ?? null, $filters['fecha_fin'] ?? null);
        }

        // Aislamiento multi-finca según los permisos del usuario
        $this->applyFincaFilter($query, $user, 'animal.rebano');

        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->orderByDesc('fecha')->orderByDesc('id')->get();
        }

        return $query->orderByDesc('fecha')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Registra una o múltiples vacunaciones dentro de una transacción atómica.
     *
     * @param array $data
     * @param mixed $user
     * @return array<Vacunacion>
     * @throws AuthorizationException|ValidationException
     */
    public function createVacunacion(array $data, $user): array
    {
        // Normalizar lista de IDs de animales
        $animalIds = !empty($data['animal_ids'])
            ? collect($data['animal_ids'])->map(fn($id) => (int)$id)->unique()->values()->all()
            : [(int) ($data['animal_id'] ?? 0)];

        if (empty($animalIds) || $animalIds[0] === 0) {
            throw ValidationException::withMessages([
                'animal_id' => ['Debe indicar al menos un animal a vacunar.']
            ]);
        }

        // Delegar autorización completa a la Policy (permisos + fincas + bypass de admin)
        if ($user->cannot('create', [Vacunacion::class, $animalIds])) {
            throw new AuthorizationException('No tienes permisos para registrar vacunaciones en los animales o fincas seleccionadas.');
        }

        // Validar que los animales a vacunar estén activos
        $inactivosCount = Animal::whereIn('id', $animalIds)->where('archivado', true)->count();
        if ($inactivosCount > 0) {
            throw ValidationException::withMessages([
                'animal_ids' => ['No es posible registrar vacunaciones en animales inactivos, vendidos o fallecidos.']
            ]);
        }

        $vacunaId    = (int) $data['vacuna_id'];
        $personaId   = !empty($data['persona_id']) 
            ? (int) $data['persona_id'] 
            : ($user->personas()->first()?->id ?? null);
        $fecha       = $data['fecha'];
        $dosis       = isset($data['dosis']) ? (float) $data['dosis'] : null;
        $costo       = (float) ($data['costo'] ?? 0);
        $lote        = $data['lote'] ?? null;
        $observacion = $data['observacion'] ?? null;

        return DB::transaction(function () use ($animalIds, $vacunaId, $personaId, $fecha, $dosis, $costo, $lote, $observacion) {
            $records = [];
            foreach ($animalIds as $animalId) {
                $vacunacion = Vacunacion::updateOrCreate(
                    [
                        'animal_id' => $animalId,
                        'vacuna_id' => $vacunaId,
                        'fecha'     => $fecha,
                    ],
                    [
                        'persona_id'  => $personaId,
                        'dosis'       => $dosis,
                        'costo'       => $costo,
                        'lote'        => $lote,
                        'observacion' => $observacion,
                    ]
                );
                $records[] = $vacunacion->load(['animal.rebano.finca', 'vacuna', 'aplicador']);
            }
            return $records;
        });
    }

    /**
     * Obtiene una vacunación por su ID.
     *
     * @param int $id
     * @param mixed $user
     * @return Vacunacion
     * @throws AuthorizationException
     */
    public function getVacunacionById(int $id, $user): Vacunacion
    {
        $vacunacion = Vacunacion::with(['animal.rebano.finca', 'vacuna', 'aplicador'])->findOrFail($id);

        if ($user->cannot('read', $vacunacion)) {
            throw new AuthorizationException('No tienes permisos para ver esta vacunación.');
        }

        return $vacunacion;
    }

    /**
     * Actualiza un registro de vacunación existente.
     *
     * @param int $id
     * @param array $data
     * @param mixed $user
     * @return Vacunacion
     * @throws AuthorizationException
     */
    public function updateVacunacion(int $id, array $data, $user): Vacunacion
    {
        $vacunacion = Vacunacion::findOrFail($id);

        if ($user->cannot('update', $vacunacion)) {
            throw new AuthorizationException('No tienes permisos para actualizar esta vacunación.');
        }

        $vacunacion->update($data);
        return $vacunacion->load(['animal.rebano.finca', 'vacuna', 'aplicador']);
    }

    /**
     * Elimina un registro de vacunación.
     *
     * @param int $id
     * @param mixed $user
     * @return bool
     * @throws AuthorizationException
     */
    public function deleteVacunacion(int $id, $user): bool
    {
        $vacunacion = Vacunacion::findOrFail($id);

        if ($user->cannot('delete', $vacunacion)) {
            throw new AuthorizationException('No tienes permisos para eliminar esta vacunación.');
        }

        return (bool) $vacunacion->delete();
    }
}

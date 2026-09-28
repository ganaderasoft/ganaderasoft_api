<?php

namespace App\Services\Animal;

use App\Models\CambiosAnimal;
use App\Models\Animal;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Services\BaseService;

class CambiosAnimalService extends BaseService
{
    /**
     * Obtiene el listado de cambios de animal aplicando filtros y validando permisos.
     *
     * @param array $filters Filtros aplicados.
     * @param mixed $user Usuario que realiza la consulta.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listCambios(array $filters, $user)
    {
        if ($user->cannot('readAny', CambiosAnimal::class)) {
            throw new AuthorizationException('No tiene permisos para listar cambios de animal.');
        }

        $query = CambiosAnimal::query();

        // Aplicar filtros V2
        if (!empty($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function ($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }

        if (!empty($filters['etapa_id'])) {
            $query->whereHas('etapaAnimal', function ($q) use ($filters) {
                $q->where('etapa_id', $filters['etapa_id']);
            });
        }

        if (!empty($filters['etapa_cambio'])) {
            $query->where('etapa_cambio', $filters['etapa_cambio']);
        }

        if (!empty($filters['fecha_inicio'])) {
            $endDate = $filters['fecha_fin'] ?? null;
            $query->byDateRange($filters['fecha_inicio'], $endDate);
        }

        $this->applyFincaFilter($query, $user, 'animal.rebano');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo log de cambio de animal.
     *
     * @param array $data Datos del cambio.
     * @param mixed $user Usuario que realiza la acción.
     * @return CambiosAnimal
     * @throws AuthorizationException
     */
    public function createCambio(array $data, $user): CambiosAnimal
    {
        // Obtener el animal a través de la etapa seleccionada para verificar permisos
        $animal = Animal::with('composicionRaza')->whereHas('etapaAnimales', function ($q) use ($data) {
            $q->where('id', $data['animal_etapa_id']);
        })->firstOrFail();

        if ($user->cannot('create', [CambiosAnimal::class, $animal])) {
            throw new AuthorizationException('No tiene permisos para registrar cambios a este animal.');
        }

        $animalEtapaId = $data['animal_etapa_id'];
        $fechaCambio = $data['fecha_cambio'] ?? now()->toDateString();

        // Si se especificó una nueva etapa_cambio, sincronizar la etapa activa del animal
        if (!empty($data['etapa_cambio'])) {
            $nombreEtapa = strtolower(trim($data['etapa_cambio']));
            $targetEtapa = Etapa::whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreEtapa])
                ->when($animal->composicionRaza?->tipo_animal_id, fn($q, $tid) => $q->where('tipo_animal_id', $tid))
                ->when($animal->sexo, fn($q, $sex) => $q->where(fn($sq) => $sq->where('sexo', $sex)->orWhereNull('sexo')))
                ->first();

            if (!$targetEtapa) {
                $targetEtapa = Etapa::whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreEtapa])->first();
            }

            if ($targetEtapa) {
                // Cerrar etapas activas previas
                EtapaAnimal::where('animal_id', $animal->id)
                    ->where(function ($q) use ($fechaCambio) {
                        $q->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>', $fechaCambio);
                    })
                    ->update(['fecha_fin' => $fechaCambio]);

                // Activar o crear registro de la nueva etapa
                $etapaAnimal = EtapaAnimal::where('animal_id', $animal->id)
                    ->where('etapa_id', $targetEtapa->id)
                    ->where('fecha_ini', $fechaCambio)
                    ->first();

                if (!$etapaAnimal) {
                    $etapaAnimal = EtapaAnimal::create([
                        'animal_id' => $animal->id,
                        'etapa_id'  => $targetEtapa->id,
                        'fecha_ini' => $fechaCambio,
                        'fecha_fin' => null,
                    ]);
                } else {
                    $etapaAnimal->update(['fecha_fin' => null]);
                }

                $animalEtapaId = $etapaAnimal->id;
            }
        }

        return CambiosAnimal::create([
            'animal_etapa_id' => $animalEtapaId,
            'fecha_cambio'    => $fechaCambio,
            'etapa_cambio'    => $data['etapa_cambio'] ?? null,
            'peso'            => $data['peso'],
            'altura'          => $data['altura'],
            'comentario'      => $data['comentario'] ?? null,
        ]);
    }

    /**
     * Actualiza un log de cambio existente.
     *
     * @param int $id ID del registro de CambiosAnimal.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario que realiza la acción.
     * @return CambiosAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateCambio(int $id, array $data, $user): CambiosAnimal
    {
        $cambio = CambiosAnimal::findOrFail($id);
        $animal = $cambio->animal;

        if ($user->cannot('update', $cambio)) {
            throw new AuthorizationException('No tiene permisos para editar estos cambios de animal.');
        }

        $payload = [];
        if (array_key_exists('fecha_cambio', $data)) $payload['fecha_cambio'] = $data['fecha_cambio'];
        if (array_key_exists('etapa_cambio', $data)) $payload['etapa_cambio'] = $data['etapa_cambio'];
        if (array_key_exists('peso', $data)) $payload['peso'] = $data['peso'];
        if (array_key_exists('altura', $data)) $payload['altura'] = $data['altura'];
        if (array_key_exists('comentario', $data)) $payload['comentario'] = $data['comentario'];

        if (!empty($payload['etapa_cambio']) && $animal) {
            $nombreEtapa = strtolower(trim($payload['etapa_cambio']));
            $targetEtapa = Etapa::whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreEtapa])
                ->when($animal->composicionRaza?->tipo_animal_id, fn($q, $tid) => $q->where('tipo_animal_id', $tid))
                ->when($animal->sexo, fn($q, $sex) => $q->where(fn($sq) => $sq->where('sexo', $sex)->orWhereNull('sexo')))
                ->first();

            if (!$targetEtapa) {
                $targetEtapa = Etapa::whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreEtapa])->first();
            }

            if ($targetEtapa) {
                $fechaCambio = $payload['fecha_cambio'] ?? $cambio->fecha_cambio ?? now()->toDateString();
                EtapaAnimal::where('animal_id', $animal->id)
                    ->where(function ($q) use ($fechaCambio) {
                        $q->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>', $fechaCambio);
                    })
                    ->update(['fecha_fin' => $fechaCambio]);

                $etapaAnimal = EtapaAnimal::where('animal_id', $animal->id)
                    ->where('etapa_id', $targetEtapa->id)
                    ->where('fecha_ini', $fechaCambio)
                    ->first();

                if (!$etapaAnimal) {
                    $etapaAnimal = EtapaAnimal::create([
                        'animal_id' => $animal->id,
                        'etapa_id'  => $targetEtapa->id,
                        'fecha_ini' => $fechaCambio,
                        'fecha_fin' => null,
                    ]);
                } else {
                    $etapaAnimal->update(['fecha_fin' => null]);
                }

                $payload['animal_etapa_id'] = $etapaAnimal->id;
            }
        }

        $cambio->update($payload);

        return $cambio;
    }

    /**
     * Elimina un log de cambio de animal.
     *
     * @param int $id ID del registro de CambiosAnimal.
     * @param mixed $user Usuario que realiza la acción.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteCambio(int $id, $user): bool
    {
        $cambio = CambiosAnimal::findOrFail($id);
        $animal = $cambio->animal;

        if ($user->cannot('delete', $cambio)) {
            throw new AuthorizationException('No tiene permisos para eliminar estos cambios de animal.');
        }

        return $cambio->delete();
    }

    /**
     * Obtiene un registro de cambio específico por su ID.
     *
     * @param int $id ID del cambio.
     * @param mixed $user Usuario.
     * @return CambiosAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getCambioById(int $id, $user): CambiosAnimal
    {
        $cambio = CambiosAnimal::with(['etapaAnimal'])->findOrFail($id);
        $animal = $cambio->animal;

        if ($user->cannot('read', $cambio)) {
            throw new AuthorizationException('No tiene permisos para ver estos cambios de animal.');
        }

        return $cambio;
    }
}

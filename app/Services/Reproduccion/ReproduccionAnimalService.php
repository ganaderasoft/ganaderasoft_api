<?php

namespace App\Services\Reproduccion;

use App\Models\ReproduccionAnimal;
use App\Models\EtapaAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use App\Services\BaseService;

class ReproduccionAnimalService extends BaseService
{
    /**
     * Obtiene una lista paginada de registros de reproducción basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedReproducciones(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', ReproduccionAnimal::class)) {
            throw new AuthorizationException('Sin permisos para listar registros de reproducción.');
        }

        $query = ReproduccionAnimal::with([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
        ]);

        if (!empty($filters['animal_id'])) {
            $query->forAnimal($filters['animal_id']);
        }
        if (!empty($filters['finca_id'])) {
            $query->whereHas('etapaAnimal.animal.rebano', function($q) use ($filters) {
                $q->where('finca_id', $filters['finca_id']);
            });
        }
        if (!empty($filters['rebano_id'])) {
            $query->whereHas('etapaAnimal.animal', function($q) use ($filters) {
                $q->where('rebano_id', $filters['rebano_id']);
            });
        }
        if (!empty($filters['tipo'])) {
            $tipoFilter = strtolower($filters['tipo']);
            $query->where(function($q) use ($tipoFilter) {
                $q->whereRaw('LOWER(tipo_reproduccion) LIKE ?', ["%{$tipoFilter}%"]);
            });
        }
        if (!empty($filters['fecha_inicio'])) {
            $query->where('fecha_reproduccion', '>=', $filters['fecha_inicio']);
        }
        if (!empty($filters['fecha_fin'])) {
            $query->where('fecha_reproduccion', '<=', $filters['fecha_fin']);
        }

        $this->applyFincaFilter($query, $user, 'etapaAnimal.animal.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de reproducción, resolviendo animal_etapa_id si es necesario.
     */
    public function createReproduccion(array $data, $user)
    {

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();

            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa especificada no existe.']
                ]);
            }
        }

        if ($user->cannot('create', [ReproduccionAnimal::class, $data['animal_id'] ?? null, $data['animal_etapa_id'] ?? null])) {
            throw new AuthorizationException('No tiene permisos para registrar reproducción a este animal.');
        }

        if (isset($data['animal_etapa_id'])) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $repro = ReproduccionAnimal::create([
            'animal_etapa_id'    => $data['animal_etapa_id'],
            'fecha_reproduccion' => $data['fecha_reproduccion'],
            'tipo_reproduccion'  => $data['tipo_reproduccion'] ?? null,
            'observacion'        => $data['observacion'] ?? null,
        ]);

        return $repro->load([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
        ]);
    }

    /**
     * Obtiene un registro de reproducción específico por su ID.
     */
    public function getReproduccionById($id, $user)
    {
        $repro = ReproduccionAnimal::with([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
        ])->findOrFail($id);

        if ($user->cannot('read', $repro)) {
            throw new AuthorizationException('No tiene permisos para ver este registro de reproducción.');
        }

        return $repro;
    }

    /**
     * Actualiza un registro de reproducción existente.
     */
    public function updateReproduccion($id, array $data, $user)
    {
        $repro = ReproduccionAnimal::findOrFail($id);

        if ($user->cannot('update', $repro)) {
            throw new AuthorizationException('No tiene permisos para actualizar este registro de reproducción.');
        }

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();

            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa especificada no existe.']
                ]);
            }
        }

        if (isset($data['animal_etapa_id']) && $data['animal_etapa_id'] != $repro->animal_etapa_id) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_etapa_id', $data)) {
            $updatePayload['animal_etapa_id'] = $data['animal_etapa_id'];
        }
        if (array_key_exists('fecha_reproduccion', $data)) {
            $updatePayload['fecha_reproduccion'] = $data['fecha_reproduccion'];
        }
        if (array_key_exists('tipo_reproduccion', $data)) {
            $updatePayload['tipo_reproduccion'] = $data['tipo_reproduccion'];
        }
        if (array_key_exists('observacion', $data)) {
            $updatePayload['observacion'] = $data['observacion'];
        }

        $repro->update($updatePayload);

        return $repro->load([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
        ]);
    }

    /**
     * Elimina un registro de reproducción existente.
     */
    public function deleteReproduccion($id, $user)
    {
        $repro = ReproduccionAnimal::findOrFail($id);

        if ($user->cannot('delete', $repro)) {
            throw new AuthorizationException('No tiene permisos para eliminar este registro de reproducción.');
        }

        return $repro->delete();
    }

    /**
     * Valida que el animal asociado al etapa_animal sea hembra ('H').
     */
    private function validateFemaleEtapaAnimal($etapaAnimalId): void
    {
        $etapaAnimal = EtapaAnimal::with('animal')->findOrFail($etapaAnimalId);
        if ($etapaAnimal->animal) {
            $sexo = strtoupper((string) $etapaAnimal->animal->sexo);
            if (!in_array($sexo, ['H', 'F', 'HEMBRA', 'FEMENINO'], true)) {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['El registro de reproducción solo puede ser creado para animales hembras (H).']
                ]);
            }
        }
    }

    /**
     * Valida que el animal sea hembra ('H').
     */
    private function validateFemaleAnimal($animalId): void
    {
        $animal = Animal::findOrFail($animalId);
        $sexo = strtoupper((string) $animal->sexo);
        if (!in_array($sexo, ['H', 'F', 'HEMBRA', 'FEMENINO'], true)) {
            throw ValidationException::withMessages([
                'animal_id' => ['El registro de reproducción solo puede ser creado para animales hembras (H).']
            ]);
        }
    }
}

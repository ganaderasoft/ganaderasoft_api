<?php

namespace App\Services\Reproduccion;

use App\Models\RegistroCelo;
use App\Models\EtapaAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use App\Services\BaseService;

class RegistroCeloService extends BaseService
{
    /**
     * Obtiene una lista paginada de registros de celo basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedCelos(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', RegistroCelo::class)) {
            throw new AuthorizationException('Sin permisos para listar registros de celo.');
        }

        $query = RegistroCelo::with([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'servicios',
        ]);

        if (!empty($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function ($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->where('fecha', '>=', $filters['fecha_inicio']);
        }

        if (!empty($filters['fecha_fin'])) {
            $query->where('fecha', '<=', $filters['fecha_fin']);
        }

        $this->applyFincaFilter($query, $user, 'etapaAnimal.animal.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de celo, resolviendo animal_etapa_id si es necesario.
     */
    public function createCelo(array $data, $user)
    {

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id'])) {
            if (!empty($data['etapa_id'])) {
                $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                    ->where('etapa_id', $data['etapa_id'])
                    ->first();

                if ($etapaAnimal) {
                    $data['animal_etapa_id'] = $etapaAnimal->id;
                }
            }

            if (empty($data['animal_etapa_id'])) {
                $latestEtapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])->latest('id')->first();
                if ($latestEtapaAnimal) {
                    $data['animal_etapa_id'] = $latestEtapaAnimal->id;
                } else {
                    throw ValidationException::withMessages([
                        'animal_id' => ['El animal seleccionado no cuenta con una etapa productiva asignada.']
                    ]);
                }
            }
        }

        if ($user->cannot('create', [RegistroCelo::class, $data['animal_id'] ?? null, $data['animal_etapa_id'] ?? null])) {
            throw new AuthorizationException('No tiene permisos para registrar celo a este animal.');
        }

        if (isset($data['animal_etapa_id'])) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $celo = RegistroCelo::create([
            'animal_etapa_id' => $data['animal_etapa_id'],
            'fecha'           => $data['fecha'],
            'observacion'     => $data['observacion'] ?? null,
        ]);

        return $celo->load([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'servicios',
        ]);
    }

    /**
     * Obtiene un registro de celo específico por su ID.
     */
    public function getCeloById($id, $user)
    {
        $celo = RegistroCelo::with([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'servicios.semen',
            'servicios.tecnico',
        ])->findOrFail($id);

        if ($user->cannot('read', $celo)) {
            throw new AuthorizationException('No tiene permisos para ver este registro de celo.');
        }

        return $celo;
    }

    /**
     * Actualiza un registro de celo existente.
     */
    public function updateCelo($id, array $data, $user)
    {
        $celo = RegistroCelo::findOrFail($id);

        if ($user->cannot('update', $celo)) {
            throw new AuthorizationException('No tiene permisos para actualizar este registro de celo.');
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

        if (isset($data['animal_etapa_id']) && $data['animal_etapa_id'] != $celo->animal_etapa_id) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_etapa_id', $data)) {
            $updatePayload['animal_etapa_id'] = $data['animal_etapa_id'];
        }
        if (array_key_exists('fecha', $data)) {
            $updatePayload['fecha'] = $data['fecha'];
        }
        if (array_key_exists('observacion', $data)) {
            $updatePayload['observacion'] = $data['observacion'];
        }

        $celo->update($updatePayload);

        return $celo->load([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'servicios.semen',
            'servicios.tecnico',
        ]);
    }

    /**
     * Elimina un registro de celo existente.
     */
    public function deleteCelo($id, $user)
    {
        $celo = RegistroCelo::findOrFail($id);

        if ($user->cannot('delete', $celo)) {
            throw new AuthorizationException('No tiene permisos para eliminar este registro de celo.');
        }

        return $celo->delete();
    }

    /**
     * Valida que el animal asociado a etapa_animal sea hembra ('H').
     */
    private function validateFemaleEtapaAnimal($etapaAnimalId): void
    {
        $etapaAnimal = EtapaAnimal::with('animal')->findOrFail($etapaAnimalId);
        if ($etapaAnimal->animal) {
            $sexo = strtoupper((string) $etapaAnimal->animal->sexo);
            if (!in_array($sexo, ['H', 'F', 'HEMBRA', 'FEMENINO'], true)) {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['El registro de celo solo puede ser creado para animales hembras (H).']
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
                'animal_id' => ['El registro de celo solo puede ser creado para animales hembras (H).']
            ]);
        }
    }
}

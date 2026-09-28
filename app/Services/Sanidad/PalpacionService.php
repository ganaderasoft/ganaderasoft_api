<?php

namespace App\Services\Sanidad;

use App\Models\Palpacion;
use App\Models\EtapaAnimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

use App\Services\BaseService;

class PalpacionService extends BaseService
{
    /**
     * Obtiene una lista paginada de palpaciones basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedPalpaciones(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', Palpacion::class)) {
            throw new AuthorizationException('Sin permisos para listar palpaciones.');
        }

        $query = Palpacion::with([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
            'tecnico.persona',
            'tecnico.tipoTrabajador'
        ]);

        if (!empty($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
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
                $q->whereRaw('LOWER(tipo) LIKE ?', ["%{$tipoFilter}%"]);
            });
        }
        
        if (!empty($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->whereBetween('fecha', [$filters['fecha_inicio'], $fechaFin]);
        }

        $this->applyFincaFilter($query, $user, 'etapaAnimal.animal.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de Palpación.
     */
    public function createPalpacion(array $data, $user)
    {

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();
            
            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa no existe en los registros.']
                ]);
            }
        }

        if ($user->cannot('create', [Palpacion::class, $data['animal_id'] ?? null, $data['animal_etapa_id'] ?? null])) {
            throw new AuthorizationException('No tiene permisos para registrar palpación a este animal.');
        }

        $palpacion = Palpacion::create($data);
        return $palpacion->load([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
            'tecnico.persona',
            'tecnico.tipoTrabajador'
        ]);
    }

    /**
     * Obtiene una palpación específica por su ID.
     */
    public function getPalpacionById($id, $user)
    {
        $palpacion = Palpacion::with([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
            'tecnico.persona',
            'tecnico.tipoTrabajador'
        ])->findOrFail($id);

        if ($user->cannot('read', $palpacion)) {
            throw new AuthorizationException('No tiene permisos para ver esta palpación.');
        }

        return $palpacion;
    }

    /**
     * Actualiza un registro de palpación existente.
     */
    public function updatePalpacion($id, array $data, $user)
    {
        $palpacion = Palpacion::findOrFail($id);

        if ($user->cannot('update', $palpacion)) {
            throw new AuthorizationException('No tiene permisos para actualizar esta palpación.');
        }

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();
            
            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa no existe en los registros.']
                ]);
            }
        }

        $palpacion->update($data);
        return $palpacion->load([
            'etapaAnimal.animal.rebano.finca',
            'etapaAnimal.etapa',
            'animal.rebano.finca',
            'etapa',
            'tecnico.persona',
            'tecnico.tipoTrabajador'
        ]);
    }

    /**
     * Elimina un registro de palpación existente.
     */
    public function deletePalpacion($id, $user)
    {
        $palpacion = Palpacion::findOrFail($id);

        if ($user->cannot('delete', $palpacion)) {
            throw new AuthorizationException('No tiene permisos para eliminar esta palpación.');
        }

        return $palpacion->delete();
    }
}

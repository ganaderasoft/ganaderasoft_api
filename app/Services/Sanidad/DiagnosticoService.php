<?php

namespace App\Services\Sanidad;

use App\Models\Diagnostico;
use App\Models\EtapaAnimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

use App\Services\BaseService;

class DiagnosticoService extends BaseService
{
    /**
     * Obtiene una lista paginada de diagnósticos basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedDiagnosticos(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', Diagnostico::class)) {
            throw new AuthorizationException('Sin permisos para listar diagnósticos.');
        }

        $query = Diagnostico::with(['etapaAnimal.animal.rebano.finca', 'etapaAnimal.etapa', 'tratamientos']);

        if (isset($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }
        
        if (isset($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }
        
        if (isset($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->whereBetween('fecha', [$filters['fecha_inicio'], $fechaFin]);
        }

        $this->applyFincaFilter($query, $user, 'animal.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de diagnóstico.
     */
    public function createDiagnostico(array $data, $user)
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

        if ($user->cannot('create', [Diagnostico::class, $data['animal_id'] ?? null, $data['animal_etapa_id'] ?? null])) {
            throw new AuthorizationException('No tiene permisos para registrar diagnóstico a este animal.');
        }

        $diagnostico = Diagnostico::create($data);
        return $diagnostico->load(['etapaAnimal.animal.rebano.finca', 'etapaAnimal.etapa', 'tratamientos']);
    }

    /**
     * Obtiene un diagnóstico específico por su ID.
     */
    public function getDiagnosticoById($id, $user)
    {
        $diagnostico = Diagnostico::with(['etapaAnimal.animal.rebano.finca', 'etapaAnimal.etapa', 'tratamientos'])->findOrFail($id);

        if ($user->cannot('read', $diagnostico)) {
            throw new AuthorizationException('No tiene permisos para ver este diagnóstico.');
        }

        return $diagnostico;
    }

    /**
     * Actualiza un registro de diagnóstico existente.
     */
    public function updateDiagnostico($id, array $data, $user)
    {
        $diagnostico = Diagnostico::findOrFail($id);

        if ($user->cannot('update', $diagnostico)) {
            throw new AuthorizationException('No tiene permisos para actualizar este diagnóstico.');
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

        $diagnostico->update($data);
        return $diagnostico->load(['etapaAnimal.animal.rebano.finca', 'etapaAnimal.etapa', 'tratamientos']);
    }

    /**
     * Elimina un registro de diagnóstico existente.
     */
    public function deleteDiagnostico($id, $user)
    {
        $diagnostico = Diagnostico::findOrFail($id);

        if ($user->cannot('delete', $diagnostico)) {
            throw new AuthorizationException('No tiene permisos para eliminar este diagnóstico.');
        }

        return $diagnostico->delete();
    }
}

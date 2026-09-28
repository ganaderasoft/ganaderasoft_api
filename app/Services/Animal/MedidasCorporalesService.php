<?php

namespace App\Services\Animal;

use App\Models\MedidasCorporales;
use App\Models\Animal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Services\BaseService;

class MedidasCorporalesService extends BaseService
{
    /**
     * Obtiene el listado de medidas corporales aplicando filtros.
     *
     * @param array $filters Filtros.
     * @param mixed $user Usuario.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listMedidas(array $filters, $user)
    {

        if ($user->cannot('readAny', MedidasCorporales::class)) {
            throw new AuthorizationException('Sin permisos para listar las medidas corporales.');
        }

        $query = MedidasCorporales::with(['etapaAnimal.etapa', 'etapaAnimal.animal']);

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

        $this->applyFincaFilter($query, $user, 'etapaAnimal.animal.rebano');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo registro de medidas corporales.
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return MedidasCorporales
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function createMedidas(array $data, $user): MedidasCorporales
    {
        $animalEtapaId = $data['animal_etapa_id'] ?? null;

        if (!$animalEtapaId && !empty($data['animal_id'])) {
            $activeEtapa = \App\Models\EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where(function ($q) {
                    $q->whereNull('fecha_fin')
                      ->orWhere('fecha_fin', '>=', now()->toDateString());
                })
                ->orderByDesc('fecha_ini')
                ->first();

            if ($activeEtapa) {
                $animalEtapaId = $activeEtapa->id;
            }
        }

        // Obtener el animal a través de la etapa seleccionada para verificar permisos
        $animal = Animal::whereHas('etapaAnimales', function ($q) use ($animalEtapaId) {
            $q->where('id', $animalEtapaId);
        })->firstOrFail();

        if ($user->cannot('create', [MedidasCorporales::class, $animal->id])) {
            throw new AuthorizationException('No tiene permisos para registrar medidas a este animal.');
        }

        $medida = MedidasCorporales::create([
            'altura_hc'       => $data['altura_hc'] ?? null,
            'altura_hg'       => $data['altura_hg'] ?? null,
            'perimetro_pt'    => $data['perimetro_pt'] ?? null,
            'perimetro_pca'   => $data['perimetro_pca'] ?? null,
            'longitud_lc'     => $data['longitud_lc'] ?? null,
            'longitud_lg'     => $data['longitud_lg'] ?? null,
            'anchura_ag'      => $data['anchura_ag'] ?? null,
            'animal_etapa_id' => $animalEtapaId,
        ]);

        return $medida->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);
    }

    /**
     * Obtiene un registro de medidas por su ID.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return MedidasCorporales
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getMedidasById(int $id, $user): MedidasCorporales
    {
        $medidasCorporales = MedidasCorporales::with(['etapaAnimal.etapa', 'etapaAnimal.animal.rebano.finca'])->findOrFail($id);

        if ($user->cannot('read', $medidasCorporales)) {
            throw new AuthorizationException('No tiene permisos para ver estas medidas corporales.');
        }

        return $medidasCorporales;
    }

    /**
     * Actualiza un registro de medidas corporales.
     *
     * @param int $id ID.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return MedidasCorporales
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateMedidas(int $id, array $data, $user): MedidasCorporales
    {
        $medidasCorporales = MedidasCorporales::findOrFail($id);

        if ($user->cannot('update', $medidasCorporales)) {
            throw new AuthorizationException('No tiene permisos para editar estas medidas corporales.');
        }

        $payload = [];
        if (array_key_exists('altura_hc', $data)) $payload['altura_hc'] = $data['altura_hc'];
        if (array_key_exists('altura_hg', $data)) $payload['altura_hg'] = $data['altura_hg'];
        if (array_key_exists('perimetro_pt', $data)) $payload['perimetro_pt'] = $data['perimetro_pt'];
        if (array_key_exists('perimetro_pca', $data)) $payload['perimetro_pca'] = $data['perimetro_pca'];
        if (array_key_exists('longitud_lc', $data)) $payload['longitud_lc'] = $data['longitud_lc'];
        if (array_key_exists('longitud_lg', $data)) $payload['longitud_lg'] = $data['longitud_lg'];
        if (array_key_exists('anchura_ag', $data)) $payload['anchura_ag'] = $data['anchura_ag'];

        $medidasCorporales->update($payload);

        return $medidasCorporales->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);
    }

    /**
     * Elimina un registro de medidas corporales.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteMedidas(int $id, $user): bool
    {
        $medidasCorporales = MedidasCorporales::findOrFail($id);

        if ($user->cannot('delete', $medidasCorporales)) {
            throw new AuthorizationException('No tiene permisos para eliminar estas medidas corporales.');
        }

        return $medidasCorporales->delete();
    }
}

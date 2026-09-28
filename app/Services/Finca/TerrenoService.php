<?php

namespace App\Services\Finca;

use App\Models\Terreno;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\BaseService;

class TerrenoService extends BaseService
{
    /**
     * Obtener lista de terrenos paginada.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listTerrenos(array $filters, User $user)
    {
        if ($user->cannot('readAny', Terreno::class)) {
            throw new AuthorizationException('Sin permisos para listar terrenos.');
        }

        $query = Terreno::with('finca');

        $this->applyFincaFilter($query, $user, null);

        $this->applyFilters($query, $filters);

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Almacenar un nuevo terreno.
     *
     * @param array $data
     * @param User $user
     * @return Terreno
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function storeTerreno(array $data, User $user): Terreno
    {
        $fincaId = (int) $data['finca_id'];
        
        if ($user->cannot('create', [Terreno::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para crear terreno en esta finca.');
        }
        
        Finca::findOrFail($fincaId);

        $terreno = Terreno::create($data);

        return $terreno->load('finca');
    }

    /**
     * Obtener un terreno específico.
     *
     * @param int $id
     * @param User $user
     * @return Terreno
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getTerreno(int $id, User $user): Terreno
    {
        $terreno = Terreno::with('finca')->findOrFail($id);

        if ($user->cannot('read', $terreno)) {
            throw new AuthorizationException('No tiene permisos para ver este terreno.');
        }

        return $terreno;
    }

    /**
     * Actualizar los datos de un terreno.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return Terreno
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function updateTerreno(int $id, array $data, User $user): Terreno
    {
        $terreno = Terreno::with('finca')->findOrFail($id);

        if ($user->cannot('update', $terreno)) {
            throw new AuthorizationException('No tiene permisos para actualizar este terreno.');
        }

        if (isset($data['finca_id']) && (int) $data['finca_id'] !== $terreno->finca_id) {
            if ($user->cannot('create', [Terreno::class, (int) $data['finca_id']])) {
                throw new AuthorizationException('No tiene permisos para mover el terreno a esta finca.');
            }
            Finca::findOrFail((int) $data['finca_id']);
        }

        $terreno->update($data);

        return $terreno->fresh(['finca']);
    }

    /**
     * Eliminar físicamente un terreno.
     *
     * @param int $id
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function deleteTerreno(int $id, User $user): bool
    {
        $terreno = Terreno::with('finca')->findOrFail($id);

        if ($user->cannot('delete', $terreno)) {
            throw new AuthorizationException('No tiene permisos para eliminar este terreno.');
        }

        return $terreno->delete();
    }

    /**
     * Aplicar filtros opcionales de búsqueda a la consulta.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        $fincaId = $filters['finca_id'] ?? $filters['id_finca'] ?? $filters['id_Finca'] ?? null;
        if ($fincaId !== null) {
            $query->forFinca($fincaId);
        }

        if (isset($filters['relieve'])) {
            $query->byRelieve($filters['relieve']);
        }
    }
}

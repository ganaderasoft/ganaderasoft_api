<?php

namespace App\Services\Inventario;

use App\Models\InventarioVacuno;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Services\BaseService;

class InventarioVacunoService extends BaseService
{
    /**
     * Listar inventario vacuno.
     * @throws AuthorizationException
     */
    public function listInventarioVacuno(array $filters, User $user)
    {
        if ($user->cannot('readAny', InventarioVacuno::class)) {
            throw new AuthorizationException('Sin permisos para listar inventarios.');
        }

        $query = InventarioVacuno::query()->with('finca');

        $this->applyFincaFilter($query, $user, null);

        $fincaIdFilter = $filters['finca_id'] ?? $filters['id_finca'] ?? null;
        if ($fincaIdFilter) {
            if (!$user->isAdmin() && !in_array($fincaIdFilter, $user->getAllowedFincasIds())) {
                throw new AuthorizationException('No tiene permisos para ver el inventario de esta finca.');
            }
            $query->forFinca($fincaIdFilter);
        }

        if (isset($filters['fecha_inicio'])) {
            $query->byDateRange($filters['fecha_inicio'], $filters['fecha_fin'] ?? null);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Almacenar un nuevo registro de inventario vacuno.
     * @throws AuthorizationException
     */
    public function storeInventarioVacuno(array $data, User $user): InventarioVacuno
    {
        $fincaId = (int) $data['finca_id'];

        if ($user->cannot('create', [InventarioVacuno::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para crear inventarios en esta finca.');
        }

        return InventarioVacuno::create($data);
    }

    /**
     * Obtener un registro específico de inventario vacuno.
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getInventarioVacuno(int $id, User $user): InventarioVacuno
    {
        $inventario = InventarioVacuno::with('finca')->findOrFail($id);

        if ($user->cannot('read', $inventario)) {
            throw new AuthorizationException('No tiene permisos para ver este inventario.');
        }

        return $inventario;
    }

    /**
     * Actualizar los datos del inventario vacuno.
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function updateInventarioVacuno(int $id, array $data, User $user): InventarioVacuno
    {
        $inventario = InventarioVacuno::findOrFail($id);

        if ($user->cannot('update', $inventario)) {
            throw new AuthorizationException('No tiene permisos para actualizar este inventario.');
        }

        if (isset($data['finca_id']) && (int) $data['finca_id'] !== $inventario->finca_id) {
            if ($user->cannot('create', [InventarioVacuno::class, (int) $data['finca_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar el inventario a la nueva finca.');
            }
        }

        $inventario->update($data);

        return $inventario;
    }

    /**
     * Eliminar el registro del inventario vacuno.
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function deleteInventarioVacuno(int $id, User $user): bool
    {
        $inventario = InventarioVacuno::findOrFail($id);

        if ($user->cannot('delete', $inventario)) {
            throw new AuthorizationException('No tiene permisos para eliminar este inventario.');
        }

        return $inventario->delete();
    }
}

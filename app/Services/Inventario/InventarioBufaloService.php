<?php

namespace App\Services\Inventario;

use App\Models\InventarioBufalo;
use App\Models\Finca;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use App\Services\BaseService;

class InventarioBufaloService extends BaseService
{
    /**
     * Listar inventario de búfalos.
     * @throws AuthorizationException
     */
    public function listInventarioBufalo(array $filters, User $user)
    {
        if ($user->cannot('readAny', InventarioBufalo::class)) {
            throw new AuthorizationException('Sin permisos para listar inventarios.');
        }

        $query = InventarioBufalo::with(['finca.propietario'])->recent();

        $this->applyFincaFilter($query, $user, null);

        $fincaId = Arr::get($filters, 'finca_id') ?? Arr::get($filters, 'id_finca');
        if ($fincaId) {
            $query->forFinca($fincaId);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Almacenar un nuevo registro de inventario de búfalos.
     * @throws AuthorizationException
     */
    public function storeInventarioBufalo(array $data, User $user)
    {
        $fincaId = (int) $data['finca_id'];

        if ($user->cannot('create', [InventarioBufalo::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para crear inventarios en esta finca.');
        }

        $data['num_becerro'] = $data['num_becerro'] ?? 0;
        $data['num_anojo'] = $data['num_anojo'] ?? 0;
        $data['num_bubilla'] = $data['num_bubilla'] ?? 0;
        $data['num_bufalo'] = $data['num_bufalo'] ?? 0;

        $inventario = InventarioBufalo::create($data);
        $inventario->load(['finca.propietario']);

        return $inventario;
    }

    /**
     * Obtener un registro específico de inventario de búfalos.
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getInventarioBufalo(int $id, User $user)
    {
        $inventario = InventarioBufalo::with(['finca.propietario'])->findOrFail($id);

        if ($user->cannot('read', $inventario)) {
            throw new AuthorizationException('No tiene permisos para ver este inventario.');
        }

        return $inventario;
    }

    /**
     * Actualizar los datos del inventario de búfalos.
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateInventarioBufalo(int $id, array $data, User $user)
    {
        $inventario = InventarioBufalo::findOrFail($id);

        if ($user->cannot('update', $inventario)) {
            throw new AuthorizationException('No tiene permisos para actualizar este inventario.');
        }

        if (isset($data['finca_id']) && (int) $data['finca_id'] !== $inventario->finca_id) {
            if ($user->cannot('create', [InventarioBufalo::class, (int) $data['finca_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar el inventario a la nueva finca.');
            }
        }

        $inventario->update($data);

        return $inventario;
    }

    /**
     * Eliminar el registro del inventario de búfalos.
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteInventarioBufalo(int $id, User $user)
    {
        $inventario = InventarioBufalo::findOrFail($id);

        if ($user->cannot('delete', $inventario)) {
            throw new AuthorizationException('No tiene permisos para eliminar este inventario.');
        }

        $inventario->delete();
        return true;
    }
}

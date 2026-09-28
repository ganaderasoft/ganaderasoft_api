<?php

namespace App\Services\Sanidad;

use App\Models\Vacuna;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

use App\Services\BaseService;

class VacunaService extends BaseService
{
    /**
     * Obtiene una lista paginada de vacunas basándose en los filtros.
     */
    public function getPaginatedVacunas(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', Vacuna::class)) {
            throw new AuthorizationException('Sin permisos para listar vacunas.');
        }

        $query = Vacuna::with('casasComerciales');

        if (!empty($filters['nombre'])) {
            $query->byNombre($filters['nombre']);
        }

        if (isset($filters['activa'])) {
            $query->where('activa', filter_var($filters['activa'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de vacuna.
     */
    public function createVacuna(array $data, $user)
    {

        if ($user->cannot('create', Vacuna::class)) {
            throw new AuthorizationException('No tiene permisos para crear una vacuna.');
        }

        return Vacuna::create([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activa'      => array_key_exists('activa', $data) ? $data['activa'] : true,
        ]);
    }

    /**
     * Obtiene una vacuna específica por su ID.
     */
    public function getVacunaById($id, $user)
    {
        $vacuna = Vacuna::with(['casasComerciales'])->findOrFail($id);

        if ($user->cannot('read', $vacuna)) {
            throw new AuthorizationException('No tiene permisos para ver esta vacuna.');
        }

        return $vacuna;
    }

    /**
     * Actualiza un registro de vacuna existente.
     */
    public function updateVacuna($id, array $data, $user)
    {
        $vacuna = Vacuna::findOrFail($id);

        if ($user->cannot('update', $vacuna)) {
            throw new AuthorizationException('No tiene permisos para actualizar esta vacuna.');
        }

        $vacuna->update([
            'nombre'      => $data['nombre'] ?? $vacuna->nombre,
            'descripcion' => array_key_exists('descripcion', $data) ? $data['descripcion'] : $vacuna->descripcion,
            'activa'      => array_key_exists('activa', $data) ? $data['activa'] : $vacuna->activa,
        ]);

        return $vacuna;
    }

    /**
     * Elimina un registro de vacuna existente.
     */
    public function deleteVacuna($id, $user)
    {
        $vacuna = Vacuna::findOrFail($id);

        if ($user->cannot('delete', $vacuna)) {
            throw new AuthorizationException('No tiene permisos para eliminar esta vacuna.');
        }

        $vacuna->delete();
        return true;
    }
}

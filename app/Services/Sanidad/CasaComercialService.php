<?php

namespace App\Services\Sanidad;

use App\Models\CasaComercial;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

use App\Services\BaseService;

class CasaComercialService extends BaseService
{
    /**
     * Obtiene una lista paginada de casas comerciales basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedCasasComerciales(array $filters, $user, $perPage = 15)
    {

        if ($user->cannot('readAny', CasaComercial::class)) {
            throw new AuthorizationException('Sin permisos para listar casas comerciales.');
        }

        $query = CasaComercial::query();

        if (!empty($filters['laboratorio'])) {
            $query->byLaboratorio($filters['laboratorio']);
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
     * Crea un nuevo registro de casa comercial.
     */
    public function createCasaComercial(array $data, $user)
    {

        if ($user->cannot('create', CasaComercial::class)) {
            throw new AuthorizationException('No tiene permisos para crear una casa comercial.');
        }

        return CasaComercial::create([
            'laboratorio'     => $data['laboratorio'],
            'marca_comercial' => $data['marca_comercial'],
            'activa'          => array_key_exists('activa', $data) ? $data['activa'] : true,
        ]);
    }

    /**
     * Obtiene una casa comercial específica por su ID.
     */
    public function getCasaComercialById($id, $user)
    {
        $casaComercial = CasaComercial::with(['vacunas'])->findOrFail($id);

        if ($user->cannot('read', $casaComercial)) {
            throw new AuthorizationException('No tiene permisos para ver esta casa comercial.');
        }

        return $casaComercial;
    }

    /**
     * Actualiza un registro de casa comercial existente.
     */
    public function updateCasaComercial($id, array $data, $user)
    {
        $casa = CasaComercial::findOrFail($id);

        if ($user->cannot('update', $casa)) {
            throw new AuthorizationException('No tiene permisos para actualizar esta casa comercial.');
        }

        $casa->update([
            'laboratorio'     => $data['laboratorio'] ?? $casa->laboratorio,
            'marca_comercial' => $data['marca_comercial'] ?? $casa->marca_comercial,
            'activa'          => array_key_exists('activa', $data) ? $data['activa'] : $casa->activa,
        ]);

        return $casa;
    }

    /**
     * Elimina un registro de casa comercial existente.
     */
    public function deleteCasaComercial($id, $user)
    {
        $casa = CasaComercial::findOrFail($id);

        if ($user->cannot('delete', $casa)) {
            throw new AuthorizationException('No tiene permisos para eliminar esta casa comercial.');
        }

        $casa->delete();
        return true;
    }
}

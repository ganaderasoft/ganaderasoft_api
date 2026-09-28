<?php

namespace App\Services\Animal;

use App\Models\ComposicionRaza;
use App\Models\Finca;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use App\Models\User;
use App\Services\BaseService;

class ComposicionRazaService extends BaseService
{
    /**
     * Obtiene el listado de composiciones de raza.
     *
     * @param array $filters Filtros.
     * @return LengthAwarePaginator
     */
    public function listComposiciones(array $filters, User $user)
    {
        if ($user->cannot('readAny', ComposicionRaza::class)) {
            throw new AuthorizationException('No tiene permisos para ver composiciones de raza.');
        }

        $query = ComposicionRaza::with(['finca', 'tipoAnimal']);

        if (!empty($filters['nombre'])) {
            $query->byName($filters['nombre']);
        }

        if (!empty($filters['finca_id'])) {
            // Filtro estricto: si envía finca_id, se retorna SOLO esa finca.
            if (!$user->isAdmin()) {
                $fincasPermitidas = $user->getAllowedFincasIds();
                if (!in_array($filters['finca_id'], $fincasPermitidas)) {
                    throw new AuthorizationException('No tiene permisos para acceder a las razas de esta finca.');
                }
            }
            $query->where('finca_id', $filters['finca_id']);
        } else {
            // Sin filtro: Retorna todo a lo que tiene acceso (Globales + Sus Fincas)
            if (!$user->isAdmin()) {
                $query->where(function($q) use ($user) {
                    $q->whereNull('finca_id')
                      ->orWhere(function($subQ) use ($user) {
                          $this->applyFincaFilter($subQ, $user, null, 'finca_id');
                      });
                });
            }
            // Si es Admin, no aplica ningún WHERE, retornando TODA la BD.
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra una nueva composición de raza.
     *
     * @param array $data Datos del registro.
     * @param mixed $user Usuario que realiza la acción.
     * @return ComposicionRaza
     * @throws AuthorizationException
     */
    public function createComposicion(array $data, User $user): ComposicionRaza
    {
        $fincaId = $data['finca_id'] ?? null;
        if ($user->cannot('create', [ComposicionRaza::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para crear una composición de raza.');
        }


        return ComposicionRaza::create([
            'nombre'                  => $data['nombre'],
            'siglas'                  => $data['siglas'] ?? null,
            'pelaje'                  => $data['pelaje'] ?? null,
            'proposito'               => $data['proposito'] ?? null,
            'tipo_raza'               => $data['tipo_raza'] ?? null,
            'origen'                  => $data['origen'] ?? null,
            'caracteristica_especial' => $data['caracteristica_especial'] ?? null,
            'proporcion_raza'         => $data['proporcion_raza'] ?? null,
            'finca_id'                => $data['finca_id'] ?? null,
            'tipo_animal_id'          => $data['tipo_animal_id'] ?? null,
        ]);
    }

    /**
     * Obtiene una composición de raza por su ID.
     *
     * @param int $id ID de la composición.
     * @param mixed $user Usuario.
     * @return ComposicionRaza
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getComposicionById(int $id, User $user): ComposicionRaza
    {
        $composicionRaza = ComposicionRaza::with(['finca', 'tipoAnimal', 'animales'])->findOrFail($id);

        if ($user->cannot('read', $composicionRaza)) {
            throw new AuthorizationException('No tiene permisos para ver esta composición de raza.');
        }

        return $composicionRaza;
    }

    /**
     * Actualiza una composición de raza.
     *
     * @param int $id ID del registro.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return ComposicionRaza
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateComposicion(int $id, array $data, User $user): ComposicionRaza
    {
        $composicionRaza = ComposicionRaza::findOrFail($id);

        if ($user->cannot('update', $composicionRaza)) {
            throw new AuthorizationException('No tiene permisos para actualizar esta composición de raza.');
        }

        // Validación de consistencia: No permitir asignar una finca a una raza si está siendo utilizada
        // por animales de otras fincas (a menos que la finca a la que se le asigne sea la única que la usa).
        if (array_key_exists('finca_id', $data) && !empty($data['finca_id'])) {
            $nuevaFincaId = (int) $data['finca_id'];

            if ((int) $composicionRaza->finca_id !== $nuevaFincaId) {
                $otrasFincas = Finca::whereHas('rebanos.animales', function ($q) use ($id) {
                    $q->where('composicion_raza_id', $id);
                })->where('id', '!=', $nuevaFincaId)->pluck('nombre');

                if ($otrasFincas->isNotEmpty()) {
                    $fincasNombres = $otrasFincas->unique()->join(', ');
                    throw new ConflictHttpException("No se puede asignar esta raza a la finca seleccionada porque actualmente está siendo utilizada por animales de otra(s) finca(s) ({$fincasNombres}). Solo puede asignarse a una finca si es la única que la utiliza o si no tiene animales asociados.");
                }
            }
        }

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];
        if (array_key_exists('siglas', $data)) $payload['siglas'] = $data['siglas'];
        if (array_key_exists('pelaje', $data)) $payload['pelaje'] = $data['pelaje'];
        if (array_key_exists('proposito', $data)) $payload['proposito'] = $data['proposito'];
        if (array_key_exists('tipo_raza', $data)) $payload['tipo_raza'] = $data['tipo_raza'];
        if (array_key_exists('origen', $data)) $payload['origen'] = $data['origen'];
        if (array_key_exists('caracteristica_especial', $data)) $payload['caracteristica_especial'] = $data['caracteristica_especial'];
        if (array_key_exists('proporcion_raza', $data)) $payload['proporcion_raza'] = $data['proporcion_raza'];
        if (array_key_exists('finca_id', $data)) $payload['finca_id'] = !empty($data['finca_id']) ? $data['finca_id'] : null;
        if (array_key_exists('tipo_animal_id', $data)) $payload['tipo_animal_id'] = $data['tipo_animal_id'];

        $composicionRaza->update($payload);

        return $composicionRaza;
    }

    /**
     * Elimina una composición de raza.
     *
     * @param int $id ID de la composición.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function deleteComposicion(int $id, User $user): bool
    {
        $composicionRaza = ComposicionRaza::findOrFail($id);

        if ($user->cannot('delete', $composicionRaza)) {
            throw new AuthorizationException('No tiene permisos para eliminar esta composición de raza.');
        }

        if ($composicionRaza->animales()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar la composición de raza porque tiene animales asociados.');
        }

        return $composicionRaza->delete();
    }
}

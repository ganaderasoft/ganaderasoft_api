<?php

namespace App\Services\Rebano;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Rebano;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\BaseService;

class RebanoService extends BaseService
{
    /**
     * Listar todos los rebaños, paginado según permisos y filtro de archivado.
     * Admin ve todos, propietario ve solo los suyos.
     */
    public function listRebanos(array $filters, User $user)
    {
        if ($user->cannot('readAny', Rebano::class)) {
            throw new AuthorizationException('Sin permisos para listar rebaños.');
        }

        $query = Rebano::with(['finca.propietario', 'animales'])->withCount('animales');

        $incluirArchivados = filter_var($filters['incluir_archivados'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$incluirArchivados) {
            $archivado = filter_var($filters['archivado'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->where('archivado', $archivado);
        }

        if (!empty($filters['finca_id'])) {
            $query->where('finca_id', $filters['finca_id']);
        }

        $this->applyFincaFilter($query, $user, null);

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo rebaño.
     */
    public function storeRebano(array $data, User $user)
    {
        $fincaId = (int) $data['finca_id'];
        
        if ($user->cannot('create', [Rebano::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para crear rebaño en esta finca');
        }

        $rebano = Rebano::create([
            'finca_id' => $data['finca_id'],
            'nombre' => $data['nombre'],
            'archivado' => false,
        ]);

        $rebano->load(['finca.propietario', 'animales'])->loadCount('animales');

        return $rebano;
    }

    /**
     * Obtener un rebaño específico.
     */
    public function getRebano(int $id, User $user)
    {
        $rebano = Rebano::with(['finca.propietario', 'animales'])->withCount('animales')->find($id);

        if (!$rebano) {
            throw new NotFoundHttpException('Rebaño no encontrado');
        }

        if ($user->cannot('read', $rebano)) {
            throw new AuthorizationException('No tiene permisos para ver este rebaño');
        }

        return $rebano;
    }

    /**
     * Actualizar los datos de un rebaño.
     */
    public function updateRebano(int $id, array $data, User $user)
    {
        $rebano = $this->getRebano($id, $user);

        if ($user->cannot('update', $rebano)) {
            throw new AuthorizationException('No tiene permisos para actualizar este rebaño');
        }

        if (isset($data['finca_id']) && (int) $data['finca_id'] !== $rebano->finca_id) {
            if ($user->cannot('create', [Rebano::class, (int) $data['finca_id']])) {
                throw new AuthorizationException('No tiene permisos para mover el rebaño a esa finca');
            }
        }

        $updateData = [];
        if (isset($data['nombre'])) {
            $updateData['nombre'] = $data['nombre'];
        }
        if (isset($data['finca_id'])) {
            $updateData['finca_id'] = $data['finca_id'];
        }

        $rebano->update($updateData);
        $rebano->load(['finca.propietario', 'animales']);

        return $rebano;
    }

    /**
     * Archivar un rebaño (archivado = true) y en cascada todos sus animales asociados.
     */
    public function archiveRebano(int $id, User $user): Rebano
    {
        $rebano = $this->getRebano($id, $user);
        
        if ($user->cannot('update', $rebano)) {
            throw new AuthorizationException('No tiene permisos para archivar este rebaño');
        }

        return DB::transaction(function () use ($rebano) {
            $rebano->update(['archivado' => true]);
            $rebano->animales()->update(['archivado' => true]);

            return $rebano->fresh(['finca.propietario', 'animales'])->loadCount('animales');
        });
    }

    /**
     * Desarchivar un rebaño (archivado = false).
     * Reactiva el rebaño y, si su finca padre estaba archivada, la reactiva también.
     */
    public function unarchiveRebano(int $id, User $user): Rebano
    {
        $rebano = $this->getRebano($id, $user);
        
        if ($user->cannot('update', $rebano)) {
            throw new AuthorizationException('No tiene permisos para desarchivar este rebaño');
        }

        return DB::transaction(function () use ($rebano) {
            $rebano->update(['archivado' => false]);

            // Si la finca asociada estaba archivada, reactivarla también
            if ($rebano->finca && $rebano->finca->archivado) {
                $rebano->finca->update(['archivado' => false]);
            }

            return $rebano->fresh(['finca.propietario', 'animales'])->loadCount('animales');
        });
    }

    /**
     * Eliminar físicamente un rebaño y sus animales asociados en cascada.
     *
     * Relaciones eliminadas en esta operación (en cascada por FK de base de datos):
     * - Animales asociados ('animals') y, para cada animal:
     *     - Historial de vacunaciones ('animal_vacuna')
     *     - Historial de estados de salud ('animal_estado_salud')
     *     - Historial de etapas ('animal_etapa')
     *     - Historial de pesajes corporales ('pesos_corporales')
     *     - Registros de lactancia y pesajes de leche ('lactancias', 'pesaje_leches')
     *     - Servicios reproductivos ('servicio_animals')
     *     - Genealogía y parentesco ('arbol_gens')
     * - Movimientos originados en este rebaño ('movimiento_rebanos')
     *
     * @param int $id
     * @param User $user
     * @return bool
     * @throws NotFoundHttpException
     * @throws AuthorizationException
     */
    public function deleteRebano(int $id, User $user): bool
    {
        $rebano = $this->getRebano($id, $user);
        
        if ($user->cannot('delete', $rebano)) {
            throw new AuthorizationException('No tiene permisos para eliminar este rebaño');
        }

        $rebano->delete();

        return true;
    }
}

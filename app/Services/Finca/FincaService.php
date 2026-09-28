<?php

namespace App\Services\Finca;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Rebano;
use App\Models\Terreno;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use App\Services\BaseService;

class FincaService extends BaseService
{
    /**
     * Obtener lista de fincas paginada según permisos y filtro de archivado.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     * @throws AuthorizationException
     */
    public function listFincas(array $filters, User $user)
    {
        if ($user->cannot('readAny', Finca::class)) {
            throw new AuthorizationException('Sin permisos para listar fincas.');
        }

        $query = Finca::with(['propietario.persona.users', 'terreno']);

        $incluirArchivados = filter_var($filters['incluir_archivados'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$incluirArchivados) {
            $archivado = filter_var($filters['archivado'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->where('archivado', $archivado);
        }

        $this->applyFincaFilter($query, $user, null, 'id');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear una nueva finca.
     *
     * @param array $data
     * @param User $user
     * @return Finca
     * @throws AuthorizationException
     */
    public function storeFinca(array $data, User $user): Finca
    {
        $propietarioId = (int) $data['propietario_id'];

        if ($user->cannot('create', [Finca::class, $propietarioId])) {
            throw new AuthorizationException('No tiene permisos para crear fincas para este propietario.');
        }

        return DB::transaction(function () use ($data, $user) {
            $finca = Finca::create([
                'propietario_id' => $data['propietario_id'],
                'nombre' => $data['nombre'],
                'explotacion_tipo' => $data['explotacion_tipo'],
                'archivado' => false,
            ]);

            if (!empty($data['terreno'])) {
                $terrenoData = $data['terreno'];
                $terrenoData['finca_id'] = $finca->id;
                Terreno::create($terrenoData);
            }

            // Asignar automáticamente la finca al usuario que la está creando 
            // (a menos que sea administrador, para no saturar su lista)
            // Si el creador NO es un Admin y NO es el Propietario de esta finca, 
            // significa que es un trabajador. Solo en ese caso lo registramos en finca_user.
            if (!$user->isAdmin()) {
                $isTheOwner = $user->propietario && $user->propietario->id === (int) $data['propietario_id'];
                
                if (!$isTheOwner) {
                    $finca->users()->attach($user->id, [
                        'access_level' => 'operator',
                        'is_default' => false,
                        'status' => 'active'
                    ]);
                }
            }

            return $finca->load(['propietario.persona.users', 'terreno']);
        });
    }

    /**
     * Obtener una finca específica validando permisos.
     *
     * @param int $id
     * @param User $user
     * @return Finca
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getFinca(int $id, User $user): Finca
    {
        $finca = Finca::with(['propietario.persona.users', 'terreno'])->findOrFail($id);

        if ($user->cannot('read', $finca)) {
            throw new AuthorizationException('No tiene permisos para ver esta finca.');
        }

        return $finca;
    }

    /**
     * Actualizar los datos de una finca.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return Finca
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateFinca(int $id, array $data, User $user): Finca
    {
        $finca = Finca::with(['propietario.persona.users', 'terreno'])->findOrFail($id);

        $nuevoPropietarioId = isset($data['propietario_id']) ? (int) $data['propietario_id'] : null;

        if ($user->cannot('update', [$finca, $nuevoPropietarioId])) {
            throw new AuthorizationException('No tiene permisos para actualizar esta finca o cambiar su propietario.');
        }

        return DB::transaction(function () use ($finca, $data) {
            $fincaUpdate = [];
            if (array_key_exists('nombre', $data)) $fincaUpdate['nombre'] = $data['nombre'];
            if (array_key_exists('explotacion_tipo', $data)) $fincaUpdate['explotacion_tipo'] = $data['explotacion_tipo'];
            if (array_key_exists('propietario_id', $data)) $fincaUpdate['propietario_id'] = $data['propietario_id'];

            if (!empty($fincaUpdate)) {
                $finca->update($fincaUpdate);
            }

            if (array_key_exists('terreno', $data) && is_array($data['terreno'])) {
                $terreno = $finca->terreno;
                if ($terreno) {
                    $terreno->update($data['terreno']);
                } else {
                    $terrenoData = $data['terreno'];
                    $terrenoData['finca_id'] = $finca->id;
                    Terreno::create($terrenoData);
                }
            }

            return $finca->fresh(['propietario.persona.users', 'terreno']);
        });
    }

    /**
     * Archivar una finca (estado archivado = true) y en cascada todos sus rebaños y animales.
     *
     * @param int $id
     * @param User $user
     * @return Finca
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function archiveFinca(int $id, User $user): Finca
    {
        $finca = Finca::findOrFail($id);

        if ($user->cannot('update', $finca)) {
            throw new AuthorizationException('No tiene permisos para archivar esta finca.');
        }

        return DB::transaction(function () use ($finca) {
            $finca->update(['archivado' => true]);

            $rebanoIds = $finca->rebanos()->pluck('id')->all();
            if (!empty($rebanoIds)) {
                Rebano::whereIn('id', $rebanoIds)->update(['archivado' => true]);
                Animal::whereIn('rebano_id', $rebanoIds)->update(['archivado' => true]);
            }

            return $finca->fresh(['propietario.persona.users', 'terreno']);
        });
    }

    /**
     * Desarchivar una finca (estado archivado = false).
     * Reactiva la finca sin alterar el estado individual de sus rebaños y animales previamente archivados.
     *
     * @param int $id
     * @param User $user
     * @return Finca
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function unarchiveFinca(int $id, User $user): Finca
    {
        $finca = Finca::findOrFail($id);

        if ($user->cannot('update', $finca)) {
            throw new AuthorizationException('No tiene permisos para desarchivar esta finca.');
        }

        $finca->update(['archivado' => false]);

        return $finca->fresh(['propietario.persona.users', 'terreno']);
    }

    /**
     * Eliminar físicamente una finca de la base de datos y todas sus dependencias en cascada.
     *
     * Relaciones eliminadas en esta operación:
     * - Terreno asociado (1 a 1 en 'terrenos')
     * - Asignaciones de usuarios (pivote 'finca_users')
     * - Afiliaciones gremiales ('afiliacions')
     * - Hierros y marcas ('hierros')
     * - Personal asignado a la finca ('personal_fincas')
     * - Inventarios de búfalos ('inventario_bufalos')
     * - Rebaños ('rebanos') y, en cascada a través de estos:
     *     - Animales ('animals')
     *     - Historial de vacunaciones ('animal_vacuna')
     *     - Historial de estados de salud ('animal_estado_salud')
     *     - Historial de etapas ('animal_etapa')
     *     - Pesajes corporales ('pesos_corporales')
     *     - Registros de lactancia y pesaje de leche ('lactancias', 'pesaje_leches')
     *     - Servicios reproductivos ('servicio_animals')
     *     - Genealogía y parentesco ('arbol_gens')
     *     - Movimientos de rebaño ('movimiento_rebanos')
     *
     * @param int $id
     * @param User $user
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteFinca(int $id, User $user): bool
    {
        $finca = Finca::findOrFail($id);

        if ($user->cannot('delete', $finca)) {
            throw new AuthorizationException('No tiene permisos para eliminar esta finca.');
        }

        return DB::transaction(function () use ($finca) {
            // 1. Eliminar terreno asociado
            if ($finca->terreno) {
                $finca->terreno->delete();
            }

            // 2. Desvincular usuarios asignados (tabla pivote finca_users)
            $finca->users()->detach();

            // 3. Eliminar registros directamente vinculados a la finca
            $finca->afiliaciones()->delete();
            $finca->hierros()->delete();
            $finca->personalFinca()->delete();
            $finca->inventariosBufalo()->delete();

            // 4. Eliminar rebaños asociados (elimina en cascada animales y sus historiales médicos, pesajes, etc.)
            $finca->rebanos()->delete();

            // 5. Eliminar la finca definitivamente
            $finca->delete();

            return true;
        });
    }
}

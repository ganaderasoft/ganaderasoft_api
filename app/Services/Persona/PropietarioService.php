<?php

namespace App\Services\Persona;

use App\Models\Propietario;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\BaseService;

class PropietarioService extends BaseService
{
    /**
     * Obtener lista de propietarios paginada según permisos.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listPropietarios(array $filters, User $user)
    {
        if ($user->cannot('readAny', Propietario::class)) {
            throw new AuthorizationException('Sin permisos para listar propietarios.');
        }

        $query = Propietario::with(['persona.users', 'fincas'])->active();

        if ($user->cannot('viewAll', Propietario::class)) {
            $propietario = $user->propietario;
            if (!$propietario) {
                throw new AuthorizationException('El usuario no está registrado como propietario.');
            }
            $query->where('id', $propietario->id);
        }

        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo propietario.
     *
     * @param array $data
     * @param User $user
     * @return Propietario
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function storePropietario(array $data, User $user): Propietario
    {
        $targetUserId = (int) $data['user_id'];

        if ($user->cannot('create', [Propietario::class, $targetUserId])) {
            throw new AuthorizationException('No tiene permisos para crear propietario para otro usuario.');
        }

        $targetUser = User::findOrFail($targetUserId);

        // Verificar si el perfil de propietario ya existe para este usuario
        if ($targetUser->propietario) {
            throw new ConflictHttpException('Ya existe un propietario para este usuario.');
        }

        return DB::transaction(function () use ($data, $targetUser) {
            // Crear la entidad física de la Persona
            $persona = Persona::create([
                'cedula' => $data['cedula'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'correo' => $data['correo'] ?? $targetUser->email,
                'status' => 'activo'
            ]);

            // Asociar la Persona con el Usuario
            $targetUser->personas()->attach($persona->id);

            // Crear el perfil del rol de Propietario
            $propietario = Propietario::create([
                'persona_id' => $persona->id
            ]);

            return $propietario->load(['persona.users', 'fincas']);
        });
    }

    /**
     * Obtener un propietario específico validando permisos.
     *
     * @param int $id
     * @param User $user
     * @return Propietario
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getPropietario(int $id, User $user): Propietario
    {
        $propietario = Propietario::with(['persona.users', 'fincas'])->findOrFail($id);

        if ($user->cannot('read', $propietario)) {
            throw new AuthorizationException('No tiene permisos para ver este propietario.');
        }

        return $propietario;
    }

    /**
     * Actualizar los detalles de la persona del propietario.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return Propietario
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updatePropietario(int $id, array $data, User $user): Propietario
    {
        $propietario = Propietario::with(['persona.users', 'fincas'])->findOrFail($id);

        if ($user->cannot('update', $propietario)) {
            throw new AuthorizationException('No tiene permisos para actualizar este propietario.');
        }

        // Actualizar selectivamente los atributos de la persona
        $personaData = [];
        if (array_key_exists('cedula', $data)) $personaData['cedula'] = $data['cedula'];
        if (array_key_exists('nombre', $data)) $personaData['nombre'] = $data['nombre'];
        if (array_key_exists('apellido', $data)) $personaData['apellido'] = $data['apellido'];
        if (array_key_exists('telefono', $data)) $personaData['telefono'] = $data['telefono'];
        if (array_key_exists('correo', $data)) $personaData['correo'] = $data['correo'];

        if (!empty($personaData)) {
            $propietario->persona->update($personaData);
        }

        return $propietario->fresh(['persona.users', 'fincas']);
    }

    /**
     * Archivar un propietario (cambia el estado de la persona a 'inactivo').
     *
     * @param int $id
     * @param User $user
     * @return void
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function archivePropietario(int $id, User $user): void
    {
        $propietario = Propietario::findOrFail($id);

        if ($user->cannot('delete', $propietario)) {
            throw new AuthorizationException('No tiene permisos para eliminar propietarios.');
        }

        $propietario = Propietario::findOrFail($id);
        $propietario->persona->update(['status' => 'inactivo']);
    }
}

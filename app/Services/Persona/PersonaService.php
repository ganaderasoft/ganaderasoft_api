<?php

namespace App\Services\Persona;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;

class PersonaService
{
    /**
     * Obtener lista de personas, con filtros opcionales.
     */
    public function getPersonas(array $filters, User $adminUser)
    {
        if ($adminUser->cannot('readAny', Persona::class)) {
            throw new AuthorizationException('No tienes permisos para ver personas.');
        }

        $query = Persona::with(['users', 'personalFincas', 'administrador', 'propietario']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('cedula', 'like', "%{$search}%")
                  ->orWhere('correo', 'like', "%{$search}%");
            });
        }

        if (isset($filters['eligible_for_user']) && filter_var($filters['eligible_for_user'], FILTER_VALIDATE_BOOLEAN)) {
            $query->doesntHave('users');
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Obtener una persona específica.
     */
    public function getPersona(int $id, User $adminUser)
    {
        $persona = Persona::with(['users', 'personalFincas', 'administrador', 'propietario'])->findOrFail($id);

        if ($adminUser->cannot('read', $persona)) {
            throw new AuthorizationException('No tienes permisos para ver esta persona.');
        }

        return $persona;
    }

    /**
     * Crear una persona.
     */
    public function createPersona(array $data, User $adminUser)
    {
        if ($adminUser->cannot('create', Persona::class)) {
            throw new AuthorizationException('No tienes permisos para crear personas.');
        }

        return Persona::create([
            'cedula' => $data['cedula'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'status' => $data['status'] ?? 'activo',
        ]);
    }

    /**
     * Actualizar una persona.
     */
    public function updatePersona(int $id, array $data, User $adminUser)
    {
        $persona = Persona::findOrFail($id);

        if ($adminUser->cannot('update', $persona)) {
            throw new AuthorizationException('No tienes permisos para actualizar personas.');
        }

        return DB::transaction(function () use ($persona, $data) {
            $oldCorreo = $persona->correo;
            
            $persona->update([
                'cedula' => $data['cedula'] ?? $persona->cedula,
                'nombre' => $data['nombre'] ?? $persona->nombre,
                'apellido' => $data['apellido'] ?? $persona->apellido,
                'telefono' => $data['telefono'] ?? $persona->telefono,
                'correo' => $data['correo'] ?? $persona->correo,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? $persona->fecha_nacimiento,
            ]);

            // Sincronizar correo con la cuenta de User si tiene y si el correo cambió
            if (isset($data['correo']) && $data['correo'] !== $oldCorreo) {
                foreach ($persona->users as $user) {
                    $user->update(['email' => $data['correo']]);
                }
            }

            return $persona;
        });
    }

    /**
     * Desactivar persona.
     */
    public function disablePersona(int $id, User $adminUser)
    {
        $persona = Persona::findOrFail($id);

        if ($adminUser->cannot('disable', $persona)) {
            throw new AuthorizationException('No tienes permisos para desactivar personas.');
        }

        $persona->update(['status' => 'inactivo']);
        return $persona;
    }

    /**
     * Activar persona.
     */
    public function enablePersona(int $id, User $adminUser)
    {
        $persona = Persona::findOrFail($id);

        if ($adminUser->cannot('enable', $persona)) {
            throw new AuthorizationException('No tienes permisos para activar personas.');
        }

        $persona->update(['status' => 'activo']);
        return $persona;
    }

    /**
     * Eliminar persona físicamente.
     */
    public function deletePersona(int $id, User $adminUser)
    {
        $persona = Persona::findOrFail($id);

        if ($adminUser->cannot('delete', $persona)) {
            throw new AuthorizationException('No tienes permisos para eliminar personas.');
        }

        if ($persona->users()->exists() || $persona->personalFincas()->exists()) {
            throw new \Exception('No se puede eliminar la persona porque tiene usuarios o está registrada como personal de finca. Desactívela en su lugar.');
        }

        $persona->delete();
        return true;
    }
}

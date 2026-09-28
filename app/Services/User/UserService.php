<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Persona;
use App\Models\Role;
use App\Models\Propietario;
use App\Models\Administrador;
use App\Models\Transcriptor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Access\AuthorizationException;

class UserService
{
    /**
     * Listar usuarios paginados con filtros
     */
    public function listUsers(array $filters, User $user)
    {
        if ($user->cannot('readAny', User::class)) {
            throw new AuthorizationException('No tienes permisos para ver usuarios.');
        }

        $query = User::with(['personas', 'roles', 'fincas']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('code', $filters['role']);
            });
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Si mandan nopaginate (como 'true' o 1), retornamos la colección completa
        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo usuario junto con su persona y rol
     */
    public function storeUser(array $data, User $adminUser)
    {
        if ($adminUser->cannot('create', User::class)) {
            throw new AuthorizationException('No tienes permisos para crear usuarios.');
        }

        $roleCodes = $data['roles'];
        $roles = Role::whereIn('code', $roleCodes)->get();
        if ($roles->count() !== count($roleCodes)) {
            throw new \Exception("Algunos roles especificados no existen.");
        }

        return DB::transaction(function () use ($data, $roles) {
            // Manejar Persona
            $persona = Persona::where('correo', $data['correo'])
                ->orWhere('cedula', $data['cedula'])
                ->first();

            if (!$persona) {
                $persona = Persona::create([
                    'cedula' => $data['cedula'],
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'telefono' => $data['telefono'] ?? null,
                    'correo' => $data['correo']
                ]);
            }

            if ($persona->users()->exists()) {
                throw new \Exception('Esta persona ya tiene una cuenta de usuario vinculada.');
            }

            // Crear el usuario con el mismo correo que la persona
            $user = User::create([
                'name' => $data['nombre'],
                'email' => $data['correo'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
            ]);

            // Vincular
            $user->personas()->attach($persona->id);
            $user->roles()->sync($roles->pluck('id')->toArray());

            // Entidades asociadas al rol
            foreach ($roles as $role) {
                if ($role->code === 'propietario') {
                    Propietario::firstOrCreate(['persona_id' => $persona->id]);
                } elseif (in_array($role->code, ['global_admin', 'admin'])) {
                    Administrador::firstOrCreate(['persona_id' => $persona->id]);
                }
            }

            return $user->load('personas', 'roles');
        });
    }

    /**
     * Obtener un usuario por ID
     */
    public function getUser($id, User $adminUser)
    {
        $user = User::with(['personas', 'roles.permissions', 'fincas'])->findOrFail($id);

        if ($adminUser->cannot('read', $user)) {
            throw new AuthorizationException('No tienes permisos para ver este usuario.');
        }

        return $user;
    }

    /**
     * Actualizar datos base del usuario y de su persona
     */
    public function updateUser($id, array $data, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('update', $user)) {
            throw new AuthorizationException('No tienes permisos para editar usuarios.');
        }

        return DB::transaction(function () use ($user, $data) {
            // Si cambian la contraseña
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (isset($data['status'])) {
                $user->status = $data['status'];
            }

            $persona = $user->personas()->first();

            // Si hay datos de la persona o correo que cambian
            if ($persona) {
                if (isset($data['nombre'])) $persona->nombre = $data['nombre'];
                if (isset($data['apellido'])) $persona->apellido = $data['apellido'];
                if (isset($data['cedula'])) $persona->cedula = $data['cedula'];
                if (isset($data['telefono'])) $persona->telefono = $data['telefono'];
                
                // Si cambia el correo, debe cambiar en ambos modelos
                if (isset($data['correo'])) {
                    $persona->correo = $data['correo'];
                    $user->email = $data['correo'];
                }
                
                $persona->save();
                
                // Si cambió el nombre, actualizamos el 'name' del user
                if (isset($data['nombre'])) {
                    $user->name = $persona->nombre;
                }
            }

            $user->save();

            // Manejar cambio de roles si se proveen
            if (isset($data['roles'])) {
                $roleCodes = $data['roles'];
                $roles = Role::whereIn('code', $roleCodes)->get();
                if ($roles->count() === count($roleCodes)) {
                    $user->roles()->sync($roles->pluck('id')->toArray());

                    if ($persona) {
                        $newRoleCodes = $roles->pluck('code')->toArray();

                        // Crear o mantener los que vienen en el array
                        if (in_array('propietario', $newRoleCodes)) {
                            Propietario::firstOrCreate(['persona_id' => $persona->id]);
                        } else {
                            // Eliminar si ya no es propietario, pero verificar primero
                            $propietario = Propietario::where('persona_id', $persona->id)->first();
                            if ($propietario) {
                                if ($propietario->fincas()->exists()) {
                                    throw new \Exception("No se puede quitar el rol de propietario porque tiene fincas registradas. Transfiéralas o elimínelas primero.");
                                }
                                $propietario->delete();
                            }
                        }

                        if (in_array('global_admin', $newRoleCodes) || in_array('admin', $newRoleCodes)) {
                            Administrador::firstOrCreate(['persona_id' => $persona->id]);
                        } else {
                            // Eliminar si ya no es administrador
                            Administrador::where('persona_id', $persona->id)->delete();
                        }
                    }
                }
            }

            return $user->load('personas', 'roles');
        });
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser($id, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('delete', $user)) {
            throw new AuthorizationException('No tienes permisos para eliminar usuarios.');
        }

        // Proteger contra la eliminación del último administrador global
        if ($user->hasRole('global_admin') && User::whereHas('roles', function($q) {
            $q->where('code', 'global_admin');
        })->count() <= 1) {
            throw new \Exception('No puedes eliminar al último administrador global.');
        }

        $persona = $user->personas()->first();

        return DB::transaction(function () use ($user, $persona) {
            // Si el usuario tiene perfil de propietario, eliminar en cascada sus fincas y registros relacionados
            if ($persona) {
                $propietario = Propietario::where('persona_id', $persona->id)->first();
                if ($propietario) {
                    // Eliminar cada finca del propietario con todas sus dependencias (terreno, rebaños, animales, etc.)
                    foreach ($propietario->fincas as $finca) {
                        if ($finca->terreno) {
                            $finca->terreno->delete();
                        }
                        $finca->users()->detach();
                        $finca->afiliaciones()->delete();
                        $finca->hierros()->delete();
                        $finca->personalFinca()->delete();
                        $finca->inventariosBufalo()->delete();
                        $finca->rebanos()->delete();
                        $finca->delete();
                    }

                    // Limpiar hierros y afiliaciones restantes del propietario y eliminar el registro de propietario
                    $propietario->hierros()->delete();
                    $propietario->afiliaciones()->delete();
                    $propietario->delete();
                }

                $administrador = Administrador::where('persona_id', $persona->id)->first();
                if ($administrador) {
                    $administrador->delete();
                }

                $transcriptor = Transcriptor::where('persona_id', $persona->id)->first();
                if ($transcriptor) {
                    $transcriptor->afiliaciones()->delete();
                    $transcriptor->delete();
                }
            }

            // Revocar tokens de sesión activos
            $user->tokens()->delete();

            // Desvincular usuario de todas las fincas asignadas (pivot finca_user, aplica a cualquier rol o trabajador)
            $user->fincas()->detach();

            // Desvincular de la persona (la persona física permanece registrada en la BD)
            $user->personas()->detach();

            // Desvincular roles y eliminar la cuenta de usuario
            $user->roles()->detach();
            $user->delete();

            return true;
        });
    }
    /**
     * Desactivar (borrado lógico) un usuario
     */
    public function disableUser($id, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('update', $user)) {
            throw new AuthorizationException('No tienes permisos para desactivar usuarios.');
        }

        if ($user->hasRole('global_admin') && User::whereHas('roles', function($q) {
            $q->where('code', 'global_admin');
        })->where('status', 'active')->count() <= 1) {
            throw new \Exception('No puedes desactivar al último administrador global activo.');
        }

        $user->update(['status' => 'suspended']);
        
        // Mantiene sus tokens de sesión para permitir autenticarse y consultar únicamente su perfil en la app
        return $user;
    }

    /**
     * Activar (restaurar lógicamente) un usuario
     */
    public function enableUser($id, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('update', $user)) {
            throw new AuthorizationException('No tienes permisos para activar usuarios.');
        }

        $user->update(['status' => 'active']);
        return $user;
    }
}

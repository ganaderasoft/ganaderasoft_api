<?php

namespace App\Services\User;

use App\Services\BaseService;
use App\Models\User;
use App\Models\Persona;
use App\Models\Role;
use App\Models\Administrador;
use App\Models\Propietario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Access\AuthorizationException;

class AuthService extends BaseService
{
    /**
     * Registra un nuevo usuario con su persona y rol asociados.
     * (Se asume que los usuarios creados con esta funcion son nuevos y no existen en la base de datos)
     * @param array $data Los datos del formulario de registro.
     * @param User $adminUser El administrador que ejecuta la acción.
     * @return User El usuario creado.
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function registerUser(array $data, User $adminUser): User
    {
        // 1. Autorización: Solo global_admin (o los que pasen UserPolicy@create) pueden registrar
        if (!$adminUser->can('create', User::class)) {
            throw new AuthorizationException('No tienes permisos para registrar usuarios.');
        }

        // 2. Extraer rol
        $roleCode = $data['role_code'];
        $role = Role::where('code', $roleCode)->first();
        if (!$role) {
            throw new \Exception("El rol especificado '{$roleCode}' no existe.");
        }

        // 3. Unificar datos redundantes (Nombre y Correo)
        // El usuario puede enviar 'name' o 'nombre', tomaremos 'nombre' y lo clonaremos.
        $nombre = $data['nombre'] ?? ($data['name'] ?? '');
        $correo = $data['correo'] ?? ($data['email'] ?? '');

        return DB::transaction(function () use ($data, $nombre, $correo, $role) {
            
            // 4. Crear Persona
            $persona = Persona::create([
                'cedula'           => $data['cedula'],
                'nombre'           => $nombre,
                'apellido'         => $data['apellido'] ?? '',
                'telefono'         => $data['telefono'] ?? null,
                'correo'           => $correo,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'status'           => 'active',
            ]);

            // 5. Crear Usuario
            $user = User::create([
                'name'     => $nombre,
                'email'    => $correo,
                'password' => Hash::make($data['password']),
                'status'   => 'active',
            ]);

            // 6. Enlazar Persona y Usuario
            $user->personas()->attach($persona->id);

            // 7. Asignar Rol
            $user->roles()->attach($role->id);

            // 8. Crear entidad específica si aplica
            if ($role->code === 'propietario') {
                Propietario::create(['persona_id' => $persona->id]);
            } elseif ($role->code === 'global_admin' || $role->code === 'admin') {
                Administrador::create(['persona_id' => $persona->id]);
            }

            // 9. Asignar Fincas (si se proveen)
            if (!empty($data['fincas']) && is_array($data['fincas'])) {
                foreach ($data['fincas'] as $fincaId) {
                    // Si mandan un array de objetos [['id' => 1]], extraemos el id. Si mandan un array de enteros [1], lo usamos directo.
                    $id = is_array($fincaId) ? $fincaId['id'] : $fincaId;
                    
                    $defaultAccess = in_array($role->code, ['propietario', 'admin', 'global_admin']) ? 'owner' : 'operator';
                    
                    $user->fincas()->attach($id, [
                        'access_level' => $defaultAccess,
                        'is_default' => false,
                        'status' => 'active'
                    ]);
                }
            }

            $user->load('roles', 'personas', 'fincas');
            
            return $user;
        });
    }
}

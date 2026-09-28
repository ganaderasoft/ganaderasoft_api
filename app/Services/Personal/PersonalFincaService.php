<?php

namespace App\Services\Personal;

use App\Models\PersonalFinca;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Services\BaseService;

class PersonalFincaService extends BaseService
{
    /**
     * Obtener lista de personal paginada.
     */
    public function listPersonal(array $filters, User $user)
    {
        if ($user->cannot('readAny', PersonalFinca::class)) {
            throw new AuthorizationException('Sin permisos para listar personal.');
        }

        $query = PersonalFinca::with(['finca', 'persona', 'tipoTrabajador']);

        $this->applyFincaFilter($query, $user, null);

        if (isset($filters['finca_id'])) {
            $query->forFinca($filters['finca_id']);
        }

        if (isset($filters['tipo_trabajador_id'])) {
            $query->byTipoTrabajador($filters['tipo_trabajador_id']);
        }

        if (isset($filters['nombre'])) {
            $query->byName($filters['nombre']);
        }

        $incluirInactivos = filter_var($filters['incluir_inactivos'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$incluirInactivos) {
            $status = strtolower(trim((string)($filters['status'] ?? 'activo')));
            $statusVal = in_array($status, ['inactivo', 'inactive', '0', 'false'], true) ? 'inactivo' : 'activo';
            $query->where('status', $statusVal);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo registro de personal.
     */
    public function storePersonal(array $data, User $user)
    {
        $fincaId = (int) $data['finca_id'];
        
        if ($user->cannot('create', [PersonalFinca::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para agregar personal a esta finca');
        }

        return DB::transaction(function () use ($data) {
            $persona = Persona::where('cedula', $data['cedula'])->first();

            $correo = $data['correo'] ?? $data['email'] ?? null;

            $personaData = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'correo' => $correo,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            ];

            if (!$persona) {
                $personaData['cedula'] = $data['cedula'];
                $personaData['status'] = 'activo';
                $persona = Persona::create($personaData);
            } else {
                $persona->update(array_filter($personaData, fn($val) => !is_null($val)));
            }

            $statusStr = 'activo';
            if (isset($data['status'])) {
                $statusStr = in_array(strtolower((string)$data['status']), ['0', 'false', 'inactivo'], true) ? 'inactivo' : 'activo';
            }

            $personalFinca = PersonalFinca::create([
                'finca_id' => $data['finca_id'],
                'persona_id' => $persona->id,
                'tipo_trabajador_id' => $data['tipo_trabajador_id'],
                'status' => $statusStr,
                'fecha_ingreso' => $data['fecha_ingreso'] ?? now()->toDateString(),
            ]);

            return $personalFinca->load(['finca', 'persona', 'tipoTrabajador']);
        });
    }

    /**
     * Obtener un registro específico de personal.
     */
    public function getPersonal(int $id, User $user)
    {
        $personal = PersonalFinca::with(['finca', 'persona', 'tipoTrabajador'])->findOrFail($id);

        if ($user->cannot('read', $personal)) {
            throw new AuthorizationException('No tiene permisos para ver este personal');
        }

        return $personal;
    }

    /**
     * Actualizar datos del personal.
     */
    public function updatePersonal(int $id, array $data, User $user)
    {
        $personal = PersonalFinca::findOrFail($id);

        if ($user->cannot('update', $personal)) {
            throw new AuthorizationException('No tiene permisos para editar este personal');
        }

        if (isset($data['finca_id']) && (int) $data['finca_id'] !== $personal->finca_id) {
            if ($user->cannot('create', [PersonalFinca::class, (int) $data['finca_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar personal a la nueva finca');
            }
        }

        return DB::transaction(function () use ($personal, $data) {
            if (isset($data['finca_id'])) $personal->finca_id = $data['finca_id'];
            if (isset($data['tipo_trabajador_id'])) $personal->tipo_trabajador_id = $data['tipo_trabajador_id'];
            if (isset($data['status'])) {
                $personal->status = in_array(strtolower((string)$data['status']), ['0', 'false', 'inactivo'], true) ? 'inactivo' : 'activo';
            }
            if (isset($data['fecha_ingreso'])) $personal->fecha_ingreso = $data['fecha_ingreso'];
            
            $personal->save();

            if (isset($data['nombre']) || isset($data['apellido']) || isset($data['telefono']) || isset($data['correo']) || isset($data['email']) || isset($data['cedula']) || isset($data['fecha_nacimiento'])) {
                $persona = $personal->persona;
                if ($persona) {
                    $personaData = [];
                    if (isset($data['nombre'])) $personaData['nombre'] = $data['nombre'];
                    if (isset($data['apellido'])) $personaData['apellido'] = $data['apellido'];
                    if (isset($data['telefono'])) $personaData['telefono'] = $data['telefono'];
                    if (isset($data['correo'])) $personaData['correo'] = $data['correo'];
                    if (isset($data['email'])) $personaData['correo'] = $data['email'];
                    if (isset($data['cedula'])) $personaData['cedula'] = $data['cedula'];
                    if (isset($data['fecha_nacimiento'])) $personaData['fecha_nacimiento'] = $data['fecha_nacimiento'];
                    
                    $persona->update($personaData);
                }
            }

            return $personal->load(['finca', 'persona', 'tipoTrabajador']);
        });
    }

    /**
     * Eliminar un registro de personal.
     */
    public function deletePersonal(int $id, User $user)
    {
        $personal = PersonalFinca::findOrFail($id);

        if ($user->cannot('delete', $personal)) {
            throw new AuthorizationException('No tiene permisos para eliminar este personal');
        }

        $personal->delete();
        
        return true;
    }

    /**
     * Enable personal de finca (status = 'activo').
     */
    public function enable(int $id, User $user): PersonalFinca
    {
        $personal = PersonalFinca::findOrFail($id);

        if ($user->cannot('update', $personal)) {
            throw new AuthorizationException('No tiene permisos para activar este personal');
        }

        $personal->status = 'activo';
        $personal->save();

        return $personal->load(['finca', 'persona', 'tipoTrabajador']);
    }

    /**
     * Disable personal de finca (status = 'inactivo').
     */
    public function disable(int $id, User $user): PersonalFinca
    {
        $personal = PersonalFinca::findOrFail($id);

        if ($user->cannot('update', $personal)) {
            throw new AuthorizationException('No tiene permisos para desactivar este personal');
        }

        $personal->status = 'inactivo';
        $personal->save();

        return $personal->load(['finca', 'persona', 'tipoTrabajador']);
    }

    /**
     * Convierte un personal de finca a usuario.
     */
    public function convertToUser(PersonalFinca $personalFinca, array $data, User $adminUser)
    {
        if ($adminUser->cannot('create', User::class)) {
            throw new AuthorizationException('No tienes permisos para crear usuarios.');
        }

        $persona = $personalFinca->persona;
        if (!$persona) {
            throw new \Exception('El personal no tiene una persona asociada.');
        }

        if ($persona->users()->exists()) {
            throw new \Exception('Esta persona ya tiene una cuenta de usuario vinculada.');
        }

        if (empty($persona->correo)) {
            throw new \Exception('La persona no tiene un correo asignado, es necesario para crear el usuario.');
        }

        $roleCodes = $data['roles'];
        $roles = Role::whereIn('code', $roleCodes)->get();
        if ($roles->count() !== count($roleCodes)) {
            throw new \Exception("Algunos roles especificados no existen.");
        }

        return DB::transaction(function () use ($persona, $roles, $data, $personalFinca) {
            $user = User::create([
                'name' => $persona->nombre,
                'email' => $persona->correo,
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);

            $user->personas()->attach($persona->id);
            $user->roles()->sync($roles->pluck('id')->toArray());

            // Crear entidades físicas si se le otorgan roles de propietario/administrador
            foreach ($roles as $role) {
                if ($role->code === 'propietario') {
                    Propietario::firstOrCreate(['persona_id' => $persona->id]);
                } elseif (in_array($role->code, ['global_admin', 'admin'])) {
                    Administrador::firstOrCreate(['persona_id' => $persona->id]);
                }
            }

            // Obtener todas las fincas en las que trabaja esta persona
            $fincasIds = PersonalFinca::where('persona_id', $persona->id)
                ->pluck('finca_id')
                ->unique();

            foreach ($fincasIds as $fincaId) {
                $user->fincas()->attach($fincaId, [
                    'access_level' => 'operator',
                    'is_default' => false,
                    'status' => 'active'
                ]);
            }

            return $user->load('roles', 'fincas');
        });
    }
}

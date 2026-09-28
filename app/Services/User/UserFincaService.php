<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\FincaUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;

class UserFincaService
{
    /**
     * Vincular una o múltiples fincas nuevas al usuario.
     */
    public function assignFincas($userId, array $fincas, User $adminUser)
    {
        $user = User::findOrFail($userId);

        if ($adminUser->cannot('create', FincaUser::class)) {
            throw new AuthorizationException('No tienes permisos para asignar fincas a usuarios.');
        }

        DB::transaction(function () use ($user, $fincas) {
            $hasNewDefault = false;
            foreach ($fincas as $fincaData) {
                if (isset($fincaData['is_default']) && $fincaData['is_default']) {
                    $hasNewDefault = true;
                    break;
                }
            }

            if ($hasNewDefault) {
                // Limpiamos los defaults anteriores porque el request trae un nuevo default que tiene prioridad
                DB::table('finca_user')
                    ->where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $defaultAssigned = false;

            foreach ($fincas as $fincaData) {
                $isDefault = $fincaData['is_default'] ?? false;
                
                // Asegurar que si mandan varias en true en este array, solo pase una
                if ($isDefault && $defaultAssigned) {
                    $isDefault = false;
                } elseif ($isDefault) {
                    $defaultAssigned = true;
                }

                if (!$user->fincas()->where('finca_id', $fincaData['id'])->exists()) {
                    $user->fincas()->attach($fincaData['id'], [
                        'access_level' => $fincaData['access_level'] ?? 'operator',
                        'is_default' => $isDefault,
                        'status' => $fincaData['status'] ?? 'active'
                    ]);
                }
            }
        });

        return current($user->fincas()->get()->toArray()); 
    }

    /**
     * Editar los permisos o el estado de una finca específica de este usuario.
     */
    public function updateFincaAccess($userId, $fincaId, array $data, User $adminUser)
    {
        $user = User::findOrFail($userId);

        if ($adminUser->cannot('update', FincaUser::class)) {
            throw new AuthorizationException('No tienes permisos para editar los accesos de los usuarios.');
        }

        if (!$user->fincas()->where('finca_id', $fincaId)->exists()) {
            throw new \Exception('El usuario no tiene asignada esta finca.');
        }

        DB::transaction(function () use ($user, $fincaId, $data) {
            $updateData = [];
            if (isset($data['access_level'])) $updateData['access_level'] = $data['access_level'];
            if (isset($data['is_default'])) $updateData['is_default'] = $data['is_default'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];

            if (!empty($updateData)) {
                $user->fincas()->updateExistingPivot($fincaId, $updateData);
            }

            if (isset($data['is_default']) && $data['is_default']) {
                // Si la están marcando explícitamente en true, quitamos true a las demás *antes* de enforceSingleDefault
                // para que enforceSingleDefault no elija aleatoriamente si hay colisión, 
                // sino que prevalezca esta.
                DB::table('finca_user')
                    ->where('user_id', $user->id)
                    ->where('finca_id', '!=', $fincaId)
                    ->update(['is_default' => false]);
            }
        });

        return true;
    }

    /**
     * Desvincular una finca específica del usuario.
     */
    public function removeFinca($userId, $fincaId, User $adminUser)
    {
        $user = User::findOrFail($userId);

        if ($adminUser->cannot('delete', FincaUser::class)) {
            throw new AuthorizationException('No tienes permisos para desvincular fincas de usuarios.');
        }

        if (!$user->fincas()->where('finca_id', $fincaId)->exists()) {
            throw new \Exception('El usuario no tiene asignada esta finca.');
        }

        $user->fincas()->detach($fincaId);

        return true;
    }

    /**
     * Desactivar (borrado lógico) la conexión de una finca específica del usuario.
     */
    public function disableAccess($userId, $fincaId, User $adminUser)
    {
        $user = User::findOrFail($userId);

        if ($adminUser->cannot('update', FincaUser::class)) {
            throw new AuthorizationException('No tienes permisos para desactivar accesos de usuarios.');
        }

        if (!$user->fincas()->where('finca_id', $fincaId)->exists()) {
            throw new \Exception('El usuario no tiene asignada esta finca.');
        }

        $user->fincas()->updateExistingPivot($fincaId, [
            'status' => 'inactive',
            'is_default' => false
        ]);

        return true;
    }

    /**
     * Activar (restaurar lógicamente) la conexión de una finca específica del usuario.
     */
    public function enableAccess($userId, $fincaId, User $adminUser)
    {
        $user = User::findOrFail($userId);

        if ($adminUser->cannot('update', FincaUser::class)) {
            throw new AuthorizationException('No tienes permisos para activar accesos de usuarios.');
        }

        if (!$user->fincas()->where('finca_id', $fincaId)->exists()) {
            throw new \Exception('El usuario no tiene asignada esta finca.');
        }

        $user->fincas()->updateExistingPivot($fincaId, [
            'status' => 'active'
        ]);

        return true;
    }
}

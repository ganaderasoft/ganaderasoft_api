<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService extends BaseService
{
    /**
     * Actualiza la foto de perfil del usuario autenticado.
     * Si ya tenía una foto previa en el disco, la elimina para evitar archivos huérfanos.
     *
     * @param UploadedFile $file Archivo de imagen subido.
     * @param User $user Usuario autenticado.
     * @return User Usuario actualizado con sus relaciones cargadas.
     */
    public function updatePhoto(UploadedFile $file, User $user): User
    {
        // 1. Eliminar foto anterior si existe
        if (!empty($user->foto) && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        // 2. Guardar la nueva foto en el directorio avatars del disco public
        $path = Storage::disk('public')->putFile('avatars', $file);

        // 3. Actualizar el registro del usuario
        $user->update(['foto' => $path]);

        // 4. Recargar relaciones para el recurso de respuesta
        $user->load(['roles.permissions', 'personas', 'personas.propietario.persona']);

        return $user;
    }

    /**
     * Elimina la foto de perfil del usuario autenticado.
     *
     * @param User $user Usuario autenticado.
     * @return User Usuario actualizado con foto en null.
     */
    public function deletePhoto(User $user): User
    {
        // 1. Eliminar el archivo físico si existe
        if (!empty($user->foto) && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        // 2. Limpiar el campo en base de datos
        $user->update(['foto' => null]);

        // 3. Recargar relaciones
        $user->load(['roles.permissions', 'personas', 'personas.propietario.persona']);

        return $user;
    }
}

<?php

namespace App\Http\Middleware\Legacy\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeProfile
{
    /**
     * Intercepta la respuesta del perfil para adaptarla al formato antiguo si es necesario.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isV2 = $request->header('X-API-VERSION') === '2';

        // 1. Entrada: Traducir lo que envía el front al back
        if (!$isV2) {
            $cleanedInput = $this->transformToCleanFormat($request->all());
            $request->replace($cleanedInput);
        }

        // 2. Ejecutar petición
        $response = $next($request);

        // 3. Salida: Traducir la respuesta al formato antiguo
        if (!$isV2 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']['user'])) {
                $content['data']['user'] = $this->transformToLegacyFormat($content['data']['user']);
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    /**
     * Convierte la petición antigua (V1) al formato limpio esperado por el controlador (V2).
     * En profile no se requieren cambios de entrada.
     */
    private function transformToCleanFormat(array $input): array
    {
        return $input;
    }

    /**
     * Adapta la respuesta del perfil al formato legacy (get_profile.txt).
     * Requiere consultas a la base de datos para obtener marcas de tiempo reales
     * y el objeto propietario completo del usuario.
     */
    private function transformToLegacyFormat(array $userData): array
    {
        $roles = $userData['roles'] ?? [];
        
        $typeUser = 'tecnico';
        if (in_array('global_admin', $roles) || in_array('admin', $roles)) {
            $typeUser = 'admin';
        } elseif (in_array('propietario', $roles)) {
            $typeUser = 'propietario';
        }

        $typeUserFormatted = ($typeUser === 'admin') ? 'admin' : ucfirst($typeUser);

        // Obtenemos los datos de propietario V2 y su persona anidada
        $propietarioV2 = $userData['propietario'] ?? null;
        $propietarioData = null;

        if ($propietarioV2) {
            $persona = $propietarioV2['persona'] ?? null;
            $propietarioData = [
                'id' => $propietarioV2['id'] ?? null,
                'id_Personal' => $persona && is_numeric($persona['cedula']) ? (int) $persona['cedula'] : ($persona['cedula'] ?? null),
                'Nombre' => $persona['nombre'] ?? '',
                'Apellido' => $persona['apellido'] ?? '',
                'Telefono' => $persona['telefono'] ?? '',
                'archivado' => $persona && ($persona['status'] === 'inactivo'),
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        return [
            'id' => $userData['id'] ?? null,
            'name' => $userData['name'] ?? '',
            'email' => $userData['email'] ?? '',
            'type_user' => $typeUserFormatted,
            'image' => 'user.png',
            'email_verified_at' => $userData['email_verified_at'] ?? null,
            'created_at' => $userData['created_at'] ?? null,
            'propietario' => $propietarioData,
        ];
    }
}

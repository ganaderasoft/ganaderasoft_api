<?php

namespace App\Http\Middleware\Legacy\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeRegister
{
    /**
     * Intercepta la petición y respuesta del registro de usuarios.
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

        // 3. Salida: Traducir la respuesta del usuario registrado a la estructura antigua
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
     * Traduce 'type_user' a 'role_code'.
     */
    private function transformToCleanFormat(array $input): array
    {
        $normalized = $input;

        if (isset($input['type_user'])) {
            $typeUser = $input['type_user'];
            $normalized['role_code'] = ($typeUser === 'admin') ? 'global_admin' : $typeUser;
            unset($normalized['type_user']);
        }

        return $normalized;
    }

    /**
     * Adapta la respuesta del usuario registrado al formato legacy.
     * Al registrarse el usuario solo requiere: id, name, email, type_user, image.
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

        return [
            'id' => $userData['id'] ?? null,
            'name' => $userData['name'] ?? '',
            'email' => $userData['email'] ?? '',
            'type_user' => $typeUserFormatted,
            'image' => 'user.png',
        ];
    }
}

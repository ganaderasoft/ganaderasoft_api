<?php

namespace App\Http\Middleware\Legacy\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeLogin
{
    /**
     * Intercepta la respuesta del login para adaptarla al formato antiguo si es necesario.
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
     * En login no se requieren cambios de entrada.
     */
    private function transformToCleanFormat(array $input): array
    {
        return $input;
    }

    /**
     * Adapta la respuesta del usuario registrado/logueado al formato legacy del login.
     * En login.txt el usuario solo requiere: id, name, email, type_user, image.
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

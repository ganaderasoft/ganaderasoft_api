<?php

namespace App\Http\Middleware\Legacy\Propietario;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndex
{
    /**
     * Intercepts the index response to flatten details to V1 legacy format.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isV2 = $request->header('X-API-VERSION') === '2';

        $response = $next($request);

        if (!$isV2 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']['data']) && is_array($content['data']['data'])) {
                foreach ($content['data']['data'] as $key => $item) {
                    $content['data']['data'][$key] = $this->transformToLegacyFormat($item);
                }
            } elseif (isset($content['data']) && is_array($content['data'])) {
                foreach ($content['data'] as $key => $item) {
                    $content['data'][$key] = $this->transformToLegacyFormat($item);
                }
            }

            $response->setContent(json_encode($content));
        }

        return $response;
    }

    private function transformToLegacyFormat(array $propietario): array
    {
        $persona = $propietario['persona'] ?? null;
        $users = $persona['users'] ?? [];
        $firstUser = count($users) > 0 ? $users[0] : null;

        $legacyId = $firstUser ? $firstUser['id'] : ($propietario['id'] ?? null);
        $cedula = $persona['cedula'] ?? '';
        $idPersonal = is_numeric($cedula) ? (int) $cedula : (int) preg_replace('/[^0-9]/', '', $cedula);

        $legacyUser = $firstUser ? [
            'id' => $firstUser['id'],
            'name' => $firstUser['name'] ?? '',
            'email' => $firstUser['email'] ?? '',
        ] : null;

        return [
            'id' => $legacyId,
            'id_Personal' => $idPersonal ?: null,
            'Nombre' => $persona['nombre'] ?? '',
            'Apellido' => $persona['apellido'] ?? '',
            'Telefono' => $persona['telefono'] ?? null,
            'archivado' => isset($persona['status']) ? $persona['status'] !== 'activo' : false,
            'created_at' => $persona['created_at'] ?? ($propietario['created_at'] ?? null),
            'updated_at' => $persona['updated_at'] ?? ($propietario['updated_at'] ?? null),
            'user' => $legacyUser,
            'fincas' => $propietario['fincas'] ?? [],
        ];
    }
}

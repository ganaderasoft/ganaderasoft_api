<?php

namespace App\Http\Middleware\Legacy\Propietario;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdate
{
    /**
     * Intercepts request and response for update to translate V1 <-> V2 formats.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isV2 = $request->header('X-API-VERSION') === '2';

        if (!$isV2) {
            $request->replace($this->transformToCleanFormat($request->all()));
        }

        $response = $next($request);

        if (!$isV2 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']) && is_array($content['data'])) {
                $content['data'] = $this->transformToLegacyFormat($content['data']);
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        $normalized = $input;
        
        if (isset($input['Nombre'])) {
            $normalized['nombre'] = $input['Nombre'];
            unset($normalized['Nombre']);
        }
        if (isset($input['Apellido'])) {
            $normalized['apellido'] = $input['Apellido'];
            unset($normalized['Apellido']);
        }
        if (isset($input['Telefono'])) {
            $normalized['telefono'] = $input['Telefono'];
            unset($normalized['Telefono']);
        }
        if (isset($input['id_Personal'])) {
            $idPersonal = $input['id_Personal'];
            if (is_numeric($idPersonal)) {
                $normalized['cedula'] = 'V' . $idPersonal;
            } else {
                $normalized['cedula'] = $idPersonal;
            }
            unset($normalized['id_Personal']);
        }

        return $normalized;
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

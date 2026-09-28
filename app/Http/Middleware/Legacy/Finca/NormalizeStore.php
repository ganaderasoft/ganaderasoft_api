<?php

namespace App\Http\Middleware\Legacy\Finca;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStore
{
    /**
     * Intercepts request and response for store to translate V1 <-> V2 formats.
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
                $content['data'] = $this->transformFincaToLegacyFormat($content['data']);
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
        if (isset($input['Explotacion_Tipo'])) {
            $normalized['explotacion_tipo'] = $input['Explotacion_Tipo'];
            unset($normalized['Explotacion_Tipo']);
        }
        if (isset($input['id_Propietario'])) {
            $normalized['propietario_id'] = $input['id_Propietario'];
            unset($normalized['id_Propietario']);
        }

        if (isset($input['terreno']) && is_array($input['terreno'])) {
            $terreno = $input['terreno'];
            $normalizedTerreno = $terreno;

            $mapping = [
                'Superficie' => 'superficie',
                'Relieve' => 'relieve',
                'Suelo_Textura' => 'suelo_textura',
                'ph_Suelo' => 'ph_suelo',
                'Precipitacion' => 'precipitacion',
                'Velocidad_Viento' => 'velocidad_viento',
                'Temp_Anual' => 'temp_anual',
                'Temp_Min' => 'temp_min',
                'Temp_Max' => 'temp_max',
                'Radiacion' => 'radiacion',
                'Fuente_Agua' => 'fuente_agua',
                'Caudal_Disponible' => 'caudal_disponible',
                'Riego_Metodo' => 'riego_metodo',
            ];

            foreach ($mapping as $legacyKey => $cleanKey) {
                if (array_key_exists($legacyKey, $terreno)) {
                    $normalizedTerreno[$cleanKey] = $terreno[$legacyKey];
                    unset($normalizedTerreno[$legacyKey]);
                }
            }
            $normalized['terreno'] = $normalizedTerreno;
        }

        return $normalized;
    }

    private function transformFincaToLegacyFormat(array $finca): array
    {
        $legacy = [
            'id_Finca' => $finca['id'] ?? null,
            'id_Propietario' => $finca['propietario_id'] ?? ($finca['propietario']['id'] ?? null),
            'Nombre' => $finca['nombre'] ?? '',
            'Explotacion_Tipo' => $finca['explotacion_tipo'] ?? '',
            'archivado' => $finca['archivado'] ?? false,
            'created_at' => $finca['created_at'] ?? null,
            'updated_at' => $finca['updated_at'] ?? null,
        ];

        if (array_key_exists('propietario', $finca) && is_array($finca['propietario'])) {
            $legacy['propietario'] = $this->transformPropietarioToLegacyFormat($finca['propietario']);
        }

        if (array_key_exists('terreno', $finca) && is_array($finca['terreno'])) {
            $terreno = $finca['terreno'];
            $legacy['terreno'] = [
                'id_Terreno' => $terreno['id'] ?? null,
                'id_Finca' => $terreno['finca_id'] ?? null,
                'Superficie' => $terreno['superficie'] ?? null,
                'Relieve' => $terreno['relieve'] ?? null,
                'Suelo_Textura' => $terreno['suelo_textura'] ?? null,
                'ph_Suelo' => $terreno['ph_suelo'] ?? null,
                'Precipitacion' => $terreno['precipitacion'] ?? null,
                'Velocidad_Viento' => $terreno['velocidad_viento'] ?? null,
                'Temp_Anual' => $terreno['temp_anual'] ?? null,
                'Temp_Min' => $terreno['temp_min'] ?? null,
                'Temp_Max' => $terreno['temp_max'] ?? null,
                'Radiacion' => $terreno['radiacion'] ?? null,
                'Fuente_Agua' => $terreno['fuente_agua'] ?? null,
                'Caudal_Disponible' => $terreno['caudal_disponible'] ?? null,
                'Riego_Metodo' => $terreno['riego_metodo'] ?? null,
                'created_at' => $terreno['created_at'] ?? null,
                'updated_at' => $terreno['updated_at'] ?? null,
            ];
        }

        return $legacy;
    }

    private function transformPropietarioToLegacyFormat(array $propietario): array
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

<?php

namespace App\Http\Middleware\Legacy\Terreno;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeShow
{
    /**
     * Intercepts the show response to translate details to V1 legacy format.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isV2 = $request->header('X-API-VERSION') === '2';

        $response = $next($request);

        if (!$isV2 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']) && is_array($content['data'])) {
                $content['data'] = $this->transformTerrenoToLegacyFormat($content['data']);
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    private function transformTerrenoToLegacyFormat(array $terreno): array
    {
        $legacy = [
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

        if (array_key_exists('finca', $terreno) && is_array($terreno['finca'])) {
            $finca = $terreno['finca'];
            $legacy['finca'] = [
                'id_Finca' => $finca['id'] ?? null,
                'id_Propietario' => $finca['propietario_id'] ?? null,
                'Nombre' => $finca['nombre'] ?? '',
                'Explotacion_Tipo' => $finca['explotacion_tipo'] ?? '',
                'archivado' => $finca['archivado'] ?? false,
                'created_at' => $finca['created_at'] ?? null,
                'updated_at' => $finca['updated_at'] ?? null,
            ];
        }

        return $legacy;
    }
}

<?php

namespace App\Http\Middleware\Legacy\Rebano;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndex
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process successful JSON responses for legacy API
        if ($response->isSuccessful() && $request->header('X-API-VERSION') != '2') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']) && isset($content['data']['data'])) {
                // Paginated response
                $items = $content['data']['data'];
                $mappedItems = array_map([$this, 'mapToLegacy'], $items);
                $content['data']['data'] = $mappedItems;
            } elseif (isset($content['data']) && is_array($content['data'])) {
                // Simple array response (just in case)
                if (isset($content['data'][0])) {
                    $content['data'] = array_map([$this, 'mapToLegacy'], $content['data']);
                }
            }

            $response->setContent(json_encode($content));
        }

        return $response;
    }

    private function mapToLegacy(array $item)
    {
        $mapped = [
            'id_Rebano' => $item['id'],
            'id_Finca' => $item['finca_id'] ?? ($item['finca']['id'] ?? null),
            'Nombre' => $item['nombre'],
            'archivado' => $item['archivado'],
        ];

        if (isset($item['finca'])) {
            $finca = $item['finca'];
            $mapped['finca'] = [
                'id_Finca' => $finca['id'],
                'id_Propietario' => $finca['propietario_id'] ?? ($finca['propietario']['id'] ?? null),
                'Nombre' => $finca['nombre'],
                'Explotacion_Tipo' => $finca['explotacion_tipo'],
                'archivado' => $finca['archivado'],
            ];

            if (isset($finca['propietario'])) {
                // Assuming we might need this if included in response
                $mapped['finca']['propietario'] = $finca['propietario'];
            }
        }

        if (isset($item['animales'])) {
            $mapped['animales'] = array_map(function ($animal) {
                return [
                    'id_Animal' => $animal['id'],
                    'id_Rebano' => $animal['rebano_id'],
                    'Nombre' => $animal['nombre'],
                    'codigo_animal' => $animal['codigo_animal'],
                    'Sexo' => $animal['sexo'],
                    'fecha_nacimiento' => $animal['fecha_nacimiento'],
                    'Procedencia' => $animal['procedencia'],
                    'archivado' => $animal['archivado'],
                ];
            }, $item['animales']);
        }

        return $mapped;
    }
}

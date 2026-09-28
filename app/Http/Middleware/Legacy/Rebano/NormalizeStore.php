<?php

namespace App\Http\Middleware\Legacy\Rebano;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStore
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') != '2') {
            $data = $request->all();
            $mappedData = [];

            if (isset($data['id_Finca'])) {
                $mappedData['finca_id'] = $data['id_Finca'];
            }
            if (isset($data['Nombre'])) {
                $mappedData['nombre'] = $data['Nombre'];
            }

            if (!empty($mappedData)) {
                $request->merge($mappedData);
            }
        }

        $response = $next($request);

        if ($response->isSuccessful() && $request->header('X-API-VERSION') != '2') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']) && is_array($content['data']) && isset($content['data']['id'])) {
                $content['data'] = $this->mapToLegacy($content['data']);
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    private function mapToLegacy(array $item)
    {
        $mapped = [
            'id_Rebano' => $item['id'],
            'id_Finca' => $item['finca_id'] ?? null,
            'Nombre' => $item['nombre'],
            'archivado' => $item['archivado'],
        ];

        if (isset($item['finca'])) {
            $mapped['id_Finca'] = $item['finca']['id'];
            $finca = $item['finca'];
            $mapped['finca'] = [
                'id_Finca' => $finca['id'],
                'id_Propietario' => $finca['propietario_id'] ?? ($finca['propietario']['id'] ?? null),
                'Nombre' => $finca['nombre'],
                'Explotacion_Tipo' => $finca['explotacion_tipo'],
                'archivado' => $finca['archivado'],
            ];
        }

        if (isset($item['animales'])) {
            $mapped['animales'] = []; // Store may not have animals loaded, but if so, it's empty
        }

        return $mapped;
    }
}

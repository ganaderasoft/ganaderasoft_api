<?php

namespace App\Http\Middleware\Legacy\Produccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeShowLactancia
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data']) && is_array($data['data'])) {
                $data['data'] = $this->mapToLegacy($data['data']);
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }

    private function mapToLegacy(array $item): array
    {
        $legacy = [
            'lactancia_id'           => $item['id'] ?? null,
            'lactancia_fecha_inicio' => $item['fecha_inicio'] ?? null,
            'Lactancia_fecha_fin'    => $item['fecha_fin'] ?? null,
            'lactancia_secado'       => $item['secado'] ?? null,
            'lactancia_etapa_anid'   => $item['animal']['id'] ?? ($item['etapa_animal']['animal_id'] ?? null),
            'lactancia_etapa_etid'   => $item['etapa']['id'] ?? ($item['etapa_animal']['etapa_id'] ?? null),
            'animal_etapa_id'        => $item['animal_etapa_id'] ?? null,
            'created_at'             => $item['created_at'] ?? null,
            'updated_at'             => $item['updated_at'] ?? null,
            'animal'                 => $item['animal'] ?? null,
            'etapa'                  => $item['etapa'] ?? null,
            'etapa_animal'           => $item['etapa_animal'] ?? null,
            'leche_records'          => $item['leche_records'] ?? null,
        ];

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}

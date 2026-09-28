<?php

namespace App\Http\Middleware\Legacy\Reproduccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeShowReproduccionAnimal
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
            'repro_id'                 => $item['id'] ?? null,
            'repro_fecha_reproduccion' => $item['fecha_reproduccion'] ?? null,
            'repro_tipo_reproduccion'  => $item['tipo_reproduccion'] ?? null,
            'repro_observacion'        => $item['observacion'] ?? null,
            'repro_etapa_anid'         => null,
            'repro_etapa_etid'         => null,
            'created_at'               => $item['created_at'] ?? null,
            'updated_at'               => $item['updated_at'] ?? null,
            'animal'                   => $item['animal'] ?? null,
            'etapa'                    => $item['etapa'] ?? null,
            'etapaAnimal'              => $item['etapa_animal'] ?? ($item['etapaAnimal'] ?? null),
        ];

        if (isset($item['etapa_animal']) && is_array($item['etapa_animal'])) {
            $legacy['repro_etapa_anid'] = $item['etapa_animal']['animal_id'] ?? null;
            $legacy['repro_etapa_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
        } elseif (isset($item['animal']) && is_array($item['animal'])) {
            $legacy['repro_etapa_anid'] = $item['animal']['id'] ?? null;
            if (isset($item['etapa']) && is_array($item['etapa'])) {
                $legacy['repro_etapa_etid'] = $item['etapa']['id'] ?? null;
            }
        }

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}

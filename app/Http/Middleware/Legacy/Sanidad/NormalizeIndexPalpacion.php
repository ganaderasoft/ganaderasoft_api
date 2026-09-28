<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexPalpacion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $cleanedInput = $this->transformToCleanFormat($request->all());
        $cleanedInput['nopaginate'] = true;
        $request->replace($cleanedInput);

        $response = $next($request);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (isset($data['data'])) {
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    $data['data']['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']['data']);
                } else {
                    $data['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']);
                }
                $response->setData($data);
            }
        }

        return $response;
    }

    /**
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        return $input;
    }

    /**
     * Transforma un recurso V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $legacy = [
            'palpacion_id'         => $item['id'],
            'id_Tecnico'           => $item['personal_finca_id'] ?? null,
            'palpacion_tipo'       => $item['tipo'] ?? null,
            'palpacion_fecha'      => $item['fecha'] ?? null,
            'palpacion_etapa_anid' => null,
            'palpacion_etapa_etid' => null,
            'created_at'           => $item['created_at'] ?? null,
            'updated_at'           => $item['updated_at'] ?? null,
        ];

        if (isset($item['etapa_animal'])) {
            $legacy['palpacion_etapa_anid'] = $item['etapa_animal']['animal_id'] ?? null;
            $legacy['palpacion_etapa_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
        }

        return $legacy;
    }
}

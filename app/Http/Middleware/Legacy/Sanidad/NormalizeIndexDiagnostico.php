<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexDiagnostico
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
        $cleanedInput['nopaginate'] = true; // Forzar a devolver listado completo sin paginador
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
     * Transforma un diagnostico V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $legacy = [
            'diagnostico_id'          => $item['id'],
            'diagnostico_descripcion' => $item['descripcion'] ?? null,
            'diagnostico_tipo'        => $item['tipo'] ?? null,
            'diagnostico_fecha'       => $item['fecha'] ?? null,
            'fk_etapa_animal_anid'    => null,
            'fk_etapa_animal_etid'    => null,
            'created_at'              => $item['created_at'] ?? null,
            'updated_at'              => $item['updated_at'] ?? null,
        ];

        if (isset($item['etapa_animal'])) {
            $legacy['fk_etapa_animal_anid'] = $item['etapa_animal']['animal_id'] ?? null;
            $legacy['fk_etapa_animal_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
        }

        return $legacy;
    }
}

<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeShowDiagnostico
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (isset($data['data'])) {
                $data['data'] = $this->transformToLegacyFormat($data['data']);
                $response->setData($data);
            }
        }

        return $response;
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

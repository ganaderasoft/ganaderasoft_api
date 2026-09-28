<?php

namespace App\Http\Middleware\Legacy\Rebano;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateMovimientoRebano
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
        $request->replace($cleanedInput);

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
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        $clean = [];

        if (array_key_exists('Rebano_Destino', $input)) {
            $clean['rebano_destino'] = $input['Rebano_Destino'];
        }

        if (array_key_exists('Comentario', $input)) {
            $clean['comentario'] = $input['Comentario'];
        }

        return $clean;
    }

    /**
     * Transforma un registro de movimiento de rebaño V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        return [
            'id_Movimiento'     => $item['id'],
            'id_Finca'          => $item['finca_id'],
            'id_Rebano'         => $item['rebano_id'],
            'Rebano_Destino'    => $item['rebano_destino'] ?? null,
            'id_Finca_Destino'  => $item['finca_destino_id'],
            'id_Rebano_Destino' => $item['rebano_destino_id'],
            'Comentario'        => $item['comentario'] ?? null,
            'created_at'        => $item['created_at'] ?? null,
            'updated_at'        => $item['updated_at'] ?? null,
        ];
    }
}

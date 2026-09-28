<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreEstadoSalud
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
        return [
            'nombre' => $input['estado_nombre'] ?? null,
        ];
    }

    /**
     * Transforma un estado de salud V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        return [
            'estado_id'     => $item['id'],
            'estado_nombre' => $item['nombre'],
            'created_at'    => $item['created_at'] ?? null,
            'updated_at'    => $item['updated_at'] ?? null,
        ];
    }
}

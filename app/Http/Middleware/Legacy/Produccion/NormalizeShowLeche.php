<?php

namespace App\Http\Middleware\Legacy\Produccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeShowLeche
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
            'leche_id'           => $item['id'] ?? null,
            'leche_fecha_pesaje' => $item['fecha_pesaje'] ?? null,
            'leche_pesaje_Total' => $item['pesaje_total'] ?? null,
            'leche_lactancia_id' => $item['lactancia_id'] ?? null,
            'created_at'         => $item['created_at'] ?? null,
            'updated_at'         => $item['updated_at'] ?? null,
            'lactancia'          => $item['lactancia'] ?? null,
            'animal'             => $item['animal'] ?? null,
        ];

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}

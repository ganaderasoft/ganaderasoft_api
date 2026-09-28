<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexVacuna
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge(['nopaginate' => true]);
        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    // Paginado
                    $data['data']['data'] = array_map(function ($item) {
                        return $this->mapToLegacy($item);
                    }, $data['data']['data']);
                } elseif (is_array($data['data'])) {
                    // No paginado
                    $data['data'] = array_map(function ($item) {
                        return $this->mapToLegacy($item);
                    }, $data['data']);
                }
                
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }

    private function mapToLegacy(array $item): array
    {
        $legacy = [
            'vacuna_id'          => $item['id'] ?? null,
            'vacuna_nombre'      => $item['nombre'] ?? null,
            'vacuna_descripcion' => $item['descripcion'] ?? null,
            'activa'             => $item['activa'] ?? null,
            'casas_comerciales'  => $item['casas_comerciales'] ?? null,
            'dosis'              => $item['dosis'] ?? null,
        ];

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}

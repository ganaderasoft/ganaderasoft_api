<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexTratamiento
{
    /**
     * Handle an incoming request.
     * Convierte el formato V2 de un listado de Tratamientos (paginado o no) al formato V1 legacy.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    // It's paginated
                    $data['data']['data'] = array_map(function ($item) {
                        return $this->mapToLegacy($item);
                    }, $data['data']['data']);
                } elseif (is_array($data['data'])) {
                    // Not paginated
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
            'tratamiento_id'             => $item['id'] ?? null,
            'tratamiento_plan'           => $item['plan'] ?? null,
            'tratamiento_fecha_ini'      => $item['fecha_ini'] ?? null,
            'tratamiento_fecha_fin'      => $item['fecha_fin'] ?? null,
            'tratamiento_diagnostico_id' => $item['diagnostico_id'] ?? null,
            
            // Datos relacionados si existieran
            'diagnostico'                => $item['diagnostico'] ?? null,
        ];

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}

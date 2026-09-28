<?php

namespace App\Http\Middleware\Legacy\Reproduccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexServicioAnimal
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
            'servicio_id'          => $item['id'] ?? null,
            'servicio_id_Animal'   => $item['animal_id'] ?? null,
            'servicio_semen_id'    => $item['semen_toro_id'] ?? null,
            'servicio_id_Tecnico'  => $item['personal_finca_id'] ?? null,
            'servicio_celo_id'     => $item['registro_celo_id'] ?? null,
            'servicio_tipo'        => $item['tipo'] ?? null,
            'servicio_fecha'       => $item['fecha'] ?? null,
            'servicio_observacion' => $item['observacion'] ?? null,
            'created_at'           => $item['created_at'] ?? null,
            'updated_at'           => $item['updated_at'] ?? null,
            'animal'               => $item['animal'] ?? null,
            'semen'                => $item['semen'] ?? null,
            'tecnico'              => $item['tecnico'] ?? null,
            'registroCelo'         => $item['registro_celo'] ?? ($item['registroCelo'] ?? null),
        ];

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}

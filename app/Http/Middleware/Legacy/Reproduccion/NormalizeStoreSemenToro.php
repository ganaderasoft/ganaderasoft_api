<?php

namespace App\Http\Middleware\Legacy\Reproduccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreSemenToro
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('id_Toro', $input)) $payload['animal_id'] = $input['id_Toro'];
            elseif (array_key_exists('toro_id', $input)) $payload['animal_id'] = $input['toro_id'];
            elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

            if (array_key_exists('semen_estado', $input)) $payload['estado'] = $input['semen_estado'];
            elseif (array_key_exists('estado', $input)) $payload['estado'] = $input['estado'];

            if (array_key_exists('semen_fecha', $input)) $payload['fecha'] = $input['semen_fecha'];
            elseif (array_key_exists('fecha', $input)) $payload['fecha'] = $input['fecha'];

            if (array_key_exists('cantidad_pajuelas', $input)) $payload['cantidad_pajuelas'] = $input['cantidad_pajuelas'];

            $request->replace($payload);
        }

        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        if ($response->getStatusCode() === 422) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['errors'])) {
                $errorMapping = [
                    'animal_id' => 'id_Toro',
                    'toro_id'   => 'id_Toro',
                    'estado'    => 'semen_estado',
                    'fecha'     => 'semen_fecha',
                ];

                $legacyErrors = [];
                foreach ($content['errors'] as $key => $messages) {
                    $newKey = $errorMapping[$key] ?? $key;
                    $legacyErrors[$newKey] = $messages;
                }
                $content['errors'] = $legacyErrors;
                $response->setContent(json_encode($content));
            }
            return $response;
        }

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);
            if (isset($data['data']) && is_array($data['data'])) {
                $item = $data['data'];
                $legacy = [
                    'semen_id'          => $item['id'] ?? null,
                    'id_Toro'           => $item['animal_id'] ?? ($item['toro']['id'] ?? null),
                    'semen_estado'      => $item['estado'] ?? null,
                    'semen_fecha'       => $item['fecha'] ?? null,
                    'cantidad_pajuelas' => $item['cantidad_pajuelas'] ?? null,
                    'created_at'        => $item['created_at'] ?? null,
                    'updated_at'        => $item['updated_at'] ?? null,
                    'toro'              => $item['toro'] ?? null,
                    'servicios'         => $item['servicios'] ?? null,
                ];
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

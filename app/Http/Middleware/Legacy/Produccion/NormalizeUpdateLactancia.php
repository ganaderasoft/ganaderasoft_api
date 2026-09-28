<?php

namespace App\Http\Middleware\Legacy\Produccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateLactancia
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('lactancia_fecha_inicio', $input)) $payload['fecha_inicio'] = $input['lactancia_fecha_inicio'];
            elseif (array_key_exists('fecha_inicio', $input)) $payload['fecha_inicio'] = $input['fecha_inicio'];

            if (array_key_exists('Lactancia_fecha_fin', $input)) $payload['fecha_fin'] = $input['Lactancia_fecha_fin'];
            elseif (array_key_exists('lactancia_fecha_fin', $input)) $payload['fecha_fin'] = $input['lactancia_fecha_fin'];
            elseif (array_key_exists('fecha_fin', $input)) $payload['fecha_fin'] = $input['fecha_fin'];

            if (array_key_exists('lactancia_secado', $input)) $payload['secado'] = $input['lactancia_secado'];
            elseif (array_key_exists('secado', $input)) $payload['secado'] = $input['secado'];

            if (array_key_exists('lactancia_etapa_anid', $input)) $payload['animal_id'] = $input['lactancia_etapa_anid'];
            elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

            if (array_key_exists('lactancia_etapa_etid', $input)) $payload['etapa_id'] = $input['lactancia_etapa_etid'];
            elseif (array_key_exists('etapa_id', $input)) $payload['etapa_id'] = $input['etapa_id'];

            if (array_key_exists('animal_etapa_id', $input)) $payload['animal_etapa_id'] = $input['animal_etapa_id'];

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
                    'fecha_inicio'    => 'lactancia_fecha_inicio',
                    'fecha_fin'       => 'Lactancia_fecha_fin',
                    'secado'          => 'lactancia_secado',
                    'animal_id'       => 'lactancia_etapa_anid',
                    'etapa_id'        => 'lactancia_etapa_etid',
                    'animal_etapa_id' => 'lactancia_etapa_anid',
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
                    'lactancia_id'           => $item['id'] ?? null,
                    'lactancia_fecha_inicio' => $item['fecha_inicio'] ?? null,
                    'Lactancia_fecha_fin'    => $item['fecha_fin'] ?? null,
                    'lactancia_secado'       => $item['secado'] ?? null,
                    'lactancia_etapa_anid'   => $item['animal']['id'] ?? ($item['etapa_animal']['animal_id'] ?? null),
                    'lactancia_etapa_etid'   => $item['etapa']['id'] ?? ($item['etapa_animal']['etapa_id'] ?? null),
                    'animal_etapa_id'        => $item['animal_etapa_id'] ?? null,
                    'created_at'             => $item['created_at'] ?? null,
                    'updated_at'             => $item['updated_at'] ?? null,
                    'animal'                 => $item['animal'] ?? null,
                    'etapa'                  => $item['etapa'] ?? null,
                    'etapa_animal'           => $item['etapa_animal'] ?? null,
                    'leche_records'          => $item['leche_records'] ?? null,
                ];
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

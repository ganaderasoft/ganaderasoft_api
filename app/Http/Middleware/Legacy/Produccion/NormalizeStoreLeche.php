<?php

namespace App\Http\Middleware\Legacy\Produccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreLeche
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('leche_fecha_pesaje', $input)) $payload['fecha_pesaje'] = $input['leche_fecha_pesaje'];
            elseif (array_key_exists('fecha_pesaje', $input)) $payload['fecha_pesaje'] = $input['fecha_pesaje'];

            if (array_key_exists('leche_pesaje_Total', $input)) $payload['pesaje_total'] = $input['leche_pesaje_Total'];
            elseif (array_key_exists('leche_pesaje_total', $input)) $payload['pesaje_total'] = $input['leche_pesaje_total'];
            elseif (array_key_exists('pesaje_total', $input)) $payload['pesaje_total'] = $input['pesaje_total'];

            if (array_key_exists('leche_lactancia_id', $input)) $payload['lactancia_id'] = $input['leche_lactancia_id'];
            elseif (array_key_exists('lactancia_id', $input)) $payload['lactancia_id'] = $input['lactancia_id'];

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
                    'fecha_pesaje' => 'leche_fecha_pesaje',
                    'pesaje_total' => 'leche_pesaje_Total',
                    'lactancia_id' => 'leche_lactancia_id',
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
                    'leche_id'           => $item['id'] ?? null,
                    'leche_fecha_pesaje' => $item['fecha_pesaje'] ?? null,
                    'leche_pesaje_Total' => $item['pesaje_total'] ?? null,
                    'leche_lactancia_id' => $item['lactancia_id'] ?? null,
                    'created_at'         => $item['created_at'] ?? null,
                    'updated_at'         => $item['updated_at'] ?? null,
                    'lactancia'          => $item['lactancia'] ?? null,
                    'animal'             => $item['animal'] ?? null,
                ];
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

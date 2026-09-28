<?php

namespace App\Http\Middleware\Legacy\Reproduccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateReproduccionAnimal
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('repro_fecha_reproduccion', $input)) $payload['fecha_reproduccion'] = $input['repro_fecha_reproduccion'];
            elseif (array_key_exists('fecha_reproduccion', $input)) $payload['fecha_reproduccion'] = $input['fecha_reproduccion'];

            if (array_key_exists('repro_tipo_reproduccion', $input)) $payload['tipo_reproduccion'] = $input['repro_tipo_reproduccion'];
            elseif (array_key_exists('tipo_reproduccion', $input)) $payload['tipo_reproduccion'] = $input['tipo_reproduccion'];

            if (array_key_exists('repro_observacion', $input)) $payload['observacion'] = $input['repro_observacion'];
            elseif (array_key_exists('observacion', $input)) $payload['observacion'] = $input['observacion'];

            if (array_key_exists('repro_etapa_anid', $input)) $payload['animal_id'] = $input['repro_etapa_anid'];
            elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

            if (array_key_exists('repro_etapa_etid', $input)) $payload['etapa_id'] = $input['repro_etapa_etid'];
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
                    'fecha_reproduccion' => 'repro_fecha_reproduccion',
                    'tipo_reproduccion'  => 'repro_tipo_reproduccion',
                    'observacion'        => 'repro_observacion',
                    'animal_id'          => 'repro_etapa_anid',
                    'etapa_id'           => 'repro_etapa_etid',
                    'animal_etapa_id'    => 'repro_etapa_anid',
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
                    'repro_id'                 => $item['id'] ?? null,
                    'repro_fecha_reproduccion' => $item['fecha_reproduccion'] ?? null,
                    'repro_tipo_reproduccion'  => $item['tipo_reproduccion'] ?? null,
                    'repro_observacion'        => $item['observacion'] ?? null,
                    'repro_etapa_anid'         => null,
                    'repro_etapa_etid'         => null,
                    'created_at'               => $item['created_at'] ?? null,
                    'updated_at'               => $item['updated_at'] ?? null,
                    'animal'                   => $item['animal'] ?? null,
                    'etapa'                    => $item['etapa'] ?? null,
                    'etapaAnimal'              => $item['etapa_animal'] ?? ($item['etapaAnimal'] ?? null),
                ];
                if (isset($item['etapa_animal']) && is_array($item['etapa_animal'])) {
                    $legacy['repro_etapa_anid'] = $item['etapa_animal']['animal_id'] ?? null;
                    $legacy['repro_etapa_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
                } elseif (isset($item['animal']) && is_array($item['animal'])) {
                    $legacy['repro_etapa_anid'] = $item['animal']['id'] ?? null;
                    if (isset($item['etapa']) && is_array($item['etapa'])) {
                        $legacy['repro_etapa_etid'] = $item['etapa']['id'] ?? null;
                    }
                }
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware\Legacy\Reproduccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateRegistroCelo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('celo_fecha', $input)) $payload['fecha'] = $input['celo_fecha'];
            elseif (array_key_exists('fecha', $input)) $payload['fecha'] = $input['fecha'];

            if (array_key_exists('celo_observacon', $input)) $payload['observacion'] = $input['celo_observacon'];
            elseif (array_key_exists('celo_observacion', $input)) $payload['observacion'] = $input['celo_observacion'];
            elseif (array_key_exists('observacion', $input)) $payload['observacion'] = $input['observacion'];

            if (array_key_exists('celo_etapa_anid', $input)) $payload['animal_id'] = $input['celo_etapa_anid'];
            elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

            if (array_key_exists('celo_etapa_etid', $input)) $payload['etapa_id'] = $input['celo_etapa_etid'];
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
                    'fecha'           => 'celo_fecha',
                    'observacion'     => 'celo_observacon',
                    'animal_id'       => 'celo_etapa_anid',
                    'etapa_id'        => 'celo_etapa_etid',
                    'animal_etapa_id' => 'celo_etapa_anid',
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
                    'celo_id'         => $item['id'] ?? null,
                    'celo_fecha'      => $item['fecha'] ?? null,
                    'celo_observacon' => $item['observacion'] ?? null,
                    'celo_etapa_anid' => null,
                    'celo_etapa_etid' => null,
                    'created_at'      => $item['created_at'] ?? null,
                    'updated_at'      => $item['updated_at'] ?? null,
                ];
                if (isset($item['etapa_animal']) && is_array($item['etapa_animal'])) {
                    $legacy['celo_etapa_anid'] = $item['etapa_animal']['animal_id'] ?? null;
                    $legacy['celo_etapa_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
                }
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware\Legacy\Reproduccion;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateServicioAnimal
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('servicio_id_Animal', $input)) $payload['animal_id'] = $input['servicio_id_Animal'];
            elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

            if (array_key_exists('servicio_semen_id', $input)) $payload['semen_toro_id'] = $input['servicio_semen_id'];
            elseif (array_key_exists('semen_toro_id', $input)) $payload['semen_toro_id'] = $input['semen_toro_id'];

            if (array_key_exists('servicio_id_Tecnico', $input)) $payload['personal_finca_id'] = $input['servicio_id_Tecnico'];
            elseif (array_key_exists('personal_finca_id', $input)) $payload['personal_finca_id'] = $input['personal_finca_id'];

            if (array_key_exists('servicio_celo_id', $input)) $payload['registro_celo_id'] = $input['servicio_celo_id'];
            elseif (array_key_exists('registro_celo_id', $input)) $payload['registro_celo_id'] = $input['registro_celo_id'];

            if (array_key_exists('servicio_tipo', $input)) $payload['tipo'] = $input['servicio_tipo'];
            elseif (array_key_exists('tipo', $input)) $payload['tipo'] = $input['tipo'];

            if (array_key_exists('servicio_fecha', $input)) $payload['fecha'] = $input['servicio_fecha'];
            elseif (array_key_exists('fecha', $input)) $payload['fecha'] = $input['fecha'];

            if (array_key_exists('servicio_observacion', $input)) $payload['observacion'] = $input['servicio_observacion'];
            elseif (array_key_exists('observacion', $input)) $payload['observacion'] = $input['observacion'];

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
                    'animal_id'         => 'servicio_id_Animal',
                    'semen_toro_id'     => 'servicio_semen_id',
                    'personal_finca_id' => 'servicio_id_Tecnico',
                    'registro_celo_id'  => 'servicio_celo_id',
                    'tipo'              => 'servicio_tipo',
                    'fecha'             => 'servicio_fecha',
                    'observacion'       => 'servicio_observacion',
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
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

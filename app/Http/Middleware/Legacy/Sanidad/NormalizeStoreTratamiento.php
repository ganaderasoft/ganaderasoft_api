<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreTratamiento
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('tratamiento_plan', $input)) $payload['plan'] = $input['tratamiento_plan'];
            elseif (array_key_exists('plan', $input)) $payload['plan'] = $input['plan'];

            if (array_key_exists('tratamiento_fecha_ini', $input)) $payload['fecha_ini'] = $input['tratamiento_fecha_ini'];
            elseif (array_key_exists('fecha_ini', $input)) $payload['fecha_ini'] = $input['fecha_ini'];
            
            if (array_key_exists('tratamiento_fecha_fin', $input)) $payload['fecha_fin'] = $input['tratamiento_fecha_fin'];
            elseif (array_key_exists('fecha_fin', $input)) $payload['fecha_fin'] = $input['fecha_fin'];

            if (array_key_exists('tratamiento_diagnostico_id', $input)) $payload['diagnostico_id'] = $input['tratamiento_diagnostico_id'];
            elseif (array_key_exists('diagnostico_id', $input)) $payload['diagnostico_id'] = $input['diagnostico_id'];

            $request->replace($payload);
        }

        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        // Catch Validation Errors and map back to legacy keys
        if ($response->getStatusCode() === 422) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['errors'])) {
                $errorMapping = [
                    'plan'           => 'tratamiento_plan',
                    'fecha_ini'      => 'tratamiento_fecha_ini',
                    'fecha_fin'      => 'tratamiento_fecha_fin',
                    'diagnostico_id' => 'tratamiento_diagnostico_id'
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

        // Map response on success
        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);
            if (isset($data['data']) && is_array($data['data'])) {
                $item = $data['data'];
                $legacy = [
                    'tratamiento_id'             => $item['id'] ?? null,
                    'tratamiento_plan'           => $item['plan'] ?? null,
                    'tratamiento_fecha_ini'      => $item['fecha_ini'] ?? null,
                    'tratamiento_fecha_fin'      => $item['fecha_fin'] ?? null,
                    'tratamiento_diagnostico_id' => $item['diagnostico_id'] ?? null,
                    
                    'diagnostico'                => $item['diagnostico'] ?? null,
                ];
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

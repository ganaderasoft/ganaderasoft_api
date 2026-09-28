<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreVacuna
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('vacuna_nombre', $input)) $payload['nombre'] = $input['vacuna_nombre'];
            elseif (array_key_exists('nombre', $input)) $payload['nombre'] = $input['nombre'];

            if (array_key_exists('vacuna_descripcion', $input)) $payload['descripcion'] = $input['vacuna_descripcion'];
            elseif (array_key_exists('descripcion', $input)) $payload['descripcion'] = $input['descripcion'];

            if (array_key_exists('activa', $input)) $payload['activa'] = $input['activa'];

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
                    'nombre'      => 'vacuna_nombre',
                    'descripcion' => 'vacuna_descripcion',
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
                    'vacuna_id'          => $item['id'] ?? null,
                    'vacuna_nombre'      => $item['nombre'] ?? null,
                    'vacuna_descripcion' => $item['descripcion'] ?? null,
                    'activa'             => $item['activa'] ?? null,
                ];
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}

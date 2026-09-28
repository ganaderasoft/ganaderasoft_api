<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdatePalpacion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $cleanedInput = $this->transformToCleanFormat($request->all());
        $request->replace($cleanedInput);

        $response = $next($request);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (isset($data['data'])) {
                $data['data'] = $this->transformToLegacyFormat($data['data']);
                $response->setData($data);
            }
        } elseif ($response->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
            $data = $response->getData(true);
            $this->transformValidationErrors($data);
            $response->setData($data);
        }

        return $response;
    }

    /**
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        $payload = [];
        
        if (array_key_exists('id_Tecnico', $input)) $payload['personal_finca_id'] = $input['id_Tecnico'];
        elseif (array_key_exists('personal_finca_id', $input)) $payload['personal_finca_id'] = $input['personal_finca_id'];

        if (array_key_exists('palpacion_tipo', $input)) $payload['tipo'] = $input['palpacion_tipo'];
        elseif (array_key_exists('tipo', $input)) $payload['tipo'] = $input['tipo'];

        if (array_key_exists('palpacion_fecha', $input)) $payload['fecha'] = $input['palpacion_fecha'];
        elseif (array_key_exists('fecha', $input)) $payload['fecha'] = $input['fecha'];

        if (array_key_exists('palpacion_etapa_anid', $input)) $payload['animal_id'] = $input['palpacion_etapa_anid'];
        elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

        if (array_key_exists('palpacion_etapa_etid', $input)) $payload['etapa_id'] = $input['palpacion_etapa_etid'];
        elseif (array_key_exists('etapa_id', $input)) $payload['etapa_id'] = $input['etapa_id'];

        return $payload;
    }

    /**
     * Transforma un recurso V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $legacy = [
            'palpacion_id'         => $item['id'],
            'id_Tecnico'           => $item['personal_finca_id'] ?? null,
            'palpacion_tipo'       => $item['tipo'] ?? null,
            'palpacion_fecha'      => $item['fecha'] ?? null,
            'palpacion_etapa_anid' => null,
            'palpacion_etapa_etid' => null,
            'created_at'           => $item['created_at'] ?? null,
            'updated_at'           => $item['updated_at'] ?? null,
        ];

        if (isset($item['etapa_animal'])) {
            $legacy['palpacion_etapa_anid'] = $item['etapa_animal']['animal_id'] ?? null;
            $legacy['palpacion_etapa_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
        }

        return $legacy;
    }

    /**
     * Convierte los errores de validación de V2 a formato legacy.
     */
    private function transformValidationErrors(array &$data): void
    {
        if (!isset($data['errors'])) {
            return;
        }

        $errorMapping = [
            'personal_finca_id' => 'id_Tecnico',
            'tipo'              => 'palpacion_tipo',
            'fecha'             => 'palpacion_fecha',
            'animal_id'         => 'palpacion_etapa_anid',
            'etapa_id'          => 'palpacion_etapa_etid',
            'animal_etapa_id'   => 'palpacion_etapa_anid'
        ];

        foreach ($errorMapping as $newKey => $legacyKey) {
            if (isset($data['errors'][$newKey])) {
                $data['errors'][$legacyKey] = $data['errors'][$newKey];
                unset($data['errors'][$newKey]);
            }
        }
    }
}

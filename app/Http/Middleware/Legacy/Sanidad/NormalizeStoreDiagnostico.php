<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreDiagnostico
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
        
        if (array_key_exists('diagnostico_descripcion', $input)) $payload['descripcion'] = $input['diagnostico_descripcion'];
        elseif (array_key_exists('descripcion', $input)) $payload['descripcion'] = $input['descripcion'];

        if (array_key_exists('diagnostico_tipo', $input)) $payload['tipo'] = $input['diagnostico_tipo'];
        elseif (array_key_exists('tipo', $input)) $payload['tipo'] = $input['tipo'];

        if (array_key_exists('diagnostico_fecha', $input)) $payload['fecha'] = $input['diagnostico_fecha'];
        elseif (array_key_exists('fecha', $input)) $payload['fecha'] = $input['fecha'];

        if (array_key_exists('fk_etapa_animal_anid', $input)) $payload['animal_id'] = $input['fk_etapa_animal_anid'];
        elseif (array_key_exists('animal_id', $input)) $payload['animal_id'] = $input['animal_id'];

        if (array_key_exists('fk_etapa_animal_etid', $input)) $payload['etapa_id'] = $input['fk_etapa_animal_etid'];
        elseif (array_key_exists('etapa_id', $input)) $payload['etapa_id'] = $input['etapa_id'];

        return $payload;
    }

    /**
     * Transforma un diagnostico V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $legacy = [
            'diagnostico_id'          => $item['id'],
            'diagnostico_descripcion' => $item['descripcion'] ?? null,
            'diagnostico_tipo'        => $item['tipo'] ?? null,
            'diagnostico_fecha'       => $item['fecha'] ?? null,
            'fk_etapa_animal_anid'    => null,
            'fk_etapa_animal_etid'    => null,
            'created_at'              => $item['created_at'] ?? null,
            'updated_at'              => $item['updated_at'] ?? null,
        ];

        if (isset($item['etapa_animal'])) {
            $legacy['fk_etapa_animal_anid'] = $item['etapa_animal']['animal_id'] ?? null;
            $legacy['fk_etapa_animal_etid'] = $item['etapa_animal']['etapa_id'] ?? null;
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
            'descripcion' => 'diagnostico_descripcion',
            'tipo'        => 'diagnostico_tipo',
            'fecha'       => 'diagnostico_fecha',
            'animal_id'   => 'fk_etapa_animal_anid',
            'etapa_id'    => 'fk_etapa_animal_etid',
            'animal_etapa_id' => 'fk_etapa_animal_anid' // Por si el error es de animal_etapa_id
        ];

        foreach ($errorMapping as $newKey => $legacyKey) {
            if (isset($data['errors'][$newKey])) {
                $data['errors'][$legacyKey] = $data['errors'][$newKey];
                unset($data['errors'][$newKey]);
            }
        }
    }
}

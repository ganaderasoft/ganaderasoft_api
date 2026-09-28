<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateComposicionRaza
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
        }

        return $response;
    }

    /**
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        $payload = [];
        if (array_key_exists('Nombre', $input)) $payload['nombre'] = $input['Nombre'];
        if (array_key_exists('Siglas', $input)) $payload['siglas'] = $input['Siglas'];
        if (array_key_exists('Pelaje', $input)) $payload['pelaje'] = $input['Pelaje'];
        if (array_key_exists('Proposito', $input)) $payload['proposito'] = $input['Proposito'];
        if (array_key_exists('Tipo_Raza', $input)) $payload['tipo_raza'] = $input['Tipo_Raza'];
        if (array_key_exists('Origen', $input)) $payload['origen'] = $input['Origen'];
        if (array_key_exists('Caracteristica_Especial', $input)) $payload['caracteristica_especial'] = $input['Caracteristica_Especial'];
        if (array_key_exists('Proporcion_Raza', $input)) $payload['proporcion_raza'] = $input['Proporcion_Raza'];
        if (array_key_exists('fk_id_Finca', $input)) $payload['finca_id'] = $input['fk_id_Finca'];
        if (array_key_exists('fk_tipo_animal_id', $input)) $payload['tipo_animal_id'] = $input['fk_tipo_animal_id'];

        return $payload;
    }

    /**
     * Transforma una composición de raza V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $fincaLegacy = null;
        if (isset($item['finca'])) {
            $fincaLegacy = [
                'id_Finca' => $item['finca']['id'],
                'Nombre'   => $item['finca']['nombre'] ?? null,
            ];
        }

        $tipoAnimalLegacy = null;
        if (isset($item['tipo_animal'])) {
            $tipoAnimalLegacy = [
                'tipo_animal_id'     => $item['tipo_animal']['id'],
                'tipo_animal_nombre' => $item['tipo_animal']['nombre'] ?? null
            ];
        }

        return [
            'id_Composicion'          => $item['id'],
            'Nombre'                  => $item['nombre'],
            'Siglas'                  => $item['siglas'],
            'Pelaje'                  => $item['pelaje'],
            'Proposito'               => $item['proposito'],
            'Tipo_Raza'               => $item['tipo_raza'],
            'Origen'                  => $item['origen'],
            'Caracteristica_Especial' => $item['caracteristica_especial'],
            'Proporcion_Raza'         => $item['proporcion_raza'],
            'fk_id_Finca'             => $item['finca_id'],
            'fk_tipo_animal_id'       => $item['tipo_animal_id'],
            'finca'                   => $fincaLegacy,
            'tipo_animal'             => $tipoAnimalLegacy
        ];
    }
}

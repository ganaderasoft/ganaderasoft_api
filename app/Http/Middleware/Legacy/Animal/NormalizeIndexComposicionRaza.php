<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexComposicionRaza
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
        $cleanedInput['nopaginate'] = true;
        $request->replace($cleanedInput);

        $response = $next($request);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (isset($data['data'])) {
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    $data['data']['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']['data']);
                } else {
                    $data['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']);
                }
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
        return $input;
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

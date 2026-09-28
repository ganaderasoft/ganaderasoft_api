<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreComposicionRaza
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
        return [
            'nombre'                  => $input['Nombre'] ?? null,
            'siglas'                  => $input['Siglas'] ?? null,
            'pelaje'                  => $input['Pelaje'] ?? null,
            'proposito'               => $input['Proposito'] ?? null,
            'tipo_raza'               => $input['Tipo_Raza'] ?? null,
            'origen'                  => $input['Origen'] ?? null,
            'caracteristica_especial' => $input['Caracteristica_Especial'] ?? null,
            'proporcion_raza'         => $input['Proporcion_Raza'] ?? null,
            'finca_id'                => $input['fk_id_Finca'] ?? null,
            'tipo_animal_id'          => $input['fk_tipo_animal_id'] ?? null,
        ];
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

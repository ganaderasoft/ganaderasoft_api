<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexEstadoAnimal
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
     * Transforma un registro de EstadoAnimal V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $estadoSaludLegacy = null;
        if (isset($item['estado_salud'])) {
            $estadoSaludLegacy = [
                'estado_id'     => $item['estado_salud']['id'],
                'estado_nombre' => $item['estado_salud']['nombre'] ?? null
            ];
        }

        $animalLegacy = null;
        if (isset($item['animal'])) {
            $animalLegacy = [
                'id_Animal' => $item['animal']['id'],
                'Nombre'    => $item['animal']['nombre'] ?? null,
            ];
        }

        return [
            'id_Estado_Animal'  => $item['id'],
            'esan_fecha_ini'    => $item['fecha_ini'] ? $item['fecha_ini'] . 'T00:00:00.000000Z' : null,
            'esan_fecha_fin'    => $item['fecha_fin'] ? $item['fecha_fin'] . 'T00:00:00.000000Z' : null,
            'esan_fk_estado_id' => $item['estado_salud_id'],
            'esan_fk_id_animal' => $item['animal_id'],
            'estado_salud'      => $estadoSaludLegacy,
            'animal'            => $animalLegacy,
            'created_at'        => $item['created_at'] ?? null,
            'updated_at'        => $item['updated_at'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Middleware\Legacy\Rebano;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexMovimientoRebano
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
        $cleaned = $input;
        if (isset($input['id_finca'])) {
            $cleaned['finca_id'] = $input['id_finca'];
        }
        if (isset($input['id_rebano'])) {
            $cleaned['rebano_id'] = $input['id_rebano'];
        }
        return $cleaned;
    }

    /**
     * Transforma un registro de movimiento de rebaño V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        // Relación finca origen
        $fincaOrigLegacy = null;
        if (isset($item['finca_origen'])) {
            $fincaOrigLegacy = [
                'id_Finca' => $item['finca_origen']['id'],
                'Nombre'   => $item['finca_origen']['nombre'] ?? null,
            ];
        }

        // Relación rebaño origen
        $rebanoOrigLegacy = null;
        if (isset($item['rebano_origen'])) {
            $rebanoOrigLegacy = [
                'id_Rebano' => $item['rebano_origen']['id'],
                'Nombre'    => $item['rebano_origen']['nombre'] ?? null,
            ];
        }

        // Relación finca destino
        $fincaDestLegacy = null;
        if (isset($item['finca_destino'])) {
            $fincaDestLegacy = [
                'id_Finca' => $item['finca_destino']['id'],
                'Nombre'   => $item['finca_destino']['nombre'] ?? null,
            ];
        }

        // Relación rebaño destino (objeto)
        $rebanoDestLegacy = null;
        if (isset($item['rebano_destino_rel'])) {
            $rebanoDestLegacy = [
                'id_Rebano' => $item['rebano_destino_rel']['id'],
                'Nombre'    => $item['rebano_destino_rel']['nombre'] ?? null,
            ];
        }

        $animalesLegacy = [];
        if (isset($item['animales'])) {
            foreach ($item['animales'] as $animalItem) {
                $animalObjLegacy = null;
                if (isset($animalItem['animal'])) {
                    $animalObjLegacy = [
                        'id_Animal' => $animalItem['animal']['id'],
                        'Nombre'    => $animalItem['animal']['nombre'] ?? null,
                    ];
                }
                $animalesLegacy[] = [
                    'id_Movimiento_Animal' => $animalItem['id'],
                    'id_Animal'            => $animalItem['animal']['id'] ?? ($animalItem['animal_id'] ?? null),
                    'id_Movimiento'        => $item['id'],
                    'Estado'               => $animalItem['estado'],
                    'animal'               => $animalObjLegacy,
                ];
            }
        }

        return [
            'id_Movimiento'     => $item['id'],
            'id_Finca'          => $item['finca_origen']['id'] ?? $item['finca_id'] ?? null,
            'id_Rebano'         => $item['rebano_origen']['id'] ?? $item['rebano_id'] ?? null,
            'Rebano_Destino'    => $item['rebano_destino'] ?? null,
            'id_Finca_Destino'  => $item['finca_destino']['id'] ?? $item['finca_destino_id'] ?? null,
            'id_Rebano_Destino' => $item['rebano_destino_rel']['id'] ?? $item['rebano_destino_id'] ?? null,
            'Comentario'        => $item['comentario'] ?? null,
            'created_at'        => $item['created_at'] ?? null,
            'updated_at'        => $item['updated_at'] ?? null,
            'finca_origen'      => $fincaOrigLegacy,
            'rebano_origen'     => $rebanoOrigLegacy,
            'finca_destino'     => $fincaDestLegacy,
            'rebano_destino'    => $rebanoDestLegacy,
            'animales'          => $animalesLegacy,
        ];
    }

}

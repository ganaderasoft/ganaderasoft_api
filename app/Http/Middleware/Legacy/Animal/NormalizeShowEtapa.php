<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeShowEtapa
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
        return $input;
    }

    /**
     * Transforma un registro de Etapa V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $tipoAnimalLegacy = null;
        if (isset($item['tipo_animal'])) {
            $tipoAnimalLegacy = [
                'tipo_animal_id'     => $item['tipo_animal']['id'],
                'tipo_animal_nombre' => $item['tipo_animal']['nombre']
            ];
        }

        $etapaAnimalesLegacy = [];
        if (isset($item['etapa_animales'])) {
            foreach ($item['etapa_animales'] as $ea) {
                $etapaAnimalesLegacy[] = [
                    'id_Etapa_Animal'   => $ea['id'] ?? null,
                    'etan_fecha_ini'    => isset($ea['fecha_ini']) ? $ea['fecha_ini'] . 'T00:00:00.000000Z' : null,
                    'etan_fecha_fin'    => isset($ea['fecha_fin']) ? $ea['fecha_fin'] . 'T00:00:00.000000Z' : null,
                    'etan_fk_id_animal' => $ea['animal_id'] ?? null,
                    'etan_fk_id_etapa'  => $ea['etapa_id'] ?? null,
                    'animal'            => isset($ea['animal']) ? [
                        'id_Animal' => $ea['animal']['id'],
                        'Nombre'    => $ea['animal']['nombre']
                    ] : null
                ];
            }
        }

        return [
            'etapa_id'                => $item['id'],
            'etapa_nombre'            => $item['nombre'],
            'etapa_edad_ini'          => $item['edad_ini'],
            'etapa_edad_fin'          => $item['edad_fin'],
            'etapa_fk_tipo_animal_id' => $item['tipo_animal_id'],
            'etapa_sexo'              => $item['sexo'],
            'tipo_animal'             => $tipoAnimalLegacy,
            'etapa_animales'          => $etapaAnimalesLegacy
        ];
    }
}

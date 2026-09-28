<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeCreateEtapa
{
    public function handle(Request $request, Closure $next): Response
    {
        $isV2 = $request->header('X-API-VERSION') === '2';

        if (!$isV2) {
            $cleanedInput = $this->transformToCleanFormat($request->all());
            $request->replace($cleanedInput);
        }

        $response = $next($request);

        if (!$isV2 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data'])) {
                $content['data'] = $this->transformEtapaToLegacyFormat($content['data']);
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        $normalized = $input;
        if (isset($input['etan_fecha_ini'])) {
            $normalized['fecha_ini'] = $input['etan_fecha_ini'];
            unset($normalized['etan_fecha_ini']);
        }
        if (isset($input['etan_fecha_fin'])) {
            $normalized['fecha_fin'] = $input['etan_fecha_fin'];
            unset($normalized['etan_fecha_fin']);
        }
        if (isset($input['etan_etapa_id'])) {
            $normalized['etapa_id'] = $input['etan_etapa_id'];
            unset($normalized['etan_etapa_id']);
        }
        return $normalized;
    }

    private function transformEtapaToLegacyFormat(array $etapaAnimal): array
    {
        $etapa = $etapaAnimal['etapa'] ?? null;
        $etapaLegacy = null;
        if ($etapa) {
            $etapaLegacy = [
                'etapa_id' => $etapa['id'] ?? null,
                'etapa_nombre' => $etapa['nombre'] ?? '',
                'etapa_edad_ini' => $etapa['edad_ini'] ?? null,
                'etapa_edad_fin' => $etapa['edad_fin'] ?? null,
                'etapa_sexo' => $etapa['sexo'] ?? null,
                'etapa_fk_tipo_animal_id' => $etapa['tipo_animal_id'] ?? null,
            ];
        }

        $animal = $etapaAnimal['animal'] ?? null;
        $animalLegacy = null;
        if ($animal) {
            $animalLegacy = $this->transformAnimalToLegacyFormat($animal);
        }

        return [
            'etan_etapa_id' => $etapaAnimal['etapa_id'] ?? ($etapa['id'] ?? null),
            'etan_animal_id' => $etapaAnimal['animal_id'] ?? ($animal['id'] ?? null),
            'etan_fecha_ini' => $etapaAnimal['fecha_ini'] ?? null,
            'etan_fecha_fin' => $etapaAnimal['fecha_fin'] ?? null,
            'etapa' => $etapaLegacy,
            'animal' => $animalLegacy,
        ];
    }

    private function transformAnimalToLegacyFormat(array $animal): array
    {
        return [
            'id_Animal' => $animal['id'] ?? null,
            'id_Rebano' => $animal['rebano_id'] ?? ($animal['rebano']['id'] ?? null),
            'Nombre' => $animal['nombre'] ?? null,
            'codigo_animal' => $animal['codigo_animal'] ?? null,
            'Sexo' => $animal['sexo'] ?? null,
            'fecha_nacimiento' => $animal['fecha_nacimiento'] ?? null,
            'Procedencia' => $animal['procedencia'] ?? null,
            'archivado' => $animal['archivado'] ?? false,
            'created_at' => $animal['created_at'] ?? null,
            'updated_at' => $animal['updated_at'] ?? null,
            'fk_composicion_raza' => $animal['composicion_raza_id'] ?? ($animal['composicion_raza']['id'] ?? null),
        ];
    }
}

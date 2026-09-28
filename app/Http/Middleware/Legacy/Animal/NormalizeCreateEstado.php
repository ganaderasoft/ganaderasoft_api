<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeCreateEstado
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
                $content['data'] = $this->transformEstadoToLegacyFormat($content['data']);
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        $normalized = $input;
        if (isset($input['esan_fecha_ini'])) {
            $normalized['fecha_ini'] = $input['esan_fecha_ini'];
            unset($normalized['esan_fecha_ini']);
        }
        if (isset($input['esan_fecha_fin'])) {
            $normalized['fecha_fin'] = $input['esan_fecha_fin'];
            unset($normalized['esan_fecha_fin']);
        }
        if (isset($input['esan_fk_estado_id'])) {
            $normalized['estado_salud_id'] = $input['esan_fk_estado_id'];
            unset($normalized['esan_fk_estado_id']);
        }
        return $normalized;
    }

    private function transformEstadoToLegacyFormat(array $estado): array
    {
        $estadoSalud = $estado['estado_salud'] ?? null;
        $estadoSaludLegacy = null;
        if ($estadoSalud) {
            $estadoSaludLegacy = [
                'estado_id' => $estadoSalud['id'] ?? null,
                'estado_nombre' => $estadoSalud['nombre'] ?? '',
            ];
        }

        $animal = $estado['animal'] ?? null;
        $animalLegacy = null;
        if ($animal) {
            $animalLegacy = $this->transformAnimalToLegacyFormat($animal);
        }

        return [
            'esan_id' => $estado['id'] ?? null,
            'esan_fecha_ini' => $estado['fecha_ini'] ?? null,
            'esan_fecha_fin' => $estado['fecha_fin'] ?? null,
            'esan_fk_estado_id' => $estado['estado_salud_id'] ?? ($estadoSalud['id'] ?? null),
            'esan_fk_id_animal' => $estado['animal_id'] ?? ($animal['id'] ?? null),
            'estado_salud' => $estadoSaludLegacy,
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

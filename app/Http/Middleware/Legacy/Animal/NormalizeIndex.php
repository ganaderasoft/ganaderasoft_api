<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndex
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

            if (isset($content['data']['data']) && is_array($content['data']['data'])) {
                foreach ($content['data']['data'] as $key => $animal) {
                    $content['data']['data'][$key] = $this->transformAnimalToLegacyFormat($animal);
                }
                $response->setContent(json_encode($content));
            } elseif (isset($content['data']) && is_array($content['data'])) {
                foreach ($content['data'] as $key => $animal) {
                    $content['data'][$key] = $this->transformAnimalToLegacyFormat($animal);
                }
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        $normalized = $input;
        if (isset($input['id_Rebano'])) {
            $normalized['rebano_id'] = $input['id_Rebano'];
            unset($normalized['id_Rebano']);
        }
        if (isset($input['Sexo'])) {
            $sexo = strtoupper(trim($input['Sexo']));
            $normalized['sexo'] = ($sexo === 'F' || $sexo === 'FEMENINO' || $sexo === 'HEMBRA') ? 'H' : $sexo;
            unset($normalized['Sexo']);
        }
        return $normalized;
    }

    private function transformAnimalToLegacyFormat(array $animal): array
    {
        $rebano = $animal['rebano'] ?? null;
        $rebanoLegacy = null;
        if ($rebano) {
            $finca = $rebano['finca'] ?? null;
            $fincaLegacy = null;
            if ($finca) {
                $propietario = $finca['propietario'] ?? null;
                $propietarioLegacy = null;
                if ($propietario) {
                    $persona = $propietario['persona'] ?? null;
                    $propietarioLegacy = [
                        'id' => $propietario['id'] ?? null,
                        'id_Personal' => $persona && is_numeric($persona['cedula']) ? (int) $persona['cedula'] : ($persona['cedula'] ?? null),
                        'Nombre' => $persona['nombre'] ?? '',
                        'Apellido' => $persona['apellido'] ?? '',
                        'Telefono' => $persona['telefono'] ?? '',
                        'archivado' => $persona && ($persona['status'] === 'inactivo'),
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                }
                
                $fincaLegacy = [
                    'id_Finca' => $finca['id'] ?? null,
                    'id_Propietario' => $finca['propietario_id'] ?? ($propietario['id'] ?? null),
                    'Nombre' => $finca['nombre'] ?? '',
                    'Explotacion_Tipo' => $finca['explotacion_tipo'] ?? '',
                    'archivado' => $finca['archivado'] ?? false,
                    'created_at' => $finca['created_at'] ?? null,
                    'updated_at' => $finca['updated_at'] ?? null,
                    'propietario' => $propietarioLegacy,
                ];
            }
            
            $rebanoLegacy = [
                'id_Rebano' => $rebano['id'] ?? null,
                'id_Finca' => $rebano['finca_id'] ?? ($finca['id'] ?? null),
                'Nombre' => $rebano['nombre'] ?? '',
                'archivado' => $rebano['archivado'] ?? false,
                'created_at' => $rebano['created_at'] ?? null,
                'updated_at' => $rebano['updated_at'] ?? null,
                'finca' => $fincaLegacy,
            ];
        }

        $raza = $animal['composicion_raza'] ?? null;
        $razaLegacy = null;
        if ($raza) {
            $razaLegacy = [
                'id_Composicion' => $raza['id'] ?? null,
                'Nombre' => $raza['nombre'] ?? '',
                'Siglas' => $raza['siglas'] ?? '',
                'Pelaje' => $raza['pelaje'] ?? '',
                'Proposito' => $raza['proposito'] ?? '',
                'Tipo_Raza' => $raza['tipo_raza'] ?? '',
                'Origen' => $raza['origen'] ?? '',
                'Caracteristica_Especial' => $raza['caracteristica_especial'] ?? '',
                'Proporcion_Raza' => $raza['proporcion_raza'] ?? '',
                'created_at' => $raza['created_at'] ?? null,
                'updated_at' => $raza['updated_at'] ?? null,
                'fk_id_Finca' => $raza['finca_id'] ?? ($raza['finca']['id'] ?? null),
                'fk_tipo_animal_id' => $raza['tipo_animal_id'] ?? ($raza['tipo_animal']['id'] ?? null),
            ];
        }

        return [
            'id_Animal' => $animal['id'] ?? null,
            'id_Rebano' => $animal['rebano_id'] ?? ($rebano['id'] ?? null),
            'Nombre' => $animal['nombre'] ?? null,
            'codigo_animal' => $animal['codigo_animal'] ?? null,
            'Sexo' => isset($animal['sexo']) && $animal['sexo'] === 'H' ? 'F' : ($animal['sexo'] ?? null),
            'fecha_nacimiento' => $animal['fecha_nacimiento'] ?? null,
            'Procedencia' => $animal['procedencia'] ?? null,
            'archivado' => $animal['archivado'] ?? false,
            'created_at' => $animal['created_at'] ?? null,
            'updated_at' => $animal['updated_at'] ?? null,
            'fk_composicion_raza' => $animal['composicion_raza_id'] ?? ($raza['id'] ?? null),
            'rebano' => $rebanoLegacy,
            'composicion_raza' => $razaLegacy,
        ];
    }
}

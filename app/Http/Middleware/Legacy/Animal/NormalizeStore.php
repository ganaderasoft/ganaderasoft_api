<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStore
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
                $content['data'] = $this->transformAnimalToLegacyFormat($content['data']);
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
        if (isset($input['Nombre'])) {
            $normalized['nombre'] = $input['Nombre'];
            unset($normalized['Nombre']);
        }
        if (isset($input['Sexo'])) {
            $sexo = strtoupper(trim($input['Sexo']));
            $normalized['sexo'] = ($sexo === 'F' || $sexo === 'FEMENINO' || $sexo === 'HEMBRA') ? 'H' : $sexo;
            unset($normalized['Sexo']);
        }
        if (isset($input['Procedencia'])) {
            $normalized['procedencia'] = $input['Procedencia'];
            unset($normalized['Procedencia']);
        }
        if (isset($input['fk_composicion_raza'])) {
            $normalized['composicion_raza_id'] = $input['fk_composicion_raza'];
            unset($normalized['fk_composicion_raza']);
        }

        // Traducir estado_inicial si aplica
        if (isset($input['estado_inicial'])) {
            $est = $input['estado_inicial'];
            $normalized['estado_inicial'] = [
                'fecha_ini' => $est['fecha_ini'] ?? null,
                'estado_salud_id' => $est['estado_id'] ?? null,
            ];
        }

        // Traducir etapa_inicial si aplica
        if (isset($input['etapa_inicial'])) {
            $et = $input['etapa_inicial'];
            $normalized['etapa_inicial'] = [
                'fecha_ini' => $et['fecha_ini'] ?? null,
                'etapa_id' => $et['etapa_id'] ?? null,
            ];
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

        $estadoActual = $animal['estado_actual'] ?? null;
        $estadoActualLegacy = null;
        if ($estadoActual) {
            $estadoActualLegacy = $this->transformEstadoToLegacyFormat($estadoActual);
        }

        $etapaActual = $animal['etapa_actual'] ?? null;
        $etapaActualLegacy = null;
        if ($etapaActual) {
            $etapaActualLegacy = $this->transformEtapaToLegacyFormat($etapaActual);
        }

        $legacyAnimal = [
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

        // Mapeos condicionales en el output
        if (array_key_exists('estado_actual', $animal)) {
            $legacyAnimal['estado_actual'] = $estadoActualLegacy;
        }
        if (array_key_exists('etapa_actual', $animal)) {
            $legacyAnimal['etapa_actual'] = $etapaActualLegacy;
        }

        return $legacyAnimal;
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

        return [
            'esan_id' => $estado['id'] ?? null,
            'esan_fecha_ini' => $estado['fecha_ini'] ?? null,
            'esan_fecha_fin' => $estado['fecha_fin'] ?? null,
            'esan_fk_estado_id' => $estado['estado_salud_id'] ?? ($estadoSalud['id'] ?? null),
            'esan_fk_id_animal' => $estado['animal_id'] ?? ($estado['animal']['id'] ?? null),
            'estado_salud' => $estadoSaludLegacy,
        ];
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
                'etapa_fk_tipo_animal_id' => $etapa['tipo_animal_id'] ?? ($etapa['tipo_animal']['id'] ?? null),
            ];
        }

        return [
            'etan_etapa_id' => $etapaAnimal['etapa_id'] ?? ($etapa['id'] ?? null),
            'etan_animal_id' => $etapaAnimal['animal_id'] ?? ($etapaAnimal['animal']['id'] ?? null),
            'etan_fecha_ini' => $etapaAnimal['fecha_ini'] ?? null,
            'etan_fecha_fin' => $etapaAnimal['fecha_fin'] ?? null,
            'etapa' => $etapaLegacy,
        ];
    }
}

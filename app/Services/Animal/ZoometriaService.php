<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\MedidasCorporales;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ZoometriaService extends BaseService
{
    /**
     * Calcula dinámicamente los 7 índices zoométricos corporales y su interpretación zootécnica
     * a partir de un conjunto de medidas físicas (en centímetros).
     *
     * @param MedidasCorporales|array $medidas
     * @return array
     */
    public function calcularIndices($medidas): array
    {
        $hc  = is_array($medidas) ? ($medidas['altura_hc'] ?? null) : $medidas->altura_hc;
        $hg  = is_array($medidas) ? ($medidas['altura_hg'] ?? null) : $medidas->altura_hg;
        $pt  = is_array($medidas) ? ($medidas['perimetro_pt'] ?? null) : $medidas->perimetro_pt;
        $pca = is_array($medidas) ? ($medidas['perimetro_pca'] ?? null) : $medidas->perimetro_pca;
        $lc  = is_array($medidas) ? ($medidas['longitud_lc'] ?? null) : $medidas->longitud_lc;
        $lg  = is_array($medidas) ? ($medidas['longitud_lg'] ?? null) : $medidas->longitud_lg;
        $ag  = is_array($medidas) ? ($medidas['anchura_ag'] ?? null) : $medidas->anchura_ag;

        $hc  = ($hc !== null && is_numeric($hc) && (float) $hc > 0) ? (float) $hc : null;
        $hg  = ($hg !== null && is_numeric($hg) && (float) $hg > 0) ? (float) $hg : null;
        $pt  = ($pt !== null && is_numeric($pt) && (float) $pt > 0) ? (float) $pt : null;
        $pca = ($pca !== null && is_numeric($pca) && (float) $pca > 0) ? (float) $pca : null;
        $lc  = ($lc !== null && is_numeric($lc) && (float) $lc > 0) ? (float) $lc : null;
        $lg  = ($lg !== null && is_numeric($lg) && (float) $lg > 0) ? (float) $lg : null;
        $ag  = ($ag !== null && is_numeric($ag) && (float) $ag > 0) ? (float) $ag : null;

        // 1. Índice de Anamorfosis (IA) = PT^2 / HC
        $anamorfosis = ($pt !== null && $hc !== null && $hc > 0)
            ? round(pow($pt, 2) / $hc, 2)
            : null;

        // 2. Índice Corporal (IC) = (LC / PT) * 100
        $corporal = ($lc !== null && $pt !== null && $pt > 0)
            ? round(($lc / $pt) * 100, 2)
            : null;

        // 3. Índice Pelviano (IP) = (AG / LG) * 100
        $pelviano = ($ag !== null && $lg !== null && $lg > 0)
            ? round(($ag / $lg) * 100, 2)
            : null;

        // 4. Índice de Proporcionalidad (IPr) = (HC / LC) * 100
        $proporcionalidad = ($hc !== null && $lc !== null && $lc > 0)
            ? round(($hc / $lc) * 100, 2)
            : null;

        // 5. Índice Dáctilo-Torácico (IDT) = (PCA / PT) * 100
        $dactiloToracico = ($pca !== null && $pt !== null && $pt > 0)
            ? round(($pca / $pt) * 100, 2)
            : null;

        // 6. Índice Pelviano Transversal (IPT) = (AG / HC) * 100
        $pelvianoTransversal = ($ag !== null && $hc !== null && $hc > 0)
            ? round(($ag / $hc) * 100, 2)
            : null;

        // 7. Índice Pelviano Longitudinal (IPL) = (LG / HC) * 100
        $pelvianoLongitudinal = ($lg !== null && $hc !== null && $hc > 0)
            ? round(($lg / $hc) * 100, 2)
            : null;

        // Interpretaciones Zootécnicas
        $biotipo = null;
        $biotipoDescripcion = null;
        if ($corporal !== null) {
            if ($corporal < 85.0) {
                $biotipo = 'Brevilíneo';
                $biotipoDescripcion = 'Animal de formato compacto, tórax profundo y grupa ancha. Predisposición a aptitud cárnica.';
            } elseif ($corporal <= 90.0) {
                $biotipo = 'Mediolíneo';
                $biotipoDescripcion = 'Animal de proporciones armónicas y equilibradas. Típico de biotipos de doble propósito.';
            } else {
                $biotipo = 'Longilíneo';
                $biotipoDescripcion = 'Animal de cuerpo estilizado, tronco alargado. Predisposición a aptitud lechera.';
            }
        }

        $pelvisConformacion = null;
        if ($pelviano !== null) {
            if ($pelviano < 100.0) {
                $pelvisConformacion = 'Pelvis Estrecha / Alargada';
            } elseif ($pelviano <= 110.0) {
                $pelvisConformacion = 'Pelvis Cuadrada / Equilibrada';
            } else {
                $pelvisConformacion = 'Pelvis Ancha';
            }
        }

        $esqueletoTipo = null;
        if ($dactiloToracico !== null) {
            if ($dactiloToracico < 10.5) {
                $esqueletoTipo = 'Esqueleto Ligero / Fino';
            } elseif ($dactiloToracico <= 11.5) {
                $esqueletoTipo = 'Esqueleto Medio / Eumétrico';
            } else {
                $esqueletoTipo = 'Esqueleto Fuerte / Robusto';
            }
        }

        return [
            'indices' => [
                'anamorfosis' => [
                    'valor'          => $anamorfosis,
                    'nombre'         => 'Índice de Anamorfosis',
                    'sigla'          => 'IA',
                    'formula'        => 'PT² / HC',
                    'parametros'     => ['perimetro_pt', 'altura_hc'],
                    'descripcion'    => 'Evalúa la compacidad torácica y corpulencia en relación a la alzada.',
                ],
                'corporal' => [
                    'valor'          => $corporal,
                    'nombre'         => 'Índice Corporal',
                    'sigla'          => 'IC',
                    'formula'        => '(LC / PT) * 100',
                    'parametros'     => ['longitud_lc', 'perimetro_pt'],
                    'descripcion'    => 'Clasifica el formato constitucional del animal (Brevilíneo, Mediolíneo o Longilíneo).',
                    'clasificacion'  => $biotipo,
                ],
                'pelviano' => [
                    'valor'          => $pelviano,
                    'nombre'         => 'Índice Pelviano',
                    'sigla'          => 'IP',
                    'formula'        => '(AG / LG) * 100',
                    'parametros'     => ['anchura_ag', 'longitud_lg'],
                    'descripcion'    => 'Mide la amplitud y conformación de la pelvis, relevante para facilidad de parto y conformación cárnica.',
                    'clasificacion'  => $pelvisConformacion,
                ],
                'proporcionalidad' => [
                    'valor'          => $proporcionalidad,
                    'nombre'         => 'Índice de Proporcionalidad',
                    'sigla'          => 'IPr',
                    'formula'        => '(HC / LC) * 100',
                    'parametros'     => ['altura_hc', 'longitud_lc'],
                    'descripcion'    => 'Determina la relación entre la alzada a la cruz y la longitud corporal.',
                ],
                'dactilo_toracico' => [
                    'valor'          => $dactiloToracico,
                    'nombre'         => 'Índice Dáctilo-Torácico',
                    'sigla'          => 'IDT',
                    'formula'        => '(PCA / PT) * 100',
                    'parametros'     => ['perimetro_pca', 'perimetro_pt'],
                    'descripcion'    => 'Relación entre la fortaleza ósea de extremidades y la masa o perímetro torácico.',
                    'clasificacion'  => $esqueletoTipo,
                ],
                'pelviano_transversal' => [
                    'valor'          => $pelvianoTransversal,
                    'nombre'         => 'Índice Pelviano Transversal',
                    'sigla'          => 'IPT',
                    'formula'        => '(AG / HC) * 100',
                    'parametros'     => ['anchura_ag', 'altura_hc'],
                    'descripcion'    => 'Amplitud de la grupa en relación a la alzada.',
                ],
                'pelviano_longitudinal' => [
                    'valor'          => $pelvianoLongitudinal,
                    'nombre'         => 'Índice Pelviano Longitudinal',
                    'sigla'          => 'IPL',
                    'formula'        => '(LG / HC) * 100',
                    'parametros'     => ['longitud_lg', 'altura_hc'],
                    'descripcion'    => 'Longitud de la grupa en relación a la alzada.',
                ],
            ],
            'interpretacion' => [
                'biotipo'              => $biotipo,
                'biotipo_descripcion'  => $biotipoDescripcion,
                'pelvis_conformacion'  => $pelvisConformacion,
                'esqueleto_tipo'       => $esqueletoTipo,
                'total_calculados'     => count(array_filter([
                    $anamorfosis, $corporal, $pelviano, $proporcionalidad,
                    $dactiloToracico, $pelvianoTransversal, $pelvianoLongitudinal
                ], fn($v) => $v !== null)),
            ],
        ];
    }

    /**
     * Obtiene el análisis zoométrico completo para un registro de medidas específico.
     *
     * @param int $medidaId
     * @param User $user
     * @return array
     * @throws ModelNotFoundException|AuthorizationException
     */
    public function getIndicesByMedidaId(int $medidaId, User $user): array
    {
        $medida = MedidasCorporales::with(['etapaAnimal.etapa', 'etapaAnimal.animal.rebano.finca'])
            ->findOrFail($medidaId);

        $animal = optional($medida->etapaAnimal)->animal;
        $fincaId = optional(optional($animal)->rebano)->finca_id;

        if ($user->cannot('read', $medida)) {
            throw new AuthorizationException('No tiene permisos para consultar los índices de esta medida corporal.');
        }

        $calculo = $this->calcularIndices($medida);

        return [
            'medida_id'       => $medida->id,
            'animal'          => [
                'id'            => $animal ? $animal->id : null,
                'nombre'        => $animal ? $animal->nombre : null,
                'codigo_animal' => $animal ? $animal->codigo_animal : null,
                'sexo'          => $animal ? $animal->sexo : null,
                'etapa'         => optional(optional($medida->etapaAnimal)->etapa)->nombre,
            ],
            'medidas_base'    => [
                'altura_hc'     => $medida->altura_hc,
                'altura_hg'     => $medida->altura_hg,
                'perimetro_pt'  => $medida->perimetro_pt,
                'perimetro_pca' => $medida->perimetro_pca,
                'longitud_lc'   => $medida->longitud_lc,
                'longitud_lg'   => $medida->longitud_lg,
                'anchura_ag'    => $medida->anchura_ag,
                'fecha_registro'=> $medida->created_at ? $medida->created_at->toIso8601String() : null,
            ],
            'indices'         => $calculo['indices'],
            'interpretacion'  => $calculo['interpretacion'],
        ];
    }

    /**
     * Obtiene la evolución histórica de índices zoométricos para un animal a lo largo del tiempo.
     *
     * @param int $animalId
     * @param User $user
     * @return array
     * @throws ModelNotFoundException|AuthorizationException
     */
    public function getEvolucionIndicesByAnimal(int $animalId, User $user): array
    {
        $animal = Animal::with(['rebano.finca'])->findOrFail($animalId);

        if ($user->cannot('read', $animal) || $user->cannot('readAny', MedidasCorporales::class)) {
            throw new AuthorizationException('No tiene permisos para consultar la evolución zoométrica de este animal.');
        }

        $medidas = MedidasCorporales::with(['etapaAnimal.etapa'])
            ->whereHas('etapaAnimal', function ($q) use ($animalId) {
                $q->where('animal_id', $animalId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $evolucion = [];
        foreach ($medidas as $medida) {
            $calculo = $this->calcularIndices($medida);
            $evolucion[] = [
                'medida_id'       => $medida->id,
                'fecha_registro'  => $medida->created_at ? $medida->created_at->toIso8601String() : null,
                'etapa'           => optional(optional($medida->etapaAnimal)->etapa)->nombre,
                'medidas_base'    => [
                    'altura_hc'     => $medida->altura_hc,
                    'altura_hg'     => $medida->altura_hg,
                    'perimetro_pt'  => $medida->perimetro_pt,
                    'perimetro_pca' => $medida->perimetro_pca,
                    'longitud_lc'   => $medida->longitud_lc,
                    'longitud_lg'   => $medida->longitud_lg,
                    'anchura_ag'    => $medida->anchura_ag,
                ],
                'indices'         => $calculo['indices'],
                'interpretacion'  => $calculo['interpretacion'],
            ];
        }

        $ultimoCalculo = count($evolucion) > 0 ? end($evolucion) : null;

        return [
            'animal'           => [
                'id'            => $animal->id,
                'nombre'        => $animal->nombre,
                'codigo_animal' => $animal->codigo_animal,
                'sexo'          => $animal->sexo,
                'finca'         => optional(optional($animal->rebano)->finca)->nombre,
            ],
            'total_mediciones' => count($evolucion),
            'ultimo_analisis'  => $ultimoCalculo ? $ultimoCalculo['interpretacion'] : null,
            'historial'        => $evolucion,
        ];
    }
}

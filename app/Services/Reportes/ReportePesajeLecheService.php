<?php

namespace App\Services\Reportes;

use App\Models\Finca;
use App\Models\Leche;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Servicio para la generación del Reporte de Pesaje de Leche y Rendimiento Productivo.
 */
class ReportePesajeLecheService extends BaseService
{
    /**
     * Genera la estructura de datos para el Reporte de Pesaje de Leche.
     *
     * @param array $filters Filtros opcionales (fecha_inicio, fecha_fin, finca_id, rebano_id, etc.)
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array Estructura con KPIs lecheros y detalle de ordeños.
     *
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function generar(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fechaInicio = $filters['fecha_inicio'] ?? null;
        $fechaFin    = $filters['fecha_fin'] ?? null;
        $fincaId     = $filters['finca_id'] ?? ($filters['id_finca'] ?? null);

        $fincasQuery = Finca::where('archivado', false);
        $this->applyFincaFilter($fincasQuery, $user, null, 'id');

        if ($fincaId) {
            $fincaId = (int) $fincaId;
            if (!$this->checkFincaAccess($user, $fincaId)) {
                throw new AuthorizationException('No tiene permisos para ver reportes de esta finca.');
            }
            $fincasQuery->where('id', $fincaId);
        }

        $fincas = $fincasQuery->get();
        if ($fincas->isEmpty()) {
            throw new ModelNotFoundException('No se encontraron fincas para el reporte.');
        }

        $fincaIds = $fincas->pluck('id')->toArray();
        $primeraFinca = $fincas->first();

        // 1. Consultar registros de pesajes de leche
        $lecheQuery = Leche::whereHas('lactancia.etapaAnimal.animal.rebano', function ($q) use ($fincaIds, $filters) {
            $q->whereIn('finca_id', $fincaIds);
            if (!empty($filters['rebano_id'])) {
                $q->where('id', $filters['rebano_id']);
            }
        })
        ->with([
            'lactancia.etapaAnimal.animal.rebano',
            'lactancia.etapaAnimal.etapa',
            'lactancia.etapaAnimal.animal.estadoActual.estadoSalud',
        ]);

        if (!empty($filters['animal_id'])) {
            $lecheQuery->whereHas('lactancia.etapaAnimal', function ($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }

        if (!empty($fechaInicio)) {
            $lecheQuery->where('fecha_pesaje', '>=', $fechaInicio);
        }
        if (!empty($fechaFin)) {
            $lecheQuery->where('fecha_pesaje', '<=', $fechaFin);
        }

        $lecheRecords = $lecheQuery->orderBy('fecha_pesaje', 'desc')->get();

        // 2. Mapear listado de pesajes
        $pesajesFormateados = [];
        $totalProduccion = 0.0;
        $animalesEnOrdeno = [];

        foreach ($lecheRecords as $leche) {
            $animal = $leche->lactancia?->etapaAnimal?->animal;
            $etapa = $leche->lactancia?->etapaAnimal?->etapa;
            $rebano = $animal?->rebano;

            $pesajeTotal = (float) $leche->pesaje_total;
            $totalProduccion += $pesajeTotal;

            if ($animal) {
                $animalesEnOrdeno[$animal->id] = true;
            }

            $fechaStr = $leche->fecha_pesaje
                ? (is_string($leche->fecha_pesaje) ? substr($leche->fecha_pesaje, 0, 10) : $leche->fecha_pesaje->format('Y-m-d'))
                : null;

            $pesajesFormateados[] = [
                'id'           => $leche->id,
                'codigo'       => $animal?->codigo_animal ?? (string) $animal?->id,
                'nombre'       => $animal?->nombre ?? 'Sin nombre',
                'categoria'    => $etapa?->nombre ?? 'Vacas en ordeño',
                'estatus'      => $animal?->estadoActual?->estadoSalud?->nombre ?? ($animal?->archivado ? 'Archivado' : 'Activo'),
                'lote'         => $rebano?->nombre ?? 'Sin rebaño',
                'fecha_evento' => $fechaStr,
                'peso_total'   => $pesajeTotal,
            ];
        }

        $totalPesajes = count($pesajesFormateados);
        $totalProduccion = round($totalProduccion, 2);
        $promedioPesaje = $totalPesajes > 0 ? round($totalProduccion / $totalPesajes, 2) : 0.0;
        $totalVacasEnOrdeno = count($animalesEnOrdeno);
        $promedioDiarioVaca = $totalVacasEnOrdeno > 0 ? round($totalProduccion / $totalVacasEnOrdeno, 2) : 0.0;

        // 3. Agrupación de pesajes consolidados por fecha y lote (items)
        $itemsConsolidados = [];
        $gruposFechaLote = $lecheRecords->groupBy(function ($rec) {
            $fStr = $rec->fecha_pesaje ? (is_string($rec->fecha_pesaje) ? substr($rec->fecha_pesaje, 0, 10) : $rec->fecha_pesaje->format('Y-m-d')) : 'Fecha';
            $loteNom = $rec->lactancia?->etapaAnimal?->animal?->rebano?->nombre ?? 'Lote general';
            return $fStr . '|' . $loteNom;
        });

        foreach ($gruposFechaLote as $key => $recordsGrupo) {
            [$fechaStr, $loteNom] = explode('|', $key);
            $totalDia = (float) $recordsGrupo->sum('pesaje_total');
            $cantVacas = $recordsGrupo->pluck('lactancia.etapaAnimal.animal_id')->filter()->unique()->count();
            $cantVacas = max(1, $cantVacas);
            $promedioVaca = round($totalDia / $cantVacas, 1);

            $itemsConsolidados[] = [
                'fecha_pesaje'         => $fechaStr,
                'rebano_nombre'        => $loteNom,
                'vacas_pesadas'        => $cantVacas,
                'promedio_vaca_litros' => $promedioVaca,
                'total_dia_litros'     => round($totalDia, 1),
            ];
        }

        // 4. Rendimiento individual por vaca
        $rendimientoIndividual = [];
        $gruposPorAnimal = $lecheRecords->groupBy(fn($r) => $r->lactancia?->etapaAnimal?->animal_id);

        foreach ($gruposPorAnimal as $anId => $recsAnimal) {
            $primerRec = $recsAnimal->first();
            $animal = $primerRec->lactancia?->etapaAnimal?->animal;
            $lactancia = $primerRec->lactancia;

            if (!$animal) {
                continue;
            }

            $inicioLact = $lactancia?->fecha_inicio;
            $diasOrdeno = $inicioLact ? (is_string($inicioLact) ? Carbon::parse($inicioLact)->diffInDays(now()) : $inicioLact->diffInDays(now())) : 0;
            $totalLitrosAnimal = (float) $recsAnimal->sum('pesaje_total');
            $cantPesajes = $recsAnimal->count();
            $litrosDia = $cantPesajes > 0 ? round($totalLitrosAnimal / $cantPesajes, 1) : 0.0;

            // Calcular variación real
            $variacion = '+0.0 lts';
            if ($cantPesajes >= 2) {
                $ordenados = $recsAnimal->sortByDesc('fecha_pesaje')->values();
                $ultimoPesaje = (float) ($ordenados[0]->pesaje_total ?? 0);
                $penultimoPesaje = (float) ($ordenados[1]->pesaje_total ?? 0);
                $diff = round($ultimoPesaje - $penultimoPesaje, 1);
                $variacion = ($diff >= 0 ? '+' : '') . number_format($diff, 1) . ' lts';
            } elseif ($promedioDiarioVaca > 0) {
                $diff = round($litrosDia - $promedioDiarioVaca, 1);
                $variacion = ($diff >= 0 ? '+' : '') . number_format($diff, 1) . ' lts';
            }

            $rendimientoIndividual[] = [
                'animal_identificador' => ($animal->codigo_animal ?? 'ID:' . $animal->id) . ' (' . ($animal->nombre ?? 'Sin nombre') . ')',
                'lactancia'            => 'Lactancia en curso',
                'dias_en_ordeno'       => (int) $diasOrdeno,
                'litros_dia'           => $litrosDia,
                'variacion'            => $variacion,
            ];
        }

        return [
            'finca' => [
                'id'     => $primeraFinca->id,
                'nombre' => $primeraFinca->nombre,
            ],
            'resumen' => [
                'total_pesajes'           => $totalPesajes,
                'total_produccion'        => $totalProduccion,
                'promedio_pesaje'         => $promedioPesaje,
                'produccion_total_ordeno' => $totalProduccion,
                'promedio_diario_vaca'    => $promedioDiarioVaca,
                'vacas_en_ordeno'         => $totalVacasEnOrdeno,
            ],
            'kpis' => [
                'produccion_total_ordeno' => $totalProduccion,
                'promedio_diario_vaca'    => $promedioDiarioVaca,
                'vacas_en_ordeno'         => $totalVacasEnOrdeno,
                'total_pesajes'           => $totalPesajes,
            ],
            'pesajes'                => $pesajesFormateados,
            'items'                  => $itemsConsolidados,
            'rendimiento_individual' => $rendimientoIndividual,
            'filtros_aplicados'      => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'finca_id'     => $fincaId,
                'rebano_id'    => $filters['rebano_id'] ?? null,
                'animal_id'    => $filters['animal_id'] ?? null,
            ],
        ];
    }
}

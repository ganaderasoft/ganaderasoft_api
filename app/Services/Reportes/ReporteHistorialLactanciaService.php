<?php

namespace App\Services\Reportes;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Lactancia;
use App\Models\User;
use App\Services\BaseService;
use DateTime;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Servicio para la generación del Reporte de Historia de Lactancias y Curvas de Producción Láctea (Algoritmo TIM).
 */
class ReporteHistorialLactanciaService extends BaseService
{
    /**
     * Genera la estructura de datos para el Reporte de Historia de Lactancias con cálculo TIM.
     *
     * @param array $filters Filtros opcionales (finca_id, rebano_id, animal_id, etc.)
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array Estructura con datos de finca, KPIs y listado detallado de vacas y lactancias.
     *
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function generar(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincaId = $filters['finca_id'] ?? ($filters['id_finca'] ?? null);

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
        $finca = $fincas->first();

        $animalesQuery = Animal::query()
            ->whereHas('rebano', function ($q) use ($fincaIds) {
                $q->whereIn('finca_id', $fincaIds);
            })
            ->whereIn('sexo', ['H', 'F', 'HEMBRA', 'Hembra', 'hembra'])
            ->with(['rebano', 'etapaActual.etapa']);

        if (!empty($filters['rebano_id'])) {
            $animalesQuery->where('rebano_id', $filters['rebano_id']);
        }

        if (!empty($filters['animal_id'])) {
            $animalesQuery->where('id', $filters['animal_id']);
        }

        $animales = $animalesQuery->orderBy('nombre')->get();
        $animalIds = $animales->pluck('id')->toArray();

        // Obtener todas las lactancias con sus pesajes de leche ordenados por fecha
        $lactancias = Lactancia::whereHas('etapaAnimal', function ($q) use ($animalIds) {
            $q->whereIn('animal_id', $animalIds);
        })
        ->with([
            'etapaAnimal',
            'lecheRecords' => function ($q) {
                $q->orderBy('fecha_pesaje', 'asc');
            }
        ])
        ->orderBy('fecha_inicio', 'asc')
        ->get();

        $lactanciasPorAnimal = [];
        foreach ($lactancias as $lact) {
            $anId = $lact->etapaAnimal?->animal_id;
            if ($anId) {
                $lactanciasPorAnimal[$anId][] = $lact;
            }
        }

        $resultadoAnimales = [];
        $totalProduccionFinca = 0.0;

        foreach ($animales as $animal) {
            $animalLacts = $lactanciasPorAnimal[$animal->id] ?? [];
            $listaLactancias = [];
            $produccionVitalicia = 0.0;
            $nro = 1;

            foreach ($animalLacts as $lact) {
                $inicio = $lact->fecha_inicio;
                $fin = $lact->fecha_fin;

                $diasLactancia = $fin
                    ? $inicio->diffInDays($fin)
                    : ($inicio ? $inicio->diffInDays(now()) : 0);

                $pesajes = $lact->lecheRecords;

                $p244 = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, 244) : null;
                $p270 = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, 270) : null;
                $p305 = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, 305) : null;
                $prodTotal = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, $diasLactancia) : null;

                if ($prodTotal) {
                    $produccionVitalicia += $prodTotal;
                }

                $listaLactancias[] = [
                    'id'               => $lact->id,
                    'num_lactancia'    => $nro++,
                    'fecha_inicio'     => $inicio ? $inicio->format('Y-m-d') : null,
                    'fecha_fin'        => $fin ? $fin->format('Y-m-d') : null,
                    'estado'           => $fin ? 'Secada' : 'En curso',
                    'dias_lactancia'   => $diasLactancia,
                    'p244'             => $p244,
                    'p270'             => $p270,
                    'p305'             => $p305,
                    'produccion_total' => $prodTotal ? round($prodTotal, 2) : 0.0,
                    'total_pesajes'    => $pesajes->count(),
                ];
            }

            $totalProduccionFinca += $produccionVitalicia;

            $resultadoAnimales[] = [
                'id'                   => $animal->id,
                'codigo'               => $animal->codigo_animal,
                'nombre'               => $animal->nombre,
                'categoria'            => $animal->etapaActual?->etapa?->nombre ?? 'S/C',
                'estatus'              => $animal->archivado ? 'Archivado' : 'Activo',
                'rebano_nombre'        => $animal->rebano?->nombre ?? 'Sin rebaño',
                'total_lactancias'     => count($listaLactancias),
                'produccion_vitalicia' => round($produccionVitalicia, 2),
                'lactancias'           => $listaLactancias,
            ];
        }

        return [
            'finca' => [
                'id'     => $finca->id,
                'nombre' => $finca->nombre,
            ],
            'total_animales'         => count($resultadoAnimales),
            'produccion_total_finca' => round($totalProduccionFinca, 2),
            'animales'               => $resultadoAnimales,
            'kpis' => [
                'total_animales'         => count($resultadoAnimales),
                'produccion_total_finca' => round($totalProduccionFinca, 2),
            ],
            'items' => $resultadoAnimales,
            'filtros_aplicados' => [
                'finca_id'  => $fincaId,
                'rebano_id' => $filters['rebano_id'] ?? null,
                'animal_id' => $filters['animal_id'] ?? null,
            ],
        ];
    }

    /**
     * Algoritmo Test Interval Method (TIM) para el cálculo estándar de producción láctea.
     *
     * @param mixed $startDate Fecha de inicio de la lactancia (DateTime, Carbon o string)
     * @param mixed $endDate Fecha de secado/fin de la lactancia
     * @param mixed $weighings Colección de registros de pesaje de leche
     * @param int $targetDays Días objetivo (ej. 244, 270, 305 o duración total)
     * @return float|null
     */
    public function calculateTIM($startDate, $endDate, $weighings, int $targetDays): ?float
    {
        if ($targetDays <= 0) {
            return 0.0;
        }

        if (!$weighings || $weighings->isEmpty()) {
            return null;
        }

        $start = $startDate instanceof DateTimeInterface
            ? new DateTime($startDate->format('Y-m-d H:i:s'))
            : new DateTime((string) $startDate);

        $totalYield = 0.0;
        $currentDays = 0;
        $lastDate = clone $start;
        $lastYield = 0.0;

        if ($endDate) {
            $end = $endDate instanceof DateTimeInterface
                ? new DateTime($endDate->format('Y-m-d H:i:s'))
                : new DateTime((string) $endDate);

            $totalDays = (int) $start->diff($end)->days;
            if ($totalDays < $targetDays) {
                $targetDays = $totalDays;
            }
        }

        if ($targetDays <= 0) {
            return 0.0;
        }

        foreach ($weighings as $index => $w) {
            $rawDate = $w->fecha_pesaje ?? $w->leche_fecha_pesaje ?? null;
            $rawYield = $w->pesaje_total ?? $w->leche_pesaje_Total ?? 0.0;

            if (!$rawDate) {
                continue;
            }

            $wDate = $rawDate instanceof DateTimeInterface
                ? new DateTime($rawDate->format('Y-m-d H:i:s'))
                : new DateTime((string) $rawDate);

            $wYield = (float) $rawYield;
            $daysSinceStart = (int) $start->diff($wDate)->days;

            if ($daysSinceStart > $targetDays) {
                $allowedIntervalDays = $targetDays - $currentDays;
                if ($index === 0) {
                    $totalYield += $allowedIntervalDays * $wYield;
                } else {
                    $intervalDays = (int) $lastDate->diff($wDate)->days;
                    if ($intervalDays > 0) {
                        $yieldAtTarget = $lastYield + (($wYield - $lastYield) * ($allowedIntervalDays / $intervalDays));
                        $totalYield += (($lastYield + $yieldAtTarget) / 2) * $allowedIntervalDays;
                    }
                }
                $currentDays = $targetDays;
                break;
            }

            $intervalDays = (int) $lastDate->diff($wDate)->days;
            if ($index === 0) {
                $totalYield += $intervalDays * $wYield;
            } else {
                $totalYield += (($lastYield + $wYield) / 2) * $intervalDays;
            }

            $lastDate = $wDate;
            $lastYield = $wYield;
            $currentDays = $daysSinceStart;
        }

        if ($currentDays < $targetDays) {
            $remainingDays = $targetDays - $currentDays;
            $totalYield += $remainingDays * $lastYield;
        }

        return round($totalYield, 2);
    }
}

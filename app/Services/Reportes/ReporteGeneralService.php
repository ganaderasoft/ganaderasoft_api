<?php

namespace App\Services\Reportes;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\PersonalFinca;
use App\Models\PesoCorporal;
use App\Models\Rebano;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para la generación del Reporte General de Finca / Inventario Ganadero.
 */
class ReporteGeneralService extends BaseService
{
    /**
     * Genera la estructura de datos para el Reporte General.
     *
     * @param array $filters Filtros opcionales (fecha_inicio, fecha_fin, finca_id, rebano_id, etc.)
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array Estructura con KPIs y detalle consolidado de animales/fincas.
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

        $fincasQuery = Finca::where('archivado', false);
        $this->applyFincaFilter($fincasQuery, $user, null, 'id');

        $fincas = $fincasQuery->get();
        if ($fincas->isEmpty()) {
            throw new ModelNotFoundException('No se encontraron fincas para el reporte.');
        }

        $fincaIds = $fincas->pluck('id')->toArray();

        // 1. Rebaños
        $rebanos = Rebano::whereIn('finca_id', $fincaIds)
            ->where('archivado', false)
            ->when(!empty($filters['rebano_id']), fn($q) => $q->where('id', $filters['rebano_id']))
            ->get();

        $rebanoIds = $rebanos->pluck('id')->toArray();
        $totalRebanos = $rebanos->count();

        // 2. Animales
        $animalesQuery = Animal::query()
            ->whereIn('rebano_id', $rebanoIds)
            ->with([
                'rebano.finca',
                'composicionRaza',
                'etapaActual.etapa',
                'estadoActual.estadoSalud',
                'registroPadre.progenitor',
                'registroMadre.progenitor',
            ]);

        if (isset($filters['archivado'])) {
            if ($filters['archivado'] === 'true' || $filters['archivado'] === true || $filters['archivado'] === '1' || $filters['archivado'] === 1) {
                $animalesQuery->where('archivado', true);
            } elseif ($filters['archivado'] === 'todos') {
                // Sin filtro
            } else {
                $animalesQuery->where('archivado', false);
            }
        } else {
            $animalesQuery->where('archivado', false);
        }

        if (!empty($fechaInicio)) {
            $animalesQuery->where('fecha_nacimiento', '>=', $fechaInicio);
        }
        if (!empty($fechaFin)) {
            $animalesQuery->where('fecha_nacimiento', '<=', $fechaFin);
        }

        $animales = $animalesQuery->orderBy('nombre')->get();
        $animalIds = $animales->pluck('id')->toArray();
        $totalAnimales = $animales->count();

        // 3. Pesos corporales (ingreso, penúltimo, último)
        $pesosPorAnimal = [];
        if (!empty($animalIds)) {
            $pesos = PesoCorporal::whereHas('etapaAnimal', function ($q) use ($animalIds) {
                $q->whereIn('animal_id', $animalIds);
            })
            ->with('etapaAnimal')
            ->orderBy('fecha_peso', 'asc')
            ->get();

            foreach ($pesos as $p) {
                $aId = $p->etapaAnimal?->animal_id;
                if ($aId) {
                    $pesosPorAnimal[$aId][] = $p;
                }
            }
        }

        // 4. Personal registrado
        $totalPersonal = PersonalFinca::whereIn('finca_id', $fincaIds)->count();

        // 5. Mapear listado de animales formateado
        $animalesList = $animales->map(function ($animal) use ($pesosPorAnimal) {
            $pesosAnimal = $pesosPorAnimal[$animal->id] ?? [];
            $pesoIngreso = !empty($pesosAnimal) ? $pesosAnimal[0] : null;
            $ultimoPeso = !empty($pesosAnimal) ? end($pesosAnimal) : null;
            $penultimoPeso = count($pesosAnimal) > 1 ? $pesosAnimal[count($pesosAnimal) - 2] : null;

            return [
                'id'                   => $animal->id,
                'codigo'               => $animal->codigo_animal ?? (string) $animal->id,
                'nombre'               => $animal->nombre,
                'sexo'                 => $animal->sexo,
                'categoria'            => $animal->etapaActual?->etapa?->nombre ?? 'S/C',
                'estatus'              => $animal->estadoActual?->estadoSalud?->nombre ?? ($animal->archivado ? 'Archivado' : 'Activo'),
                'rebano_nombre'        => $animal->rebano?->nombre ?? 'Sin rebaño',
                'finca_nombre'         => $animal->rebano?->finca?->nombre ?? 'Sin finca',
                'edad_meses'           => $animal->edad_en_meses ?? null,
                'edad_formateada'      => $animal->edad_formateada,
                'fecha_nacimiento'     => $animal->fecha_nacimiento ? $animal->fecha_nacimiento->format('Y-m-d') : null,
                'raza'                 => $animal->composicionRaza?->nombre ?? ($animal->composicionRaza?->siglas ?? 'S/R'),
                'archivado'            => (bool) $animal->archivado,
                'peso_ingreso'         => $pesoIngreso ? (float) $pesoIngreso->peso : null,
                'fecha_ingreso'        => $pesoIngreso && $pesoIngreso->fecha_peso ? $pesoIngreso->fecha_peso->format('Y-m-d') : null,
                'penultimo_peso'       => $penultimoPeso ? (float) $penultimoPeso->peso : null,
                'fecha_penultimo_peso' => $penultimoPeso && $penultimoPeso->fecha_peso ? $penultimoPeso->fecha_peso->format('Y-m-d') : null,
                'ultimo_peso'          => $ultimoPeso ? (float) $ultimoPeso->peso : null,
                'fecha_ultimo_peso'    => $ultimoPeso && $ultimoPeso->fecha_peso ? $ultimoPeso->fecha_peso->format('Y-m-d') : null,
                'padre_id'             => $animal->registroPadre?->padre_id,
                'padre_codigo'         => $animal->registroPadre?->progenitor?->codigo_animal ?? $animal->registroPadre?->progenitor?->nombre,
                'madre_id'             => $animal->registroMadre?->padre_id,
                'madre_codigo'         => $animal->registroMadre?->progenitor?->codigo_animal ?? $animal->registroMadre?->progenitor?->nombre,
            ];
        })->values()->toArray();

        // 6. Agrupación por Finca / Categoría para tabla ejecutiva (items)
        $itemsAgrupados = [];
        $grupos = $animales->groupBy(function ($an) {
            $fincaNom = $an->rebano?->finca?->nombre ?? 'Finca Principal';
            $etapaNom = $an->etapaActual?->etapa?->nombre ?? 'General';
            return $fincaNom . '|' . $etapaNom;
        });

        foreach ($grupos as $key => $grupoAnimales) {
            [$fincaNom, $categoriaNom] = explode('|', $key);
            $machos = $grupoAnimales->whereIn('sexo', ['M', 'MACHO', 'Macho', 'macho'])->count();
            $hembras = $grupoAnimales->whereIn('sexo', ['H', 'F', 'HEMBRA', 'Hembra', 'hembra'])->count();
            
            $primerAnimal = $grupoAnimales->first();
            $estadoSalud = $primerAnimal?->estadoActual?->estadoSalud?->nombre ?? ($primerAnimal?->archivado ? 'Archivado' : 'Sano');

            $itemsAgrupados[] = [
                'finca_nombre'       => $fincaNom,
                'categoria'          => $categoriaNom,
                'cantidad_animales'  => $grupoAnimales->count(),
                'estado_nutricional' => $estadoSalud,
                'observacion'        => "{$hembras} hembra(s), {$machos} macho(s)",
            ];
        }

        // 7. Resumen de fincas y rebaños
        $rebanosPorFinca = Rebano::whereIn('finca_id', $fincaIds)
            ->where('archivado', false)
            ->select('finca_id', DB::raw('COUNT(*) as cantidad_rebanos'))
            ->groupBy('finca_id')
            ->pluck('cantidad_rebanos', 'finca_id');

        $animalesPorFinca = Animal::join('rebanos', 'animals.rebano_id', '=', 'rebanos.id')
            ->whereIn('rebanos.finca_id', $fincaIds)
            ->where('animals.archivado', false)
            ->select('rebanos.finca_id', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('rebanos.finca_id')
            ->pluck('cantidad_animales', 'finca_id');

        $personalPorFinca = PersonalFinca::whereIn('finca_id', $fincaIds)
            ->select('finca_id', DB::raw('COUNT(*) as cantidad_personal'))
            ->groupBy('finca_id')
            ->pluck('cantidad_personal', 'finca_id');

        $fincasDetalle = $fincas->map(function ($f) use ($rebanosPorFinca, $animalesPorFinca, $personalPorFinca) {
            return [
                'id'                => $f->id,
                'finca_id'          => $f->id,
                'nombre'            => $f->nombre,
                'cantidad_rebanos'  => $rebanosPorFinca[$f->id] ?? 0,
                'cantidad_animales' => $animalesPorFinca[$f->id] ?? 0,
                'cantidad_personal' => $personalPorFinca[$f->id] ?? 0,
            ];
        })->values()->toArray();

        $animalesPorRebano = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->select('rebano_id', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('rebano_id')
            ->pluck('cantidad_animales', 'rebano_id');

        $rebanosDetalle = $rebanos->map(function ($r) use ($animalesPorRebano) {
            return [
                'rebano_id'         => $r->id,
                'finca_id'          => $r->finca_id,
                'nombre'            => $r->nombre,
                'cantidad_animales' => $animalesPorRebano[$r->id] ?? 0,
            ];
        })->values()->toArray();

        $primeraFinca = $fincas->first();

        return [
            'finca' => $primeraFinca ? [
                'id'               => $primeraFinca->id,
                'nombre'           => $primeraFinca->nombre,
                'explotacion_tipo' => $primeraFinca->explotacion_tipo,
                'propietario_id'   => $primeraFinca->propietario_id,
            ] : null,
            'kpis' => [
                'total_animales'      => $totalAnimales,
                'rebanos_activos'     => $totalRebanos,
                'personal_registrado' => $totalPersonal,
            ],
            'resumen' => [
                'total_animales'      => $totalAnimales,
                'total_rebanos'       => $totalRebanos,
                'total_personal'      => $totalPersonal,
            ],
            'items'             => $itemsAgrupados,
            'animales'          => $animalesList,
            'total_animales'    => $totalAnimales,
            'fincas'            => $fincasDetalle,
            'rebanos'           => $rebanosDetalle,
            'filtros_aplicados' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'rebano_id'    => $filters['rebano_id'] ?? null,
            ],
        ];
    }
}

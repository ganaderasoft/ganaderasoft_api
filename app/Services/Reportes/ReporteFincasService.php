<?php

namespace App\Services\Reportes;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\PersonalFinca;
use App\Models\Rebano;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para la generación del Reporte Estadístico Consolidado de Fincas.
 */
class ReporteFincasService extends BaseService
{
    /**
     * Genera el reporte estadístico consolidado de fincas.
     *
     * @param array $filters Filtros de consulta (propietario_id, finca_id).
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array
     *
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getEstadisticasFincas(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincasQuery = Finca::where('archivado', false);

        if (!empty($filters['propietario_id'])) {
            $fincasQuery->where('propietario_id', $filters['propietario_id']);
        }

        $this->applyFincaFilter($fincasQuery, $user, null, 'id');

        if (isset($filters['finca_id'])) {
            $fincasQuery->where('id', $filters['finca_id']);
        }

        $fincas = $fincasQuery->get();

        if ($fincas->isEmpty()) {
            throw new ModelNotFoundException('No se encontraron fincas');
        }

        $fincaIds = $fincas->pluck('id')->toArray();

        // 1. Total Fincas
        $totalFincas = $fincas->count();

        // 2. Rebaños
        $rebanos = Rebano::whereIn('finca_id', $fincaIds)
            ->where('archivado', false)
            ->get();
        $totalRebanos = $rebanos->count();
        $rebanoIds = $rebanos->pluck('id')->toArray();

        $rebanosPorFinca = Rebano::whereIn('finca_id', $fincaIds)
            ->where('archivado', false)
            ->select('finca_id', DB::raw('COUNT(*) as cantidad_rebanos'))
            ->groupBy('finca_id')
            ->pluck('cantidad_rebanos', 'finca_id');

        // 3. Animales
        $totalAnimales = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->count();

        $animalesPorSexo = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->select('sexo', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('sexo')
            ->pluck('cantidad', 'sexo')
            ->toArray();

        $animalesPorRebano = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->select('rebano_id', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('rebano_id')
            ->pluck('cantidad_animales', 'rebano_id');

        $animalesPorFinca = Animal::join('rebanos', 'animals.rebano_id', '=', 'rebanos.id')
            ->whereIn('rebanos.finca_id', $fincaIds)
            ->where('animals.archivado', false)
            ->where('rebanos.archivado', false)
            ->select('rebanos.finca_id', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('rebanos.finca_id')
            ->pluck('cantidad_animales', 'finca_id');

        // 4. Personal (solo personal activo)
        $totalPersonal = PersonalFinca::whereIn('finca_id', $fincaIds)->where('status', 'activo')->count();

        $personalPorTipo = PersonalFinca::whereIn('finca_id', $fincaIds)
            ->where('status', 'activo')
            ->select('tipo_trabajador_id', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('tipo_trabajador_id')
            ->pluck('cantidad', 'tipo_trabajador_id')
            ->toArray();

        $personalPorFinca = PersonalFinca::whereIn('finca_id', $fincaIds)
            ->where('status', 'activo')
            ->select('finca_id', DB::raw('COUNT(*) as cantidad_personal'))
            ->groupBy('finca_id')
            ->pluck('cantidad_personal', 'finca_id');

        // Detalles
        $fincasDetalle = $fincas->map(function ($finca) use ($rebanosPorFinca, $animalesPorFinca, $personalPorFinca) {
            return [
                'finca_id' => $finca->id,
                'nombre' => $finca->nombre,
                'cantidad_rebanos' => $rebanosPorFinca[$finca->id] ?? 0,
                'cantidad_animales' => $animalesPorFinca[$finca->id] ?? 0,
                'cantidad_personal' => $personalPorFinca[$finca->id] ?? 0,
            ];
        })->values()->toArray();

        $rebanosDetalle = $rebanos->map(function ($rebano) use ($animalesPorRebano) {
            return [
                'rebano_id' => $rebano->id,
                'finca_id' => $rebano->finca_id,
                'nombre' => $rebano->nombre,
                'cantidad_animales' => $animalesPorRebano[$rebano->id] ?? 0,
            ];
        })->values()->toArray();

        return [
            'resumen' => [
                'total_fincas' => $totalFincas,
                'total_rebanos' => $totalRebanos,
                'total_animales' => $totalAnimales,
                'total_personal' => $totalPersonal,
            ],
            'animales_por_sexo' => $animalesPorSexo,
            'personal_por_tipo' => $personalPorTipo,
            'fincas' => $fincasDetalle,
            'rebanos' => $rebanosDetalle,
        ];
    }
}

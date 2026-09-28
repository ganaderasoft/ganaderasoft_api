<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reportes\EstadisticasFincasResource;
use App\Http\Resources\Reportes\ReporteGeneralResource;
use App\Http\Resources\Reportes\ReporteHistorialLactanciaResource;
use App\Http\Resources\Reportes\ReportePesajeLecheResource;
use App\Http\Resources\Reportes\ReporteReproductivoResource;
use App\Services\Reportes\ReporteFincasService;
use App\Services\Reportes\ReporteGeneralService;
use App\Services\Reportes\ReporteHistorialLactanciaService;
use App\Services\Reportes\ReportePesajeLecheService;
use App\Services\Reportes\ReporteReproductivoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controlador de endpoints de Reportes del Sistema.
 */
class ReportesController extends Controller
{
    public function __construct(
        protected ReporteFincasService $reporteFincasService,
        protected ReporteGeneralService $reporteGeneralService,
        protected ReporteHistorialLactanciaService $reporteHistorialLactanciaService,
        protected ReporteReproductivoService $reporteReproductivoService,
        protected ReportePesajeLecheService $reportePesajeLecheService
    ) {
        $this->middleware(\App\Http\Middleware\Legacy\Reportes\NormalizeEstadisticasFincas::class)->only('estadisticasFincas');
    }

    /**
     * Obtiene el reporte estadístico consolidado de fincas.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estadisticasFincas(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['propietario_id', 'finca_id']);
            $estadisticas = $this->reporteFincasService->getEstadisticasFincas($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas de fincas',
                'data' => $this->formatResource(EstadisticasFincasResource::class, $estadisticas),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Reporte General de Finca (Animales, pesos, genealogía).
     */
    public function general(Request $request): JsonResponse
    {
        try {
            $data = $this->reporteGeneralService->generar($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte general obtenido exitosamente.',
                'data' => $this->formatResource(ReporteGeneralResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Reporte de Historia de Lactancias con cálculo TIM (P244, P270, P305).
     */
    public function lactancias(Request $request): JsonResponse
    {
        try {
            $data = $this->reporteHistorialLactanciaService->generar($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte de lactancias obtenido exitosamente.',
                'data' => $this->formatResource(ReporteHistorialLactanciaResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Reporte de Historial Reproductivo Consolidado (Partos + Servicios).
     */
    public function reproductivo(Request $request): JsonResponse
    {
        try {
            $data = $this->reporteReproductivoService->generar($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte reproductivo obtenido exitosamente.',
                'data' => $this->formatResource(ReporteReproductivoResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Reporte de Histórico de Pesajes de Leche.
     */
    public function pesajeLeche(Request $request): JsonResponse
    {
        try {
            $data = $this->reportePesajeLecheService->generar($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte de pesaje de leche obtenido exitosamente.',
                'data' => $this->formatResource(ReportePesajeLecheResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

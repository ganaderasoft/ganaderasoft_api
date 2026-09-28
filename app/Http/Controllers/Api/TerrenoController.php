<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finca\TerrenoService;
use App\Http\Resources\Finca\TerrenoResource;
use App\Http\Middleware\Legacy\Terreno\NormalizeIndex;
use App\Http\Middleware\Legacy\Terreno\NormalizeStore;
use App\Http\Middleware\Legacy\Terreno\NormalizeShow;
use App\Http\Middleware\Legacy\Terreno\NormalizeUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TerrenoController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de Terreno y registra los middlewares de compatibilidad legacy.
     */
    public function __construct(
        private TerrenoService $terrenoService
    ) {
        $this->middleware(NormalizeIndex::class)->only('index');
        $this->middleware(NormalizeStore::class)->only('store');
        $this->middleware(NormalizeShow::class)->only('show');
        $this->middleware(NormalizeUpdate::class)->only('update');
    }

    /**
     * Display a listing of terrains.
     */
    public function index(Request $request)
    {
        try {
            $terrenos = $this->terrenoService->listTerrenos($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de terrenos',
                'data' => $this->formatCollection(TerrenoResource::class, $terrenos)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Store a newly created terrain.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'required|exists:fincas,id',
            'superficie' => 'nullable|numeric|min:0',
            'relieve' => 'nullable|string|max:9',
            'suelo_textura' => 'nullable|string|max:25',
            'ph_suelo' => 'nullable|string|max:2',
            'precipitacion' => 'nullable|numeric|min:0',
            'velocidad_viento' => 'nullable|numeric|min:0',
            'temp_anual' => 'nullable|string|max:4',
            'temp_min' => 'nullable|string|max:4',
            'temp_max' => 'nullable|string|max:4',
            'radiacion' => 'nullable|numeric|min:0',
            'fuente_agua' => 'nullable|string|max:25',
            'caudal_disponible' => 'nullable|integer|min:0',
            'riego_metodo' => 'nullable|string|max:18',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $terreno = $this->terrenoService->storeTerreno($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Terreno creado exitosamente',
                'data' => $this->formatResource(TerrenoResource::class, $terreno)
            ], Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Display the specified terrain.
     */
    public function show(Request $request, $id)
    {
        try {
            $terreno = $this->terrenoService->getTerreno((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de terreno',
                'data' => $this->formatResource(TerrenoResource::class, $terreno)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terreno no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Update the specified terrain.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'sometimes|exists:fincas,id',
            'superficie' => 'nullable|numeric|min:0',
            'relieve' => 'nullable|string|max:9',
            'suelo_textura' => 'nullable|string|max:25',
            'ph_suelo' => 'nullable|string|max:2',
            'precipitacion' => 'nullable|numeric|min:0',
            'velocidad_viento' => 'nullable|numeric|min:0',
            'temp_anual' => 'nullable|string|max:4',
            'temp_min' => 'nullable|string|max:4',
            'temp_max' => 'nullable|string|max:4',
            'radiacion' => 'nullable|numeric|min:0',
            'fuente_agua' => 'nullable|string|max:25',
            'caudal_disponible' => 'nullable|integer|min:0',
            'riego_metodo' => 'nullable|string|max:18',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $terreno = $this->terrenoService->updateTerreno((int) $id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Terreno actualizado exitosamente',
                'data' => $this->formatResource(TerrenoResource::class, $terreno)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terreno no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Remove the specified terrain (physical delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->terrenoService->deleteTerreno((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Terreno eliminado exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terreno no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}

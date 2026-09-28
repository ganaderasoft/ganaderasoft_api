<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Animal\EtapaResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndexEtapa;
use App\Http\Middleware\Legacy\Animal\NormalizeShowEtapa;
use App\Http\Middleware\Legacy\Animal\NormalizeStoreEtapa;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateEtapa;
use App\Models\Etapa;
use App\Services\Animal\EtapaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EtapaController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de etapas y registra los middlewares legacy por método.
     *
     * @param EtapaService $etapaService
     */
    public function __construct(
        protected EtapaService $etapaService
    ) {
        $this->middleware(NormalizeIndexEtapa::class)->only('index');
        $this->middleware(NormalizeShowEtapa::class)->only('show');
        $this->middleware(NormalizeStoreEtapa::class)->only('store');
        $this->middleware(NormalizeUpdateEtapa::class)->only('update');
    }

    /**
     * Devuelve el listado filtrado de etapas.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(['tipo_animal_id', 'sexo', 'nombre', 'nopaginate']);
        $paginator = $this->etapaService->listEtapas($filters, $request->user());
        
        return response()->json([
            'success' => true,
            'message' => 'Lista de etapas obtenida exitosamente',
            'data' => $this->formatCollection(EtapaResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra una nueva etapa.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'         => 'required|string|max:40',
            'edad_ini'       => 'required|integer|min:0',
            'edad_fin'       => 'nullable|integer|min:0|gt:edad_ini',
            'tipo_animal_id' => 'required|exists:tipo_animals,id',
            'sexo'           => 'nullable|in:M,H',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $etapa = $this->etapaService->createEtapa($request->all(), $request->user());
            $etapa->load(['tipoAnimal']);
            
            return response()->json([
                'success' => true,
                'message' => 'Etapa creada exitosamente',
                'data' => $this->formatResource(EtapaResource::class, $etapa)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de una etapa específica.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $etapa = $this->etapaService->getEtapaById((int)$id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Etapa obtenida exitosamente',
                'data' => $this->formatResource(EtapaResource::class, $etapa)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Actualiza los datos de una etapa.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre'         => 'sometimes|string|max:40',
            'edad_ini'       => [
                'sometimes',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $etapa = Etapa::find($id);
                    if (!$etapa) return;
                    $edadFin = $request->has('edad_fin') ? $request->input('edad_fin') : $etapa->edad_fin;
                    if ($edadFin !== null && $value >= $edadFin) {
                        $fail('El campo edad ini debe ser menor que edad fin.');
                    }
                }
            ],
            'edad_fin'       => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $etapa = Etapa::find($id);
                    if (!$etapa) return;
                    $edadIni = $request->has('edad_ini') ? $request->input('edad_ini') : $etapa->edad_ini;
                    if ($value !== null && $value <= $edadIni) {
                        $fail('El campo edad fin debe ser mayor que edad ini.');
                    }
                }
            ],
            'tipo_animal_id' => 'sometimes|exists:tipo_animals,id',
            'sexo'           => 'nullable|in:M,H',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $etapa = $this->etapaService->updateEtapa((int)$id, $request->all(), $request->user());
            $etapa->load(['tipoAnimal']);
            
            return response()->json([
                'success' => true,
                'message' => 'Etapa actualizada exitosamente',
                'data' => $this->formatResource(EtapaResource::class, $etapa)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina una etapa del catálogo.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->etapaService->deleteEtapa((int)$id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Etapa eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_CONFLICT);
        }
    }
}
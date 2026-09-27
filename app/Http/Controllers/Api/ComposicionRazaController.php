<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Animal\ComposicionRazaResource;
use App\Http\Middleware\Legacy\Animal\NormalizeIndexComposicionRaza;
use App\Http\Middleware\Legacy\Animal\NormalizeShowComposicionRaza;
use App\Http\Middleware\Legacy\Animal\NormalizeStoreComposicionRaza;
use App\Http\Middleware\Legacy\Animal\NormalizeUpdateComposicionRaza;
use App\Services\Animal\ComposicionRazaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ComposicionRazaController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de composición y registra los middlewares legacy por método.
     *
     * @param ComposicionRazaService $composicionService
     */
    public function __construct(
        protected ComposicionRazaService $composicionService
    ) {
        $this->middleware(NormalizeIndexComposicionRaza::class)->only('index');
        $this->middleware(NormalizeShowComposicionRaza::class)->only('show');
        $this->middleware(NormalizeStoreComposicionRaza::class)->only('store');
        $this->middleware(NormalizeUpdateComposicionRaza::class)->only('update');
    }

    /**
     * Devuelve el listado filtrado de composiciones de raza.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(['nombre', 'nopaginate']);
        $paginator = $this->composicionService->listComposiciones($filters, $request->user());
        
        return response()->json([
            'success' => true,
            'message' => 'Lista de composiciones de raza obtenida exitosamente',
            'data' => $this->formatCollection(ComposicionRazaResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra una nueva composición de raza.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'                  => 'required|string|max:30',
            'siglas'                  => 'nullable|string|max:6',
            'pelaje'                  => 'nullable|string|max:80',
            'proposito'               => 'nullable|string|max:15',
            'tipo_raza'               => 'nullable|string|max:12',
            'origen'                  => 'nullable|string|max:60',
            'caracteristica_especial' => 'nullable|string|max:80',
            'proporcion_raza'         => 'nullable|string|max:7',
            'finca_id'                => 'nullable|exists:fincas,id',
            'tipo_animal_id'          => 'nullable|exists:tipo_animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $composicion = $this->composicionService->createComposicion($request->all(), $request->user());
            $composicion->load(['finca', 'tipoAnimal']);
            
            return response()->json([
                'success' => true,
                'message' => 'Composición de raza creada exitosamente',
                'data' => $this->formatResource(ComposicionRazaResource::class, $composicion)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Devuelve el detalle de una composición de raza específica.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $composicion = $this->composicionService->getComposicionById((int)$id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Composición de raza obtenida exitosamente',
                'data' => $this->formatResource(ComposicionRazaResource::class, $composicion)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Composición de raza no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de una composición de raza.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre'                  => 'sometimes|string|max:30',
            'siglas'                  => 'nullable|string|max:6',
            'pelaje'                  => 'nullable|string|max:80',
            'proposito'               => 'nullable|string|max:15',
            'tipo_raza'               => 'nullable|string|max:12',
            'origen'                  => 'nullable|string|max:60',
            'caracteristica_especial' => 'nullable|string|max:80',
            'proporcion_raza'         => 'nullable|string|max:7',
            'finca_id'                => 'nullable|exists:fincas,id',
            'tipo_animal_id'          => 'nullable|exists:tipo_animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $composicion = $this->composicionService->updateComposicion((int)$id, $request->all(), $request->user());
            $composicion->load(['finca', 'tipoAnimal']);
            
            return response()->json([
                'success' => true,
                'message' => 'Composición de raza actualizada exitosamente',
                'data' => $this->formatResource(ComposicionRazaResource::class, $composicion)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Composición de raza no encontrada'
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

    /**
     * Elimina una composición de raza.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->composicionService->deleteComposicion((int)$id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Composición de raza eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Composición de raza no encontrada'
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
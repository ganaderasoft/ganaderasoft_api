<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Legacy\Rebano\NormalizeIndex;
use App\Http\Middleware\Legacy\Rebano\NormalizeShow;
use App\Http\Middleware\Legacy\Rebano\NormalizeStore;
use App\Http\Middleware\Legacy\Rebano\NormalizeUpdate;
use App\Http\Resources\Rebano\RebanoResource;
use App\Services\Rebano\RebanoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RebanoController extends Controller
{
    protected $rebanoService;

    public function __construct(RebanoService $rebanoService)
    {
        $this->rebanoService = $rebanoService;
        
        $this->middleware(NormalizeIndex::class)->only('index');
        $this->middleware(NormalizeShow::class)->only('show');
        $this->middleware(NormalizeStore::class)->only('store');
        $this->middleware(NormalizeUpdate::class)->only('update');
    }

    /**
     * Display a listing of rebanos.
     */
    public function index(Request $request)
    {
        try {
            $rebanos = $this->rebanoService->listRebanos($request->all(), $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Lista de rebaños',
                'data' => $this->formatCollection(RebanoResource::class, $rebanos)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Store a newly created rebano.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'required|exists:fincas,id',
            'nombre' => 'required|string|max:25'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $rebano = $this->rebanoService->storeRebano($request->all(), $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Rebaño creado exitosamente',
                'data' => $this->formatResource(RebanoResource::class, $rebano)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Display the specified rebano.
     */
    public function show(Request $request, $id)
    {
        try {
            $rebano = $this->rebanoService->getRebano($id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Detalle de rebaño',
                'data' => $this->formatResource(RebanoResource::class, $rebano)
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Update the specified rebano.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:25',
            'finca_id' => 'sometimes|exists:fincas,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $rebano = $this->rebanoService->updateRebano($id, $request->all(), $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Rebaño actualizado exitosamente',
                'data' => $this->formatResource(RebanoResource::class, $rebano)
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Remove the specified rebano (hard delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->rebanoService->deleteRebano((int) $id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Rebaño eliminado exitosamente'
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
     * Archivar un rebaño específico.
     */
    public function archive(Request $request, $id)
    {
        try {
            $rebano = $this->rebanoService->archiveRebano((int) $id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Rebaño archivado exitosamente',
                'data' => $this->formatResource(RebanoResource::class, $rebano)
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Desarchivar un rebaño específico.
     */
    public function unarchive(Request $request, $id)
    {
        try {
            $rebano = $this->rebanoService->unarchiveRebano((int) $id, $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Rebaño desarchivado exitosamente',
                'data' => $this->formatResource(RebanoResource::class, $rebano)
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rebano\MovimientoRebanoIndexResource;
use App\Http\Resources\Rebano\MovimientoRebanoShowResource;
use App\Http\Resources\Rebano\MovimientoRebanoStoreResource;
use App\Http\Middleware\Legacy\Rebano\NormalizeIndexMovimientoRebano;
use App\Http\Middleware\Legacy\Rebano\NormalizeShowMovimientoRebano;
use App\Http\Middleware\Legacy\Rebano\NormalizeStoreMovimientoRebano;
use App\Http\Middleware\Legacy\Rebano\NormalizeUpdateMovimientoRebano;
use App\Services\Rebano\MovimientoRebanoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class MovimientoRebanoController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de traslados y registra los middlewares legacy correspondientes.
     *
     * @param MovimientoRebanoService $movimientoService
     */
    public function __construct(
        protected MovimientoRebanoService $movimientoService
    ) {
        $this->middleware(NormalizeIndexMovimientoRebano::class)->only('index');
        $this->middleware(NormalizeShowMovimientoRebano::class)->only('show');
        $this->middleware(NormalizeStoreMovimientoRebano::class)->only('store');
        $this->middleware(NormalizeUpdateMovimientoRebano::class)->only('update');
    }

    /**
     * Devuelve el listado de movimientos de rebaño.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(['finca_id', 'rebano_id', 'nopaginate']);
        $paginator = $this->movimientoService->listMovimientos($filters, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Movimientos de rebaño obtenidos exitosamente',
            'data'    => $this->formatCollection(MovimientoRebanoIndexResource::class, $paginator)
        ], Response::HTTP_OK);
    }

    /**
     * Registra un nuevo movimiento de rebaño y traslada los animales.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id'          => 'required|exists:fincas,id',
            'rebano_id'         => 'required|exists:rebanos,id',
            'rebano_destino'    => 'nullable|string|max:30',
            'finca_destino_id'  => 'required|exists:fincas,id',
            'rebano_destino_id' => 'required|exists:rebanos,id',
            'comentario'        => 'nullable|string|max:40',
            'animales'          => 'required|array|min:1',
            'animales.*'        => 'exists:animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $movimiento = $this->movimientoService->createMovimiento($request->all(), request()->user());
            $movimiento->load('animales');

            return response()->json([
                'success' => true,
                'message' => 'Movimiento registrado exitosamente',
                'data'    => $this->formatResource(MovimientoRebanoStoreResource::class, $movimiento),
            ], Response::HTTP_CREATED);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Devuelve el detalle de un movimiento específico.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $movimiento = $this->movimientoService->getMovimientoById((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'data'    => $this->formatResource(MovimientoRebanoShowResource::class, $movimiento)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Actualiza los datos descriptivos de un traslado.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rebano_destino' => 'nullable|string|max:30',
            'comentario'     => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $movimiento = $this->movimientoService->updateMovimiento((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Movimiento actualizado exitosamente',
                'data'    => $this->formatResource(MovimientoRebanoIndexResource::class, $movimiento)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Elimina un registro de traslado físico.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->movimientoService->deleteMovimiento((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Movimiento eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

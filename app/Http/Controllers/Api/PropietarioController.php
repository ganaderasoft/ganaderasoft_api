<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Persona\PropietarioService;
use App\Http\Resources\Persona\PropietarioResource;
use App\Http\Middleware\Legacy\Propietario\NormalizeIndex;
use App\Http\Middleware\Legacy\Propietario\NormalizeStore;
use App\Http\Middleware\Legacy\Propietario\NormalizeShow;
use App\Http\Middleware\Legacy\Propietario\NormalizeUpdate;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PropietarioController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta el servicio de Propietario y registra los middlewares de compatibilidad legacy.
     */
    public function __construct(
        private PropietarioService $propietarioService
    ) {
        $this->middleware(NormalizeIndex::class)->only('index');
        $this->middleware(NormalizeStore::class)->only('store');
        $this->middleware(NormalizeShow::class)->only('show');
        $this->middleware(NormalizeUpdate::class)->only('update');
    }

    /**
     * Display a listing of propietarios.
     */
    public function index(Request $request)
    {
        try {
            $propietarios = $this->propietarioService->listPropietarios($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de propietarios',
                'data' => $this->formatCollection(PropietarioResource::class, $propietarios)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Store a newly created propietario.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'cedula' => ['required', 'string', 'unique:personas,cedula', 'regex:/^[VGEJ][0-9]+$/'],
            'correo' => 'nullable|email|unique:personas,correo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $propietario = $this->propietarioService->storePropietario($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Propietario creado exitosamente',
                'data' => $this->formatResource(PropietarioResource::class, $propietario)
            ], Response::HTTP_CREATED);
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
     * Display the specified propietario.
     */
    public function show(Request $request, $id)
    {
        try {
            $propietario = $this->propietarioService->getPropietario((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de propietario',
                'data' => $this->formatResource(PropietarioResource::class, $propietario)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Propietario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Update the specified propietario.
     */
    public function update(Request $request, $id)
    {
        try {
            $propietario = Propietario::findOrFail((int) $id);
            $personaId = $propietario->persona_id;
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Propietario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'cedula' => ['sometimes', 'string', 'unique:personas,cedula,' . $personaId . ',id', 'regex:/^[VGEJ][0-9]+$/'],
            'correo' => 'nullable|email|unique:personas,correo,' . $personaId . ',id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $propietario = $this->propietarioService->updatePropietario((int) $id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Propietario actualizado exitosamente',
                'data' => $this->formatResource(PropietarioResource::class, $propietario)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Remove the specified propietario (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->propietarioService->archivePropietario((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Propietario eliminado exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Propietario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
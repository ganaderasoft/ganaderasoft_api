<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\Personal\PersonalFincaService;
use App\Http\Resources\Personal\PersonalFincaResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\PersonalFinca;
use App\Models\User;
use Exception;

class PersonalFincaController extends Controller
{
    protected $personalFincaService;

    public function __construct(PersonalFincaService $personalFincaService)
    {
        $this->personalFincaService = $personalFincaService;
        
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeIndex::class)->only('index');
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeStore::class)->only('store');
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeShow::class)->only('show');
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeUpdate::class)->only('update');
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['finca_id', 'tipo_trabajador_id', 'nombre', 'status', 'nopaginate', 'incluir_inactivos']);
            $personal = $this->personalFincaService->listPersonal($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de personal de finca obtenida exitosamente',
                'data' => $this->formatCollection(PersonalFincaResource::class, $personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'required|exists:fincas,id',
            'cedula' => 'required|string|regex:/^[VEJPG][0-9]+$/',
            'nombre' => 'required|string|max:25',
            'apellido' => 'required|string|max:25',
            'telefono' => 'required|string|max:15',
            'correo' => 'required|email|max:40',
            'fecha_nacimiento' => 'nullable|date',
            'tipo_trabajador_id' => 'required|exists:tipo_trabajadors,id',
            'status' => 'nullable|in:activo,inactivo,1,0,true,false,True,False',
            'fecha_ingreso' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $personal = $this->personalFincaService->storePersonal($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca creado exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $personal = $this->personalFincaService->getPersonal($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca obtenido exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'sometimes|exists:fincas,id',
            'cedula' => 'sometimes|string|regex:/^[VEJPG][0-9]+$/',
            'nombre' => 'sometimes|string|max:25',
            'apellido' => 'sometimes|string|max:25',
            'telefono' => 'sometimes|string|max:15',
            'correo' => 'sometimes|email|max:40',
            'fecha_nacimiento' => 'nullable|date',
            'tipo_trabajador_id' => 'sometimes|exists:tipo_trabajadors,id',
            'status' => 'sometimes|nullable|in:activo,inactivo,1,0,true,false,True,False',
            'fecha_ingreso' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $personal = $this->personalFincaService->updatePersonal($id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca actualizado exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->personalFincaService->deletePersonal($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function enable(Request $request, $id)
    {
        try {
            $personal = $this->personalFincaService->enable($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca activado exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function disable(Request $request, $id)
    {
        try {
            $personal = $this->personalFincaService->disable($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca desactivado exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function convertToUser(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|same:password',
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,code'
        ]);

        try {
            $personalFinca = PersonalFinca::findOrFail($id);
            $user = $this->personalFincaService->convertToUser($personalFinca, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Cuenta de usuario creada exitosamente',
                'data' => [
                    'user' => $user
                ]
            ], Response::HTTP_CREATED);

        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

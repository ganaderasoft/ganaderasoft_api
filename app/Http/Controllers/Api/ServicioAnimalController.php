<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reproduccion\ServicioAnimalResource;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeIndexServicioAnimal;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeShowServicioAnimal;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeStoreServicioAnimal;
use App\Http\Middleware\Legacy\Reproduccion\NormalizeUpdateServicioAnimal;
use App\Services\Reproduccion\ServicioAnimalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ServicioAnimalController extends Controller
{
    public function __construct(
        protected ServicioAnimalService $servicioService
    ) {
        $this->middleware(NormalizeIndexServicioAnimal::class)->only('index');
        $this->middleware(NormalizeShowServicioAnimal::class)->only('show');
        $this->middleware(NormalizeStoreServicioAnimal::class)->only('store');
        $this->middleware(NormalizeUpdateServicioAnimal::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'nopaginate', 'finca_id', 'rebano_id']);
        
        $records = $this->servicioService->getPaginatedServicios($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Registros de servicio obtenidos exitosamente',
            'data'    => $this->formatCollection(ServicioAnimalResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'animal_id'         => 'required|exists:animals,id',
            'semen_toro_id'     => 'nullable|exists:semen_toros,id',
            'personal_finca_id' => 'nullable|exists:personal_fincas,id',
            'registro_celo_id'  => 'nullable|exists:registro_celos,id',
            'tipo'              => 'nullable|string|max:16',
            'fecha'             => 'nullable|date',
            'observacion'       => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $servicio = $this->servicioService->createServicio($request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Servicio registrado exitosamente',
                'data'    => $this->formatResource(ServicioAnimalResource::class, $servicio),
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function show($id)
    {
        try {
            $servicio = $this->servicioService->getServicioById((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Servicio obtenido exitosamente',
                'data'    => $this->formatResource(ServicioAnimalResource::class, $servicio)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Servicio no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'animal_id'         => 'sometimes|exists:animals,id',
            'semen_toro_id'     => 'nullable|exists:semen_toros,id',
            'personal_finca_id' => 'nullable|exists:personal_fincas,id',
            'registro_celo_id'  => 'nullable|exists:registro_celos,id',
            'tipo'              => 'nullable|string|max:16',
            'fecha'             => 'nullable|date',
            'observacion'       => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $servicio = $this->servicioService->updateServicio((int)$id, $request->all(), request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado exitosamente',
                'data'    => $this->formatResource(ServicioAnimalResource::class, $servicio),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Servicio no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        try {
            $this->servicioService->deleteServicio((int)$id, request()->user());

            return response()->json([
                'success' => true, 
                'message' => 'Servicio eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Servicio no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}

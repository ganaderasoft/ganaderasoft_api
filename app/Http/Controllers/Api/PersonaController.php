<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Persona\PersonaService;
use App\Http\Resources\Persona\PersonaResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    protected $personaService;

    public function __construct(PersonaService $personaService)
    {
        $this->personaService = $personaService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'eligible_for_user', 'status', 'nopaginate']);
        $personas = $this->personaService->getPersonas($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Personas listadas exitosamente',
            'data' => $this->formatCollection(PersonaResource::class, $personas)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cedula' => ['required', 'string', 'max:255', 'unique:personas,cedula', 'regex:/^[VGEJ][0-9]+$/'],
            'nombre' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'correo' => 'nullable|email|unique:personas,correo|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'status' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $persona = $this->personaService->createPersona($request->all(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Persona creada exitosamente',
            'data' => $this->formatResource(PersonaResource::class, $persona)
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $persona = $this->personaService->getPersona($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Persona obtenida exitosamente',
            'data' => $this->formatResource(PersonaResource::class, $persona)
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cedula' => ['nullable', 'string', 'max:255', Rule::unique('personas')->ignore($id), 'regex:/^[VGEJ][0-9]+$/'],
            'nombre' => 'nullable|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'correo' => ['nullable', 'email', 'max:255', Rule::unique('personas')->ignore($id)],
            'fecha_nacimiento' => 'nullable|date',
        ]);

        $persona = $this->personaService->updatePersona($id, $request->all(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Persona actualizada exitosamente',
            'data' => $this->formatResource(PersonaResource::class, $persona)
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->personaService->deletePersona($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Persona eliminada exitosamente'
        ]);
    }

    public function disable(Request $request, $id)
    {
        $persona = $this->personaService->disablePersona($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Persona desactivada exitosamente',
            'data' => $this->formatResource(PersonaResource::class, $persona)
        ]);
    }

    public function enable(Request $request, $id)
    {
        $persona = $this->personaService->enablePersona($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Persona activada exitosamente',
            'data' => $this->formatResource(PersonaResource::class, $persona)
        ]);
    }
}

<?php

namespace App\Http\Resources\Personal;

use App\Http\Resources\Finca\FincaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalFincaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'finca_id' => $this->finca_id,
            'finca' => new FincaResource($this->whenLoaded('finca')),
            'status' => $this->status,
            'fecha_ingreso' => $this->fecha_ingreso,
            'persona' => [
                'id' => $this->persona->id ?? null,
                'cedula' => $this->persona->cedula ?? null,
                'nombre' => $this->persona->nombre ?? null,
                'apellido' => $this->persona->apellido ?? null,
                'telefono' => $this->persona->telefono ?? null,
                'correo' => $this->persona->correo ?? null,
                'fecha_nacimiento' => $this->persona->fecha_nacimiento ? $this->persona->fecha_nacimiento->format('Y-m-d') : null,
            ],
            'tipo_trabajador' => [
                'id' => $this->tipoTrabajador->id ?? null,
                'nombre' => $this->tipoTrabajador->nombre ?? null,
            ],
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}

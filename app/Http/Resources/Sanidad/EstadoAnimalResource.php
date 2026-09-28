<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Animal\AnimalResource;

class EstadoAnimalResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2 (snake_case).
     *
     * @param Request $request
     * @return array<string, mixed>|null
     */
    public function toArray(Request $request): ?array
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id'              => $this->id,
            'fecha_ini'       => $this->fecha_ini ? $this->fecha_ini->toDateString() : null,
            'fecha_fin'       => $this->fecha_fin ? $this->fecha_fin->toDateString() : null,
            'estado_salud_id' => $this->when(!$this->relationLoaded('estadoSalud'), $this->estado_salud_id),
            'animal_id'       => $this->when(!$this->relationLoaded('animal'), $this->animal_id),
            'created_at'      => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'      => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            // Relaciones
            'animal'       => new AnimalResource($this->whenLoaded('animal')),
            'estado_salud' => new EstadoSaludResource($this->whenLoaded('estadoSalud')),
        ];
    }
}

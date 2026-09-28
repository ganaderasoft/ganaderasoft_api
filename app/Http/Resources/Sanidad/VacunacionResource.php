<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacunacionResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'id'          => $this->id,
            'animal_id'   => $this->animal_id,
            'vacuna_id'   => $this->vacuna_id,
            'persona_id'  => $this->persona_id,
            'fecha'       => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'dosis'       => $this->dosis !== null ? (float) $this->dosis : null,
            'costo'       => (float) $this->costo,
            'lote'        => $this->lote,
            'observacion' => $this->observacion,
            'created_at'  => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'  => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            // Relaciones cargadas bajo demanda
            'animal'      => $this->whenLoaded('animal'),
            'vacuna'      => $this->whenLoaded('vacuna'),
            'aplicador'   => $this->whenLoaded('aplicador'),
        ];
    }
}

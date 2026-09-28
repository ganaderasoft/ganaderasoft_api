<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PalpacionResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2 (snake_case).
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
            'id'                => $this->id,
            'personal_finca_id' => $this->personal_finca_id,
            'tipo'              => $this->tipo,
            'fecha'             => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'animal_etapa_id'   => $this->animal_etapa_id,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'etapa_animal'      => $this->whenLoaded('etapaAnimal'),
            'tecnico'           => $this->whenLoaded('tecnico'),
        ];
    }
}

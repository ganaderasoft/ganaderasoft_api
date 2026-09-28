<?php

namespace App\Http\Resources\Reproduccion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReproduccionAnimalResource extends JsonResource
{
    /**
     * Transforma el recurso a un array V2 estándar (snake_case).
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
            'id'                 => $this->id,
            'animal_etapa_id'    => $this->animal_etapa_id,
            'fecha_reproduccion' => $this->fecha_reproduccion ? $this->fecha_reproduccion->format('Y-m-d') : null,
            'tipo_reproduccion'  => $this->tipo_reproduccion,
            'observacion'        => $this->observacion,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,

            // Relaciones
            'etapa_animal'       => $this->whenLoaded('etapaAnimal'),
            'animal'             => $this->whenLoaded('animal'),
            'etapa'              => $this->whenLoaded('etapa'),
        ];
    }
}

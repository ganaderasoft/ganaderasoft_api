<?php

namespace App\Http\Resources\Reproduccion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioAnimalResource extends JsonResource
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
            'id'                => $this->id,
            'animal_id'         => $this->animal_id,
            'semen_toro_id'     => $this->semen_toro_id,
            'personal_finca_id' => $this->personal_finca_id,
            'registro_celo_id'  => $this->registro_celo_id,
            'tipo'              => $this->tipo,
            'fecha'             => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'observacion'       => $this->observacion,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,

            // Relaciones
            'animal'            => $this->whenLoaded('animal'),
            'semen'             => $this->whenLoaded('semen'),
            'tecnico'           => $this->whenLoaded('tecnico'),
            'registro_celo'     => $this->whenLoaded('registroCelo'),
        ];
    }
}

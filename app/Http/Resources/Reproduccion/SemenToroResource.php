<?php

namespace App\Http\Resources\Reproduccion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SemenToroResource extends JsonResource
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
            'id'         => $this->id,
            'animal_id'  => $this->animal_id,
            'estado'            => (bool) $this->estado,
            'fecha'             => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'cantidad_pajuelas' => $this->cantidad_pajuelas !== null ? (int) $this->cantidad_pajuelas : null,
            'created_at'        => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'toro'       => $this->whenLoaded('toro'),
            'servicios'  => $this->whenLoaded('servicios'),
        ];
    }
}

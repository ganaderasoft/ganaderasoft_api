<?php

namespace App\Http\Resources\Produccion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LecheResource extends JsonResource
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
            'id'           => $this->id,
            'lactancia_id' => $this->lactancia_id,
            'fecha_pesaje' => $this->fecha_pesaje ? $this->fecha_pesaje->format('Y-m-d') : null,
            'pesaje_total' => $this->pesaje_total,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            // Relaciones
            'lactancia'    => $this->whenLoaded('lactancia'),
            'animal'       => $this->whenLoaded('animal'),
        ];
    }
}

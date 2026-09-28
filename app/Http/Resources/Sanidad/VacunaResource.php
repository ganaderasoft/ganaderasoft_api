<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacunaResource extends JsonResource
{
    /**
     * Transforma el recurso a un array estándar V2 (snake_case).
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
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'activa'      => (bool) $this->activa,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            
            // Relaciones condicionales si están cargadas
            'casas_comerciales' => $this->whenLoaded('casasComerciales'),
            'dosis'             => $this->whenLoaded('dosis'),
        ];
    }
}

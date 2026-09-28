<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CasaComercialResource extends JsonResource
{
    /**
     * Transforma el recurso a un array.
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
            'id'              => $this->id,
            'laboratorio'     => $this->laboratorio,
            'marca_comercial' => $this->marca_comercial,
            'activa'          => (bool) $this->activa,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            
            // Relaciones condicionales si están cargadas
            'vacunas'         => $this->whenLoaded('vacunas'),
        ];
    }
}

<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvarioResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo estructurado V2.
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
            'palpacion_id' => $this->palpacion_id,
            'tamano'       => $this->tamano,
            'lado'         => $this->lado,
            'medida'       => $this->medida,
            'foliculos'    => $this->whenLoaded('foliculos', fn() => FoliculoResource::collection($this->foliculos)),
        ];
    }
}

<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComposicionRazaResource extends JsonResource
{
    /**
     * Resource unificado para ComposicionRaza (index, show, store, update).
     * Solo campos escalares del modelo — sin relaciones.
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'id'                      => $this->id,
            'nombre'                  => $this->nombre,
            'siglas'                  => $this->siglas,
            'pelaje'                  => $this->pelaje,
            'proposito'               => $this->proposito,
            'tipo_raza'               => $this->tipo_raza,
            'origen'                  => $this->origen,
            'caracteristica_especial' => $this->caracteristica_especial,
            'proporcion_raza'         => $this->proporcion_raza,
            'finca_id'                => $this->finca_id,
            'tipo_animal_id'          => $this->tipo_animal_id,
            'created_at'              => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'              => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'finca'                   => $this->whenLoaded('finca'),
            'tipo_animal'             => $this->whenLoaded('tipoAnimal'),
        ];
    }
}

<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CambiosAnimalResource extends JsonResource
{
    /**
     * Resource unificado para CambiosAnimal (index, show, store, update).
     * Solo campos escalares del modelo — sin relaciones.
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'id'              => $this->id,
            'animal_etapa_id' => $this->animal_etapa_id,
            'fecha_cambio'    => $this->fecha_cambio ? $this->fecha_cambio->toDateString() : null,
            'etapa_cambio'    => $this->etapa_cambio,
            'peso'            => (float) $this->peso,
            'altura'          => (float) $this->altura,
            'comentario'      => $this->comentario,
            'created_at'      => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'      => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}

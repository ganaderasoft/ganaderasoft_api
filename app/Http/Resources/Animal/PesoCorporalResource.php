<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesoCorporalResource extends JsonResource
{
    /**
     * Resource unificado para PesoCorporal (index, show, store, update).
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
            'fecha_peso'      => $this->fecha_peso ? $this->fecha_peso->toDateString() : null,
            'peso'            => (float) $this->peso,
            'comentario'      => $this->comentario,
            'created_at'      => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'      => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'animal'          => $this->whenLoaded('etapaAnimal', function () {
                if ($this->etapaAnimal && $this->etapaAnimal->relationLoaded('animal')) {
                    $animal = $this->etapaAnimal->animal;
                    return $animal ? [
                        'id' => $animal->id,
                        'nombre' => $animal->nombre,
                        'codigo_animal' => $animal->codigo_animal,
                    ] : null;
                }
                return null;
            }),
        ];
    }
}

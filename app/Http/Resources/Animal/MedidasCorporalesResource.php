<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedidasCorporalesResource extends JsonResource
{
    /**
     * Resource unificado para MedidasCorporales (index, show, store, update).
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
            'animal_id'       => $this->etapaAnimal ? $this->etapaAnimal->animal_id : null,
            'altura_hc'       => $this->altura_hc !== null ? (float) $this->altura_hc : null,
            'altura_hg'       => $this->altura_hg !== null ? (float) $this->altura_hg : null,
            'perimetro_pt'    => $this->perimetro_pt !== null ? (float) $this->perimetro_pt : null,
            'perimetro_pca'   => $this->perimetro_pca !== null ? (float) $this->perimetro_pca : null,
            'longitud_lc'     => $this->longitud_lc !== null ? (float) $this->longitud_lc : null,
            'longitud_lg'     => $this->longitud_lg !== null ? (float) $this->longitud_lg : null,
            'anchura_ag'      => $this->anchura_ag !== null ? (float) $this->anchura_ag : null,
            'created_at'      => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'      => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'animal'          => $this->whenLoaded('etapaAnimal', function () {
                if ($this->etapaAnimal && $this->etapaAnimal->relationLoaded('animal')) {
                    $animal = $this->etapaAnimal->animal;
                    return $animal ? [
                        'id'            => $animal->id,
                        'nombre'        => $animal->nombre,
                        'codigo_animal' => $animal->codigo_animal,
                        'sexo'          => $animal->sexo,
                    ] : null;
                }
                return null;
            }),
            'etapa_animal'    => $this->whenLoaded('etapaAnimal', function () {
                return $this->etapaAnimal ? [
                    'id'        => $this->etapaAnimal->id,
                    'animal_id' => $this->etapaAnimal->animal_id,
                    'etapa_id'  => $this->etapaAnimal->etapa_id,
                    'etapa'     => $this->etapaAnimal->relationLoaded('etapa') && $this->etapaAnimal->etapa ? [
                        'id'     => $this->etapaAnimal->etapa->id,
                        'nombre' => $this->etapaAnimal->etapa->nombre,
                    ] : null,
                ] : null;
            }),
        ];
    }
}

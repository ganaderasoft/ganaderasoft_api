<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtapaResource extends JsonResource
{
    /**
     * Resource unificado para Etapa (index, show, store, update).
     * Solo campos escalares del modelo — sin relaciones.
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'id'             => $this->id,
            'nombre'         => $this->nombre,
            'edad_ini'       => $this->edad_ini,
            'edad_fin'       => $this->edad_fin,
            'tipo_animal_id' => $this->tipo_animal_id,
            'sexo'           => $this->sexo,
        ];
    }
}

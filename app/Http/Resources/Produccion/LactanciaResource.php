<?php

namespace App\Http\Resources\Produccion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LactanciaResource extends JsonResource
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
            'id'              => $this->id,
            'animal_etapa_id' => $this->animal_etapa_id,
            'fecha_inicio'    => $this->fecha_inicio ? $this->fecha_inicio->format('Y-m-d') : null,
            'fecha_fin'       => $this->fecha_fin ? $this->fecha_fin->format('Y-m-d') : null,
            'secado'          => $this->secado ? $this->secado->format('Y-m-d') : null,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,

            // Relaciones
            'animal'          => $this->whenLoaded('animal'),
            'etapa'           => $this->whenLoaded('etapa'),
            'etapa_animal'    => $this->whenLoaded('etapaAnimal'),
            'leche_records'   => $this->whenLoaded('lecheRecords'),
        ];
    }
}

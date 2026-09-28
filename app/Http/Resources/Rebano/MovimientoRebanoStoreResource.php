<?php

namespace App\Http\Resources\Rebano;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoRebanoStoreResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2 (snake_case) para la creación.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        $animalesCollection = [];
        if ($this->relationLoaded('animales')) {
            foreach ($this->animales as $mra) {
                $animalesCollection[] = [
                    'id'        => $mra->id,
                    'estado'    => $mra->estado,
                    'animal_id' => $mra->animal_id,
                ];
            }
        }

        return [
            'id'             => $this->id,
            'rebano_destino' => $this->rebano_destino,
            'comentario'     => $this->comentario,
            'created_at'     => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'     => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            // FK escalar siempre presente en store (no se cargan relaciones en store)
            'finca_id'          => $this->finca_id,
            'rebano_id'         => $this->rebano_id,
            'finca_destino_id'  => $this->finca_destino_id,
            'rebano_destino_id' => $this->rebano_destino_id,

            'animales' => $animalesCollection,
        ];
    }
}

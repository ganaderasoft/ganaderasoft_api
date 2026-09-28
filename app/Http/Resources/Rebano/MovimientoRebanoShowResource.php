<?php

namespace App\Http\Resources\Rebano;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Finca\FincaResource;
use App\Http\Resources\Rebano\RebanoResource;
use App\Http\Resources\Animal\AnimalResource;

class MovimientoRebanoShowResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2 (snake_case) para el detalle.
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
                if ($mra->relationLoaded('animal') && $mra->animal) {
                    $animalesCollection[] = [
                        'id'     => $mra->id,
                        'estado' => $mra->estado,
                        'animal' => new AnimalResource($mra->animal),
                    ];
                } else {
                    $animalesCollection[] = [
                        'id'        => $mra->id,
                        'estado'    => $mra->estado,
                        'animal_id' => $mra->animal_id,
                    ];
                }
            }
        }

        return [
            'id'             => $this->id,
            'rebano_destino' => $this->rebano_destino,
            'comentario'     => $this->comentario,
            'created_at'     => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'     => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            // FK escalar solo si la relación no está cargada
            'finca_id'          => $this->when(!$this->relationLoaded('fincaOrigen'), $this->finca_id),
            'rebano_id'         => $this->when(!$this->relationLoaded('rebanoOrigen'), $this->rebano_id),
            'finca_destino_id'  => $this->when(!$this->relationLoaded('fincaDestino'), $this->finca_destino_id),
            'rebano_destino_id' => $this->when(!$this->relationLoaded('rebanoDestino'), $this->rebano_destino_id),

            // Relaciones cuando están cargadas
            'finca_origen'       => $this->whenLoaded('fincaOrigen', fn() => new FincaResource($this->fincaOrigen)),
            'rebano_origen'      => $this->whenLoaded('rebanoOrigen', fn() => new RebanoResource($this->rebanoOrigen)),
            'finca_destino'      => $this->whenLoaded('fincaDestino', fn() => new FincaResource($this->fincaDestino)),
            'rebano_destino_rel' => $this->whenLoaded('rebanoDestino', fn() => new RebanoResource($this->rebanoDestino)),
            'animales'           => $animalesCollection,
        ];
    }
}

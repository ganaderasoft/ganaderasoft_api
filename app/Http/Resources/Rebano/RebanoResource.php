<?php

namespace App\Http\Resources\Rebano;

use App\Http\Resources\Animal\AnimalResource;
use App\Http\Resources\Finca\FincaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RebanoResource extends JsonResource
{
    /**
     * Transforma el recurso de Rebaño a un array limpio estándar V2 (snake_case).
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        $totalAnimales = $this->animales_count ?? ($this->relationLoaded('animales') ? $this->animales->count() : 0);

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'archivado' => (bool) $this->archivado,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            'finca_id' => $this->finca_id,
            'finca' => new FincaResource($this->whenLoaded('finca')),
            'total_animales' => (int) $totalAnimales,
            'animales' => AnimalResource::collection($this->whenLoaded('animales')),
        ];
    }
}

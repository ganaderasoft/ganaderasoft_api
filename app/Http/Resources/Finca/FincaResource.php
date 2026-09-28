<?php

namespace App\Http\Resources\Finca;

use App\Http\Resources\Persona\PropietarioResource;
use App\Http\Resources\Finca\TerrenoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FincaResource extends JsonResource
{
    /**
     * Transforma el recurso de Finca a un array limpio estándar V2 (snake_case).
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'explotacion_tipo' => $this->explotacion_tipo,
            'archivado' => (bool) $this->archivado,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            // Omitir propietario_id si la relación propietario está cargada
            'propietario_id' => $this->when(!$this->relationLoaded('propietario'), $this->propietario_id),
            
            'propietario' => new PropietarioResource($this->whenLoaded('propietario')),
            'terreno' => new TerrenoResource($this->whenLoaded('terreno')),
        ];
    }
}

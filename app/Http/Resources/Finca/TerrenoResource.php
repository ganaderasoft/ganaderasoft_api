<?php

namespace App\Http\Resources\Finca;

use App\Http\Resources\Finca\FincaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerrenoResource extends JsonResource
{
    /**
     * Transforma el recurso de Terreno a un array limpio estándar V2 (snake_case).
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
            'finca_id' => $this->finca_id,
            'superficie' => $this->superficie !== null ? (float) $this->superficie : null,
            'relieve' => $this->relieve,
            'suelo_textura' => $this->suelo_textura,
            'ph_suelo' => $this->ph_suelo,
            'precipitacion' => $this->precipitacion !== null ? (float) $this->precipitacion : null,
            'velocidad_viento' => $this->velocidad_viento !== null ? (float) $this->velocidad_viento : null,
            'temp_anual' => $this->temp_anual,
            'temp_min' => $this->temp_min,
            'temp_max' => $this->temp_max,
            'radiacion' => $this->radiacion !== null ? (float) $this->radiacion : null,
            'fuente_agua' => $this->fuente_agua,
            'caudal_disponible' => $this->caudal_disponible !== null ? (int) $this->caudal_disponible : null,
            'riego_metodo' => $this->riego_metodo,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'finca' => new FincaResource($this->whenLoaded('finca')),
        ];
    }
}

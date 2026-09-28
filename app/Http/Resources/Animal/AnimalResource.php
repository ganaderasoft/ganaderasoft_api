<?php

namespace App\Http\Resources\Animal;

use App\Http\Resources\Rebano\RebanoResource;
use App\Http\Resources\Sanidad\EstadoAnimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2 (snake_case).
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
            'rebano_id' => $this->when(!$this->relationLoaded('rebano'), $this->rebano_id),
            'nombre' => $this->nombre,
            'codigo_animal' => $this->codigo_animal,
            'sexo' => $this->sexo,
            'fecha_nacimiento' => $this->fecha_nacimiento ? $this->fecha_nacimiento->format('Y-m-d') : null,
            'procedencia' => $this->procedencia,
            'composicion_raza_id' => $this->when(!$this->relationLoaded('composicionRaza'), $this->composicion_raza_id),
            'archivado' => (bool) $this->archivado,
            'edad_dias' => $this->edad_dias,
            'edad_meses' => $this->edad_meses,
            'edad_anos' => $this->edad_anos,
            'edad_formateada' => $this->edad_formateada,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            // Relaciones cargadas formateadas de manera limpia
            'rebano' => new RebanoResource($this->whenLoaded('rebano')),
            'composicion_raza' => $this->whenLoaded('composicionRaza'),
            'estado_actual' => $this->whenLoaded('estadoActual', fn() => new EstadoAnimalResource($this->estadoActual)),
            'etapa_actual' => $this->whenLoaded('etapaActual', fn() => new EtapaAnimalResource($this->etapaActual)),
            'estados' => $this->whenLoaded('estados', fn() => EstadoAnimalResource::collection($this->estados)),
            'etapa_animales' => $this->whenLoaded('etapaAnimales', fn() => EtapaAnimalResource::collection($this->etapaAnimales)),
        ];
    }
}

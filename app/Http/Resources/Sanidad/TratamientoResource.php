<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TratamientoResource extends JsonResource
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
            return [];
        }

        return [
            'id'             => $this->id,
            'diagnostico_id' => $this->diagnostico_id,
            'plan'           => $this->plan,
            'fecha_ini'      => $this->fecha_ini ? $this->fecha_ini->format('Y-m-d') : null,
            'fecha_fin'      => $this->fecha_fin ? $this->fecha_fin->format('Y-m-d') : null,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'diagnostico'    => $this->whenLoaded('diagnostico', fn() => new DiagnosticoResource($this->diagnostico)),
        ];
    }
}

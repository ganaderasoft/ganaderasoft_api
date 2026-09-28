<?php

namespace App\Http\Resources\Personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoTrabajadorResource extends JsonResource
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
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

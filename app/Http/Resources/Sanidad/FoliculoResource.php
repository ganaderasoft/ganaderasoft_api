<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoliculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'siglas' => $this->siglas,
        ];
    }
}

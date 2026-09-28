<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiaPalpacionResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo estructurado V2.
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
            'id'   => $this->id,
            'dias' => $this->dias,
        ];
    }
}

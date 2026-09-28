<?php

namespace App\Http\Resources\Persona;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Finca\FincaResource;

class PropietarioResource extends JsonResource
{
    /**
     * Transforma el recurso de Propietario en un array.
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
            'persona' => new PersonaResource($this->whenLoaded('persona')),
            'fincas' => FincaResource::collection($this->whenLoaded('fincas')),
        ];
    }
}

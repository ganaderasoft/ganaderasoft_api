<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFincaResource extends JsonResource
{
    /**
     * Transforma el recurso a un arreglo con los datos de acceso a la finca.
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
            'user_id'      => $this->pivot?->user_id,
            'finca_id'     => $this->id,
            'access_level' => $this->pivot?->access_level,
            'is_default'   => (bool) $this->pivot?->is_default,
            'status'       => $this->pivot?->status,
        ];
    }
}

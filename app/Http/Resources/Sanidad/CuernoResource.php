<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuernoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'id'                    => $this->id,
            'palpacion_id'          => $this->palpacion_id,
            'tamano'                => $this->tamano,
            'medicion'              => $this->medicion,
            'lado'                  => $this->lado,
            'iu_plano'              => $this->iu_plano,
            'estado_sano'           => (bool) $this->estado_sano,
            'patologia_nombre'      => $this->patologia_nombre,
            'patologia_descripcion' => $this->patologia_descripcion,
        ];
    }
}

<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class EstadisticasFincasResource extends JsonResource
{
    /**
     * Transforma el recurso a un arreglo para la respuesta JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'resumen'           => $this->resource['resumen'] ?? [],
            'animales_por_sexo' => $this->resource['animales_por_sexo'] ?? [],
            'personal_por_tipo' => $this->resource['personal_por_tipo'] ?? [],
            'fincas'            => $this->resource['fincas'] ?? [],
            'rebanos'           => $this->resource['rebanos'] ?? [],
        ];
    }
}

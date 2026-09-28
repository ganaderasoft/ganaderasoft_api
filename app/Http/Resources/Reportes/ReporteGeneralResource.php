<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class ReporteGeneralResource extends JsonResource
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
            'finca'             => $this->resource['finca'] ?? null,
            'kpis'              => $this->resource['kpis'] ?? [],
            'items'             => $this->resource['items'] ?? [],
            'resumen'           => $this->resource['resumen'] ?? [],
            'animales'          => $this->resource['animales'] ?? [],
            'total_animales'    => $this->resource['total_animales'] ?? 0,
            'fincas'            => $this->resource['fincas'] ?? [],
            'rebanos'           => $this->resource['rebanos'] ?? [],
            'filtros_aplicados' => $this->resource['filtros_aplicados'] ?? [],
        ];
    }
}

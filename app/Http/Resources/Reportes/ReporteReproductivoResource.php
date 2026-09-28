<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class ReporteReproductivoResource extends JsonResource
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
            'resumen'           => $this->resource['resumen'] ?? [],
            'animales'          => $this->resource['animales'] ?? [],
            'kpis'              => $this->resource['kpis'] ?? ($this->resource['resumen'] ?? []),
            'items'             => $this->resource['items'] ?? [],
            'filtros_aplicados' => $this->resource['filtros_aplicados'] ?? [],
        ];
    }
}

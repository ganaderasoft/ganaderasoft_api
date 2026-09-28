<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class ReporteHistorialLactanciaResource extends JsonResource
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
            'finca'                  => $this->resource['finca'] ?? null,
            'total_animales'         => $this->resource['total_animales'] ?? 0,
            'produccion_total_finca' => $this->resource['produccion_total_finca'] ?? 0.0,
            'animales'               => $this->resource['animales'] ?? [],
            'kpis'                   => $this->resource['kpis'] ?? [],
            'items'                  => $this->resource['items'] ?? [],
            'filtros_aplicados'      => $this->resource['filtros_aplicados'] ?? [],
        ];
    }
}

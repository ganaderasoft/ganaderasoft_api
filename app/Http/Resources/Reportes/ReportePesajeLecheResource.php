<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportePesajeLecheResource extends JsonResource
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
            'resumen'                => $this->resource['resumen'] ?? [],
            'pesajes'                => $this->resource['pesajes'] ?? [],
            'kpis'                   => $this->resource['kpis'] ?? ($this->resource['resumen'] ?? []),
            'items'                  => $this->resource['items'] ?? [],
            'rendimiento_individual' => $this->resource['rendimiento_individual'] ?? [],
            'filtros_aplicados'      => $this->resource['filtros_aplicados'] ?? [],
        ];
    }
}

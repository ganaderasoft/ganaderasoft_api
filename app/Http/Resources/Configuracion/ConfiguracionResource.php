<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

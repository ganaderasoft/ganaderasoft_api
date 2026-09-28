<?php

namespace App\Http\Resources\Inventario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventarioVacunoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finca_id' => $this->finca_id,
            'num_becerra' => $this->num_becerra,
            'num_mauta' => $this->num_mauta,
            'num_novilla' => $this->num_novilla,
            'num_vaca' => $this->num_vaca,
            'num_becerro' => $this->num_becerro,
            'num_maute' => $this->num_maute,
            'num_torete' => $this->num_torete,
            'num_toro' => $this->num_toro,
            'fecha_inventario' => $this->fecha_inventario ? $this->fecha_inventario->format('Y-m-d') : null,
            'total' => $this->total,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}

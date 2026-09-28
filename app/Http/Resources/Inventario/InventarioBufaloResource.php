<?php

namespace App\Http\Resources\Inventario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Finca\FincaResource; // assuming it exists

class InventarioBufaloResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? $this->id_InvBufalo,
            'finca_id' => $this->finca_id ?? $this->id_Finca,
            'num_becerro' => $this->num_becerro ?? $this->Num_Becerro ?? 0,
            'num_anojo' => $this->num_anojo ?? $this->Num_Anojo ?? 0,
            'num_bubilla' => $this->num_bubilla ?? $this->Num_Bubilla ?? 0,
            'num_bufalo' => $this->num_bufalo ?? $this->Num_Bufalo ?? 0,
            'total_bufalos' => $this->total_buffalo,
            'fecha_inventario' => $this->fecha_inventario ?? $this->Fecha_Inventario,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // optional relationships
            'finca' => $this->whenLoaded('finca', function () {
                // If FincaResource exists we can use it, else return generic array
                return class_exists(\App\Http\Resources\Finca\FincaResource::class) 
                    ? new \App\Http\Resources\Finca\FincaResource($this->finca)
                    : $this->finca;
            }),
        ];
    }
}

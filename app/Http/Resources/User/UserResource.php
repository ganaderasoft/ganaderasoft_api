<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Persona\PersonaResource;
use App\Http\Resources\Persona\PropietarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'foto' => $this->foto ? url('storage/' . $this->foto) : null,
            'avatar' => $this->foto ? url('storage/' . $this->foto) : null,
            'profile_photo_url' => $this->foto ? url('storage/' . $this->foto) : null,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'status' => $this->status,
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->map(function ($role) {
                return [
                    'code' => $role->code,
                    'name' => $role->name,
                    'permissions' => $role->relationLoaded('permissions') ? $role->permissions->pluck('code')->values()->all() : [],
                ];
            })),

            // Datos de la Persona física vinculada (solo si está cargada y existe)
            'persona' => $this->when(
                $this->relationLoaded('personas') && $this->personas->isNotEmpty(),
                fn() => new PersonaResource($this->personas->first())
            ),
            
            // Relación de propietario (solo si está cargada y la persona es propietario)
            'propietario' => $this->when(
                $this->relationLoaded('personas') && 
                $this->personas->first()?->relationLoaded('propietario') && 
                $this->personas->first()?->propietario !== null,
                fn() => new PropietarioResource($this->personas->first()->propietario)
            ),
            
            // El propietario sigue siendo requerido por los middlewares legacy (v1)
        ];
    }
}

<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Animal\AnimalResource;

class ArbolGenResource extends JsonResource
{
    /**
     * Transforma el árbol genealógico en un array estándar V2.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        return [
            'animal' => new AnimalResource($this->resource['animal']),
            'padre' => $this->resource['padre'] ? [
                ...(new AnimalResource($this->resource['padre']['animal']))->toArray($request),
                'abuelo_paterno' => $this->resource['padre']['abuelo_paterno'] ? new AnimalResource($this->resource['padre']['abuelo_paterno']) : null,
                'abuela_paterna' => $this->resource['padre']['abuela_paterna'] ? new AnimalResource($this->resource['padre']['abuela_paterna']) : null,
            ] : null,
            'madre' => $this->resource['madre'] ? [
                ...(new AnimalResource($this->resource['madre']['animal']))->toArray($request),
                'abuelo_materno' => $this->resource['madre']['abuelo_materno'] ? new AnimalResource($this->resource['madre']['abuelo_materno']) : null,
                'abuela_materna' => $this->resource['madre']['abuela_materna'] ? new AnimalResource($this->resource['madre']['abuela_materna']) : null,
            ] : null,
            'hijos' => AnimalResource::collection($this->resource['hijos']),
            'relaciones' => [
                'id_arbol_padre' => $this->resource['relaciones']['id_arbol_padre'],
                'id_arbol_madre' => $this->resource['relaciones']['id_arbol_madre'],
            ],
        ];
    }
}

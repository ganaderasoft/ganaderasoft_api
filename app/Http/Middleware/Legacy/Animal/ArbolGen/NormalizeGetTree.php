<?php

namespace App\Http\Middleware\Legacy\Animal\ArbolGen;

use Closure;
use Illuminate\Http\Request;

class NormalizeGetTree
{
    /**
     * Intercepta la respuesta para convertir las llaves V2 (id, nombre, sexo)
     * a llaves V1 (id_Animal, Nombre, Sexo) en el árbol genealógico.
     */
    public function handle(Request $request, Closure $next)
    {
        $isV2 = $request->header('X-API-VERSION') === '2';

        if (!$isV2) {
            $cleanedInput = $this->transformToCleanFormat($request->all());
            $request->replace($cleanedInput);
        }

        $response = $next($request);

        if (!$isV2 && $response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                $data['data'] = $this->transformTreeToLegacyFormat($data['data']);
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        return $input;
    }

    private function transformTreeToLegacyFormat(array $node): array
    {
        $mapped = [];
        
        if (isset($node['animal'])) {
            $mapped['animal'] = $this->transformAnimalToLegacyFormat($node['animal']);
        }
        
        if (isset($node['padre'])) {
            $mapped['padre'] = $node['padre'] ? [
                ...$this->transformAnimalToLegacyFormat($node['padre']),
                'abuelo_paterno' => isset($node['padre']['abuelo_paterno']) ? $this->transformAnimalToLegacyFormat($node['padre']['abuelo_paterno']) : null,
                'abuela_paterna' => isset($node['padre']['abuela_paterna']) ? $this->transformAnimalToLegacyFormat($node['padre']['abuela_paterna']) : null,
            ] : null;
        }

        if (isset($node['madre'])) {
            $mapped['madre'] = $node['madre'] ? [
                ...$this->transformAnimalToLegacyFormat($node['madre']),
                'abuelo_materno' => isset($node['madre']['abuelo_materno']) ? $this->transformAnimalToLegacyFormat($node['madre']['abuelo_materno']) : null,
                'abuela_materna' => isset($node['madre']['abuela_materna']) ? $this->transformAnimalToLegacyFormat($node['madre']['abuela_materna']) : null,
            ] : null;
        }

        if (isset($node['hijos'])) {
            $mapped['hijos'] = array_map([$this, 'transformAnimalToLegacyFormat'], $node['hijos']);
        }

        if (isset($node['relaciones'])) {
            $mapped['relaciones'] = $node['relaciones'];
        }

        return $mapped;
    }

    private function transformAnimalToLegacyFormat(?array $animal): ?array
    {
        if (!$animal) return null;

        $legacy = [];
        
        if (array_key_exists('id', $animal)) {
            $legacy['id_Animal'] = $animal['id'];
        }
        
        if (array_key_exists('nombre', $animal)) {
            $legacy['Nombre'] = $animal['nombre'];
        }
        
        if (array_key_exists('codigo_animal', $animal)) {
            $legacy['codigo_animal'] = $animal['codigo_animal'];
        }
        
        if (array_key_exists('sexo', $animal)) {
            $legacy['Sexo'] = $animal['sexo'];
        }
        
        if (array_key_exists('fecha_nacimiento', $animal)) {
            $legacy['fecha_nacimiento'] = $animal['fecha_nacimiento'] ? $animal['fecha_nacimiento'] . 'T00:00:00+00:00' : null;
        }

        return $legacy;
    }
}

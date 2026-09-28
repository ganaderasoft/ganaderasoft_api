<?php

namespace App\Http\Middleware\Legacy\Animal\ArbolGen;

use Closure;
use Illuminate\Http\Request;

class NormalizeGetAvailableParents
{
    /**
     * Intercepta la respuesta para normalizar la lista de candidatos disponibles.
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

            if (isset($data['data']) && is_array($data['data'])) {
                $data['data'] = array_map([$this, 'transformAnimalToLegacyFormat'], $data['data']);
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        return $input;
    }

    private function transformAnimalToLegacyFormat(array $animal): array
    {
        $mapped = [];
        if (array_key_exists('id', $animal)) {
            $mapped['id_Animal'] = $animal['id'];
        }
        if (array_key_exists('nombre', $animal)) {
            $mapped['Nombre'] = $animal['nombre'];
        }
        if (array_key_exists('codigo_animal', $animal)) {
            $mapped['codigo_animal'] = $animal['codigo_animal'];
        }
        if (array_key_exists('sexo', $animal)) {
            $mapped['Sexo'] = $animal['sexo'];
        }
        return $mapped;
    }
}

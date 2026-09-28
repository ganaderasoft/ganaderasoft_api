<?php

namespace App\Http\Middleware\Legacy\Animal\ArbolGen;

use Closure;
use Illuminate\Http\Request;

class NormalizeSetParent
{
    /**
     * Intercepta la respuesta para convertir las llaves de la relación guardada.
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
                $data['data'] = $this->transformRelationToLegacyFormat($data['data']);
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }

    private function transformToCleanFormat(array $input): array
    {
        $normalized = $input;
        if (isset($input['id_padre'])) {
            $normalized['padre_id'] = $input['id_padre'];
            unset($normalized['id_padre']);
        }
        return $normalized;
    }

    private function transformRelationToLegacyFormat(array $rel): array
    {
        $mapped = [];

        if (array_key_exists('id', $rel)) {
            $mapped['id_arbol'] = $rel['id'];
        }
        if (array_key_exists('hijo_id', $rel)) {
            $mapped['id_hijo'] = $rel['hijo_id'];
        }
        if (array_key_exists('padre_id', $rel)) {
            $mapped['id_padre'] = $rel['padre_id'];
        }
        if (array_key_exists('tipo', $rel)) {
            $mapped['tipo'] = $rel['tipo'];
        }
        if (array_key_exists('created_at', $rel)) {
            $mapped['created_at'] = $rel['created_at'];
        }
        if (array_key_exists('updated_at', $rel)) {
            $mapped['updated_at'] = $rel['updated_at'];
        }

        return $mapped;
    }
}

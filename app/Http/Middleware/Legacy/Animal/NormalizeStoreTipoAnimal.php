<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreTipoAnimal
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $cleanedInput = $this->transformToCleanFormat($request->all());
        $request->replace($cleanedInput);

        $response = $next($request);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (isset($data['data'])) {
                $data['data'] = $this->transformToLegacyFormat($data['data']);
                $response->setData($data);
            }
        } elseif ($response->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
            $data = $response->getData(true);
            if (isset($data['errors']['nombre'])) {
                $data['errors']['tipo_animal_nombre'] = $data['errors']['nombre'];
                unset($data['errors']['nombre']);
                $response->setData($data);
            }
        }

        return $response;
    }

    /**
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        return [
            'nombre' => $input['tipo_animal_nombre'] ?? ($input['nombre'] ?? null),
        ];
    }

    /**
     * Transforma un tipo de animal V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        return [
            'tipo_animal_id'     => $item['id'],
            'tipo_animal_nombre' => $item['nombre'],
            'created_at'         => $item['created_at'] ?? null,
            'updated_at'         => $item['updated_at'] ?? null,
        ];
    }
}

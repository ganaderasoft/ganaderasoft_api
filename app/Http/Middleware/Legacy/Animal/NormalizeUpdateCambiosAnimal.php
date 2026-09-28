<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateCambiosAnimal
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
        }

        return $response;
    }

    /**
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        $payload = [];
        if (array_key_exists('Fecha_Cambio', $input)) $payload['fecha_cambio'] = $input['Fecha_Cambio'];
        if (array_key_exists('Etapa_Cambio', $input)) $payload['etapa_cambio'] = $input['Etapa_Cambio'];
        if (array_key_exists('Peso', $input)) $payload['peso'] = $input['Peso'];
        if (array_key_exists('Altura', $input)) $payload['altura'] = $input['Altura'];
        if (array_key_exists('Comentario', $input)) $payload['comentario'] = $input['Comentario'];

        return $payload;
    }

    /**
     * Transforma un registro de CambiosAnimal V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $etapaAnimal = DB::table('animal_etapa')->where('id', $item['animal_etapa_id'])->first();

        return [
            'id_Cambio'          => $item['id'],
            'Fecha_Cambio'       => $item['fecha_cambio'] ? $item['fecha_cambio'] . 'T00:00:00.000000Z' : null,
            'Etapa_Cambio'       => $item['etapa_cambio'],
            'Peso'               => $item['peso'],
            'Altura'             => $item['altura'],
            'Comentario'         => $item['comentario'],
            'created_at'         => $item['created_at'],
            'updated_at'         => $item['updated_at'],
            'cambios_etapa_anid' => $etapaAnimal ? $etapaAnimal->animal_id : null,
            'cambios_etapa_etid' => $etapaAnimal ? $etapaAnimal->etapa_id : null,
        ];
    }
}

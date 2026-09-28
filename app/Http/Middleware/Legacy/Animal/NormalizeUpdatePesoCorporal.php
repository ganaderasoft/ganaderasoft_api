<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdatePesoCorporal
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
            }
            if (isset($data['clasificacion_etaria'])) {
                $clasif = $data['clasificacion_etaria'];
                if (isset($clasif['target_etapa_id'])) {
                    $clasif['target_etapa_id'] = $clasif['target_etapa_id'];
                }
                $data['clasificacion_etaria'] = $clasif;
            }
            $response->setData($data);
        }

        return $response;
    }

    /**
     * Transforma inputs V1 a formato V2 (limpio).
     */
    private function transformToCleanFormat(array $input): array
    {
        $payload = [];
        if (array_key_exists('Fecha_Peso', $input)) $payload['fecha_peso'] = $input['Fecha_Peso'];
        if (array_key_exists('Peso', $input)) $payload['peso'] = $input['Peso'];
        if (array_key_exists('Comentario', $input)) $payload['comentario'] = $input['Comentario'];

        return $payload;
    }

    /**
     * Transforma un registro de peso corporal V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $etapaAnimal = DB::table('animal_etapa')->where('id', $item['animal_etapa_id'])->first();

        return [
            'id_Peso'         => $item['id'],
            'peso_id'         => $item['id'],
            'Fecha_Peso'      => $item['fecha_peso'] ? $item['fecha_peso'] . 'T00:00:00.000000Z' : null,
            'Peso'            => $item['peso'],
            'Comentario'      => $item['comentario'],
            'peso_etapa_anid' => $etapaAnimal ? $etapaAnimal->animal_id : null,
            'peso_etapa_etid' => $etapaAnimal ? $etapaAnimal->etapa_id : null,
            'created_at'      => $item['created_at'] ?? null,
            'updated_at'      => $item['updated_at'] ?? null,
        ];
    }
}

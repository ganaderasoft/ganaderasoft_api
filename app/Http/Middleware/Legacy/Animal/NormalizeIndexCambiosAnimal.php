<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexCambiosAnimal
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
        $cleanedInput['nopaginate'] = true;
        $request->replace($cleanedInput);

        $response = $next($request);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (isset($data['data'])) {
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    // 1. Formateamos la paginación 
                    $data['pagination'] = $this->transformPaginationLegacyFormat($data['data']);
                    
                    // 2. Transformamos los registros
                    $data['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']['data']);
                } else {
                    $data['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']);
                }
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
        return $input;
    }

    /**
     * Extrae y formatea los datos de paginación al formato Legacy.
     */
    private function transformPaginationLegacyFormat(array $paginatorData): array
    {
        return [
            'current_page' => $paginatorData['current_page'] ?? 1,
            'last_page'    => $paginatorData['last_page'] ?? 1,
            'per_page'     => $paginatorData['per_page'] ?? 15,
            'total'        => $paginatorData['total'] ?? 0,
        ];
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
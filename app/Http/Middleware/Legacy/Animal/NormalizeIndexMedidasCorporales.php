<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexMedidasCorporales
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
                    $data['data']['data'] = array_map([$this, 'transformToLegacyFormat'], $data['data']['data']);
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
     * Transforma un registro de medidas corporales V2 al formato legacy V1.
     */
    private function transformToLegacyFormat(array $item): array
    {
        $etapaAnimal = DB::table('animal_etapa')->where('id', $item['animal_etapa_id'])->first();

        return [
            'id_Medida'         => $item['id'],
            'Altura_HC'         => $item['altura_hc'],
            'Altura_HG'         => $item['altura_hg'],
            'Perimetro_PT'      => $item['perimetro_pt'],
            'Perimetro_PCA'     => $item['perimetro_pca'],
            'Longitud_LC'       => $item['longitud_lc'],
            'Longitud_LG'       => $item['longitud_lg'],
            'Anchura_AG'        => $item['anchura_ag'],
            'medida_etapa_anid' => $etapaAnimal ? $etapaAnimal->animal_id : null,
            'medida_etapa_etid' => $etapaAnimal ? $etapaAnimal->etapa_id : null,
            'created_at'        => $item['created_at'] ?? null,
            'updated_at'        => $item['updated_at'] ?? null,
        ];
    }
}

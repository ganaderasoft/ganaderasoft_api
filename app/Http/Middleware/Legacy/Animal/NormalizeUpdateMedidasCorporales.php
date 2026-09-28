<?php

namespace App\Http\Middleware\Legacy\Animal;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUpdateMedidasCorporales
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
        if (array_key_exists('Altura_HC', $input)) $payload['altura_hc'] = $input['Altura_HC'];
        if (array_key_exists('Altura_HG', $input)) $payload['altura_hg'] = $input['Altura_HG'];
        if (array_key_exists('Perimetro_PT', $input)) $payload['perimetro_pt'] = $input['Perimetro_PT'];
        if (array_key_exists('Perimetro_PCA', $input)) $payload['perimetro_pca'] = $input['Perimetro_PCA'];
        if (array_key_exists('Longitud_LC', $input)) $payload['longitud_lc'] = $input['Longitud_LC'];
        if (array_key_exists('Longitud_LG', $input)) $payload['longitud_lg'] = $input['Longitud_LG'];
        if (array_key_exists('Anchura_AG', $input)) $payload['anchura_ag'] = $input['Anchura_AG'];

        return $payload;
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

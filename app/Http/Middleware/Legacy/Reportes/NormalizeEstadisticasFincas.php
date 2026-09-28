<?php

namespace App\Http\Middleware\Legacy\Reportes;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NormalizeEstadisticasFincas
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Intercept V1 request params
        if ($request->has('id_propietario')) {
            $request->merge(['propietario_id' => $request->query('id_propietario')]);
        }
        if ($request->has('id_finca')) {
            $request->merge(['finca_id' => $request->query('id_finca')]);
        }

        $response = $next($request);

        // Intercept V2 response and format to V1 if not V2 requested
        if ($request->header('X-API-VERSION') !== '2' && $response instanceof JsonResponse) {
            $data = $response->getData(true);

            if (isset($data['data'])) {
                if (isset($data['data']['fincas'])) {
                    $data['data']['fincas'] = array_map(function ($finca) {
                        return [
                            'id_Finca' => $finca['finca_id'] ?? null,
                            'Nombre' => $finca['nombre'] ?? null,
                            'cantidad_rebanos' => $finca['cantidad_rebanos'] ?? 0,
                            'cantidad_animales' => $finca['cantidad_animales'] ?? 0,
                            'cantidad_personal' => $finca['cantidad_personal'] ?? 0,
                        ];
                    }, $data['data']['fincas']);
                }

                if (isset($data['data']['rebanos'])) {
                    $data['data']['rebanos'] = array_map(function ($rebano) {
                        return [
                            'id_Rebano' => $rebano['rebano_id'] ?? null,
                            'id_Finca' => $rebano['finca_id'] ?? null,
                            'Nombre' => $rebano['nombre'] ?? null,
                            'cantidad_animales' => $rebano['cantidad_animales'] ?? 0,
                        ];
                    }, $data['data']['rebanos']);
                }

                $response->setData($data);
            }
        }

        return $response;
    }
}

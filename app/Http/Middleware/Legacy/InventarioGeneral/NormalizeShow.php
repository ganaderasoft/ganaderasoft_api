<?php

namespace App\Http\Middleware\Legacy\InventarioGeneral;

use Closure;
use Illuminate\Http\Request;

class NormalizeShow
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $response = $next($request);

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                $item = $data['data'];
                $legacyData = [
                    'id_InvGen' => $item['id'],
                    'id_Finca' => $item['finca_id'],
                    'Num_Personal' => $item['num_personal'],
                    'Fecha_Inventario' => $item['fecha_inventario'],
                ];
                $response->setContent(json_encode(['data' => $legacyData]));
            }
        }

        return $response;
    }
}

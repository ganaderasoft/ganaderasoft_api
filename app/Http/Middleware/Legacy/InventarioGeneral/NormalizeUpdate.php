<?php

namespace App\Http\Middleware\Legacy\InventarioGeneral;

use Closure;
use Illuminate\Http\Request;

class NormalizeUpdate
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-VERSION') !== '2') {
            if ($request->has('id_Finca')) {
                $request->merge(['finca_id' => $request->input('id_Finca')]);
            }
            if ($request->has('Num_Personal')) {
                $request->merge(['num_personal' => $request->input('Num_Personal')]);
            }
            if ($request->has('Fecha_Inventario')) {
                $request->merge(['fecha_inventario' => $request->input('Fecha_Inventario')]);
            }
        }

        $response = $next($request);

        if ($request->header('X-API-VERSION') !== '2' && $response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
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

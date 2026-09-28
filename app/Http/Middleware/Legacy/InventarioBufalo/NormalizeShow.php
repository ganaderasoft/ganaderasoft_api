<?php

namespace App\Http\Middleware\Legacy\InventarioBufalo;

use Closure;
use Illuminate\Http\Request;

class NormalizeShow
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$request->hasHeader('X-API-VERSION') || $request->header('X-API-VERSION') < 2) {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']) && is_array($content['data'])) {
                $item = $content['data'];
                $content['data'] = [
                    'id_InvBufalo' => $item['id'],
                    'id_Finca' => $item['finca_id'],
                    'Num_Becerro' => $item['num_becerro'],
                    'Num_Anojo' => $item['num_anojo'],
                    'Num_Bubilla' => $item['num_bubilla'],
                    'Num_Bufalo' => $item['num_bufalo'],
                    'Fecha_Inventario' => $item['fecha_inventario'] ? substr($item['fecha_inventario'], 0, 10) : null,
                ];
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }
}

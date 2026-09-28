<?php

namespace App\Http\Middleware\Legacy\InventarioBufalo;

use Closure;
use Illuminate\Http\Request;

class NormalizeIndex
{
    public function handle(Request $request, Closure $next)
    {
        // Change legacy query params to V2 if necessary
        if ($request->has('id_Finca')) {
            $request->merge(['finca_id' => $request->id_Finca]);
        }

        $response = $next($request);

        if (!$request->hasHeader('X-API-VERSION') || $request->header('X-API-VERSION') < 2) {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']['data'])) {
                $mapped = array_map(function ($item) {
                    return [
                        'id_InvBufalo' => $item['id'],
                        'id_Finca' => $item['finca_id'],
                        'Num_Becerro' => $item['num_becerro'],
                        'Num_Anojo' => $item['num_anojo'],
                        'Num_Bubilla' => $item['num_bubilla'],
                        'Num_Bufalo' => $item['num_bufalo'],
                        'Fecha_Inventario' => $item['fecha_inventario'] ? substr($item['fecha_inventario'], 0, 10) : null,
                        // some fields might be needed by legacy app, preserving total_bufalos as total if needed
                    ];
                }, $content['data']['data']);
                
                $content['data']['data'] = $mapped;
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }
}

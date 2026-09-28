<?php

namespace App\Http\Middleware\Legacy\InventarioBufalo;

use Closure;
use Illuminate\Http\Request;

class NormalizeUpdate
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('X-API-VERSION') || $request->header('X-API-VERSION') < 2) {
            $mapped = [];
            if ($request->has('id_Finca')) $mapped['finca_id'] = $request->input('id_Finca');
            if ($request->has('Num_Becerro')) $mapped['num_becerro'] = $request->input('Num_Becerro');
            if ($request->has('Num_Anojo')) $mapped['num_anojo'] = $request->input('Num_Anojo');
            if ($request->has('Num_Bubilla')) $mapped['num_bubilla'] = $request->input('Num_Bubilla');
            if ($request->has('Num_Bufalo')) $mapped['num_bufalo'] = $request->input('Num_Bufalo');
            if ($request->has('Fecha_Inventario')) $mapped['fecha_inventario'] = $request->input('Fecha_Inventario');

            $request->merge($mapped);
        }

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

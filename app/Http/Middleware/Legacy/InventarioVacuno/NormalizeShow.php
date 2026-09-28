<?php

namespace App\Http\Middleware\Legacy\InventarioVacuno;

use Closure;
use Illuminate\Http\Request;

class NormalizeShow
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->header('X-API-VERSION') == '2' || !$response->isSuccessful()) {
            return $response;
        }

        $data = $response->getData(true);

        if (isset($data['data'])) {
            $item = $data['data'];
            
            $mappedData = [
                'id_InvVacuno' => $item['id'],
                'id_Finca' => $item['finca_id'],
                'Num_Becerra' => $item['num_becerra'],
                'Num_Mauta' => $item['num_mauta'],
                'Num_Novilla' => $item['num_novilla'],
                'Num_Vaca' => $item['num_vaca'],
                'Num_Becerro' => $item['num_becerro'],
                'Num_Maute' => $item['num_maute'],
                'Num_Torete' => $item['num_torete'],
                'Num_Toro' => $item['num_toro'],
                'Fecha_Inventario' => $item['fecha_inventario']
            ];

            $data['data'] = $mappedData;
            $response->setData($data);
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware\Legacy\InventarioVacuno;

use Closure;
use Illuminate\Http\Request;

class NormalizeUpdate
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-VERSION') != '2') {
            $mappedInput = [];
            
            if ($request->has('id_Finca')) $mappedInput['finca_id'] = $request->input('id_Finca');
            if ($request->has('Num_Becerra')) $mappedInput['num_becerra'] = $request->input('Num_Becerra');
            if ($request->has('Num_Mauta')) $mappedInput['num_mauta'] = $request->input('Num_Mauta');
            if ($request->has('Num_Novilla')) $mappedInput['num_novilla'] = $request->input('Num_Novilla');
            if ($request->has('Num_Vaca')) $mappedInput['num_vaca'] = $request->input('Num_Vaca');
            if ($request->has('Num_Becerro')) $mappedInput['num_becerro'] = $request->input('Num_Becerro');
            if ($request->has('Num_Maute')) $mappedInput['num_maute'] = $request->input('Num_Maute');
            if ($request->has('Num_Torete')) $mappedInput['num_torete'] = $request->input('Num_Torete');
            if ($request->has('Num_Toro')) $mappedInput['num_toro'] = $request->input('Num_Toro');
            if ($request->has('Fecha_Inventario')) $mappedInput['fecha_inventario'] = $request->input('Fecha_Inventario');

            $request->merge($mappedInput);
        }

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

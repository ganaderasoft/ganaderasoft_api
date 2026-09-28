<?php

namespace App\Http\Middleware\Legacy\PersonalFinca;

use Closure;
use Illuminate\Http\Request;

class NormalizeShow
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->isSuccessful() && $request->header('X-API-VERSION') !== '2') {
            $data = $response->getData(true);
            
            if (isset($data['data'])) {
                $item = $data['data'];
                $data['data'] = [
                    'id_Tecnico' => $item['id'],
                    'id_Finca' => $item['finca_id'],
                    'Cedula' => isset($item['persona']['cedula']) ? (int) preg_replace('/[^0-9]/', '', $item['persona']['cedula']) : null,
                    'Nombre' => $item['persona']['nombre'] ?? null,
                    'Apellido' => $item['persona']['apellido'] ?? null,
                    'Telefono' => $item['persona']['telefono'] ?? null,
                    'Correo' => $item['persona']['correo'] ?? null,
                    'Fecha_Nacimiento' => $item['persona']['fecha_nacimiento'] ?? null,
                    'Fecha_Ingreso' => $item['fecha_ingreso'] ?? null,
                    'Tipo_Trabajador' => $item['tipo_trabajador']['nombre'] ?? null,
                    'created_at' => $item['created_at'] ?? null,
                    'updated_at' => $item['updated_at'] ?? null,
                ];

                $response->setData($data);
            }
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware\Legacy\PersonalFinca;

use Closure;
use Illuminate\Http\Request;

class NormalizeIndex
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->isSuccessful() && $request->header('X-API-VERSION') !== '2') {
            $data = $response->getData(true);
            
            if (isset($data['data'])) {
                $isPaginated = isset($data['data']['data']) && is_array($data['data']['data']);
                $items = $isPaginated ? $data['data']['data'] : $data['data'];
                
                $mappedData = array_map(function ($item) {
                    return [
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
                }, $items);

                if ($isPaginated) {
                    $paginatedObj = $data['data'];
                    $data['data'] = $mappedData;
                    $data['pagination'] = [
                        'current_page' => $paginatedObj['current_page'] ?? 1,
                        'last_page' => $paginatedObj['last_page'] ?? 1,
                        'per_page' => $paginatedObj['per_page'] ?? 15,
                        'total' => $paginatedObj['total'] ?? 0,
                    ];
                } else {
                    $data['data'] = $mappedData;
                }

                $response->setData($data);
            }
        }

        return $response;
    }
}

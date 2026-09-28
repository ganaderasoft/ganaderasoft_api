<?php

namespace App\Http\Middleware\Legacy\PersonalFinca;

use Closure;
use Illuminate\Http\Request;
use App\Models\TipoTrabajador;

class NormalizeStore
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $mappedData = [];
            
            if ($request->has('id_Finca')) $mappedData['finca_id'] = $request->input('id_Finca');
            if ($request->has('Cedula')) {
                $mappedData['cedula'] = 'V' . $request->input('Cedula');
            }
            if ($request->has('Nombre')) $mappedData['nombre'] = $request->input('Nombre');
            if ($request->has('Apellido')) $mappedData['apellido'] = $request->input('Apellido');
            if ($request->has('Telefono')) $mappedData['telefono'] = $request->input('Telefono');
            if ($request->has('Correo')) $mappedData['correo'] = $request->input('Correo');
            if ($request->has('Fecha_Nacimiento')) $mappedData['fecha_nacimiento'] = $request->input('Fecha_Nacimiento');
            if ($request->has('Fecha_Ingreso')) $mappedData['fecha_ingreso'] = $request->input('Fecha_Ingreso');
            
            if ($request->has('Tipo_Trabajador')) {
                $tipoNombre = $request->input('Tipo_Trabajador');
                $tipo = TipoTrabajador::where('nombre', $tipoNombre)->first();
                if ($tipo) {
                    $mappedData['tipo_trabajador_id'] = $tipo->id;
                }
            }

            $request->merge($mappedData);
        }

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

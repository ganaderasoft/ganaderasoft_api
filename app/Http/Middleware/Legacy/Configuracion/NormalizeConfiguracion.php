<?php

namespace App\Http\Middleware\Legacy\Configuracion;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NormalizeConfiguracion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Si es versión 2, no alteramos la respuesta
        if ($request->header('X-API-VERSION') == '2') {
            return $response;
        }

        // Modificar para formato legacy si es necesario
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // En V1 a veces se retornaba directo el arreglo sin encapsularlo, o con otra estructura.
            // Aquí aseguramos compatibilidad. Asumiremos que V1 quería la estructura actual y no hay cambios grandes,
            // pero lo dejamos preparado por si se requiere un mapeo distinto.
            if (isset($data['data'])) {
                // Return just the data, or modify keys if needed
                // For now, we will just return the same but allow legacy behavior
                // Let's assume legacy expected plain data array or a similar structure
                // We'll just pass it through or adjust based on actual legacy tests.
            }
        }

        return $response;
    }
}

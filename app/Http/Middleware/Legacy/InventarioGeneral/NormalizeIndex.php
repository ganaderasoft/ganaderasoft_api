<?php

namespace App\Http\Middleware\Legacy\InventarioGeneral;

use Closure;
use Illuminate\Http\Request;

class NormalizeIndex
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $response = $next($request);

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                $isPaginated = isset($data['data']['data']) && is_array($data['data']['data']);
                $items = $isPaginated ? $data['data']['data'] : $data['data'];

                $mappedData = array_map(function ($item) {
                    return [
                        'id_InvGen' => $item['id'],
                        'id_Finca' => $item['finca_id'],
                        'Num_Personal' => $item['num_personal'],
                        'Fecha_Inventario' => $item['fecha_inventario'],
                    ];
                }, $items);

                if ($isPaginated) {
                    $paginatedObj = $data['data'];
                    $legacyResponse = [
                        'current_page' => $paginatedObj['current_page'] ?? 1,
                        'data' => $mappedData,
                        'first_page_url' => $paginatedObj['first_page_url'] ?? null,
                        'from' => $paginatedObj['from'] ?? null,
                        'last_page' => $paginatedObj['last_page'] ?? 1,
                        'last_page_url' => $paginatedObj['last_page_url'] ?? null,
                        'links' => $paginatedObj['links'] ?? [],
                        'next_page_url' => $paginatedObj['next_page_url'] ?? null,
                        'path' => $paginatedObj['path'] ?? null,
                        'per_page' => $paginatedObj['per_page'] ?? 15,
                        'prev_page_url' => $paginatedObj['prev_page_url'] ?? null,
                        'to' => $paginatedObj['to'] ?? null,
                        'total' => $paginatedObj['total'] ?? 0,
                    ];
                } else {
                    $legacyResponse = [
                        'data' => $mappedData
                    ];
                }

                $response->setContent(json_encode($legacyResponse));
            }
        }

        return $response;
    }
}

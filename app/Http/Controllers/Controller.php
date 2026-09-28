<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Pagination\AbstractPaginator;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Formatea una colección de recursos asegurando que se conserve la estructura 
     * de paginación si los datos originales estaban paginados.
     *
     * @param string $resourceClass El nombre de la clase Resource a usar.
     * @param mixed $data Los datos a procesar (Collection, Paginator, etc).
     * @return array
     */
    protected function formatCollection($resourceClass, $data)
    {
        $collection = $resourceClass::collection($data);
        
        // Verificamos si es una paginación nativa de Laravel para inyectar los datos parseados 
        // manteniendo la estructura original (links, current_page, etc.)
        if ($data instanceof AbstractPaginator) {
            $paginated = $data->toArray();
            $paginated['data'] = $collection->resolve();
            return $paginated;
        }
        
        return $collection->resolve();
    }

    /**
     * Formatea un recurso individual utilizando su clase Resource correspondiente.
     *
     * @param string $resourceClass El nombre de la clase Resource a usar.
     * @param mixed $data El modelo o entidad a procesar.
     * @return array
     */
    protected function formatResource($resourceClass, $data)
    {
        return (new $resourceClass($data))->resolve();
    }
}

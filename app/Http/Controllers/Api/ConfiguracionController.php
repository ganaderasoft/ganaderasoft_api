<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Configuracion\ConfiguracionService;
use App\Http\Resources\Configuracion\ConfiguracionResource;
use App\Http\Middleware\Legacy\Configuracion\NormalizeConfiguracion;

class ConfiguracionController extends Controller
{
    protected $configuracionService;

    public function __construct(ConfiguracionService $configuracionService)
    {
        $this->configuracionService = $configuracionService;
        $this->middleware(NormalizeConfiguracion::class);
    }

    /**
     * Get Tipo Explotacion list.
     */
    public function tipoExplotacion()
    {
        try {
            $data = $this->configuracionService->getTipoExplotacion();
            return response()->json([
                'success' => true,
                'message' => 'Lista de tipos de explotación obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de tipos de explotación',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Metodo Riego list.
     */
    public function metodoRiego()
    {
        try {
            $data = $this->configuracionService->getMetodoRiego();
            return response()->json([
                'success' => true,
                'message' => 'Lista de métodos de riego obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de métodos de riego',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get PH Suelo list.
     */
    public function phSuelo()
    {
        try {
            $data = $this->configuracionService->getPhSuelo();
            return response()->json([
                'success' => true,
                'message' => 'Lista de pH de suelo obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de pH de suelo',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Textura Suelo list.
     */
    public function texturaSuelo()
    {
        try {
            $data = $this->configuracionService->getTexturaSuelo();
            return response()->json([
                'success' => true,
                'message' => 'Lista de texturas de suelo obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de texturas de suelo',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Fuente Agua list.
     */
    public function fuenteAgua()
    {
        try {
            $data = $this->configuracionService->getFuenteAgua();
            return response()->json([
                'success' => true,
                'message' => 'Lista de fuentes de agua obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de fuentes de agua',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Sexo list.
     */
    public function sexo()
    {
        try {
            $data = $this->configuracionService->getSexo();
            return response()->json([
                'success' => true,
                'message' => 'Lista de sexos obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de sexos',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Tipo Relieve list.
     */
    public function tipoRelieve()
    {
        try {
            $data = $this->configuracionService->getTipoRelieve();
            return response()->json([
                'success' => true,
                'message' => 'Lista de tipos de relieve obtenida exitosamente',
                'data' => ConfiguracionResource::collection($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de tipos de relieve',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
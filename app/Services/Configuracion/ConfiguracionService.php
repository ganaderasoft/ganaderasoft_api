<?php

namespace App\Services\Configuracion;

class ConfiguracionService
{
    /**
     * Get JSON data from resources.
     */
    public function getConfiguracion(string $filename): array
    {
        $path = resource_path("datos-constantes/{$filename}");
        
        if (!file_exists($path)) {
            throw new \Exception("Archivo de configuración no encontrado: {$filename}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar JSON: " . json_last_error_msg());
        }

        return $data;
    }

    public function getTipoExplotacion(): array
    {
        return $this->getConfiguracion('tipo-explotacion.json');
    }

    public function getMetodoRiego(): array
    {
        return $this->getConfiguracion('metodo-riego.json');
    }

    public function getPhSuelo(): array
    {
        return $this->getConfiguracion('ph-suelo.json');
    }

    public function getTexturaSuelo(): array
    {
        return $this->getConfiguracion('textura-suelo.json');
    }

    public function getFuenteAgua(): array
    {
        return $this->getConfiguracion('fuente-agua.json');
    }

    public function getSexo(): array
    {
        return $this->getConfiguracion('sexo.json');
    }

    public function getTipoRelieve(): array
    {
        return $this->getConfiguracion('tipo-relieve.json');
    }
}

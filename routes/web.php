<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Este archivo maneja las peticiones dirigidas a la raíz del servidor web
| (sin el prefijo /api). Como GanaderaSoft opera exclusivamente como una
| API REST, la ruta raíz retorna información detallada sobre el servicio,
| versión y guía de uso para realizar peticiones a los endpoints de la API.
|
*/

Route::get('/', function () {
    return response()->json([
        'service'     => 'GanaderaSoft API Core',
        'version'     => '2.0.0',
        'status'      => 'operational',
        'description' => 'API REST para la gestión integral de operaciones ganaderas pecuarias.',
        'instructions' => [
            'base_url'         => url('/api'),
            'required_headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'X-API-VERSION' => '2',
                'Authorization' => 'Bearer <token> (requerido para endpoints protegidos)',
            ],
            'quickstart' => [
                'login'    => 'POST /api/auth/login con credenciales { "login": "<email|cedula>", "password": "<clave>" }',
                'token'    => 'Usar el token devuelto en la cabecera "Authorization: Bearer <token>"',
                'requests' => 'Todas las rutas de recursos y consultas se encuentran bajo el prefijo /api/...',
            ],
        ],
        'endpoints' => [
            'health'   => url('/api/health'),
            'login'    => url('/api/auth/login'),
            'user'     => url('/api/user'),
            'profile'  => url('/api/profile'),
            'fincas'   => url('/api/fincas'),
            'animales' => url('/api/animales'),
            'rebanos'  => url('/api/rebanos'),
        ],
        'timestamp' => now()->toIso8601String(),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

<?php

use App\Http\Controllers\Api\ArbolGenController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PersonaController;
use App\Http\Controllers\Api\UserFincaController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\CambiosAnimalController;
use App\Http\Controllers\Api\CasaComercialController;
use App\Http\Controllers\Api\ComposicionRazaController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\CuernoController;
use App\Http\Controllers\Api\DiagnosticoController;
use App\Http\Controllers\Api\DiaPalpacionController;
use App\Http\Controllers\Api\EstadoAnimalController;
use App\Http\Controllers\Api\EstadoSaludController;
use App\Http\Controllers\Api\EtapaController;
use App\Http\Controllers\Api\FincaController;
use App\Http\Controllers\Api\FoliculoController;
use App\Http\Controllers\Api\InventarioBufaloController;
use App\Http\Controllers\Api\InventarioGeneralController;
use App\Http\Controllers\Api\InventarioVacunoController;
use App\Http\Controllers\Api\LactanciaController;
use App\Http\Controllers\Api\LecheController;
use App\Http\Controllers\Api\MedidasCorporalesController;
use App\Http\Controllers\Api\MovimientoRebanoController;
use App\Http\Controllers\Api\OvarioController;
use App\Http\Controllers\Api\PalpacionController;
use App\Http\Controllers\Api\PersonalFincaController;
use App\Http\Controllers\Api\PesoCorporalController;
use App\Http\Controllers\Api\PropietarioController;
use App\Http\Controllers\Api\RebanoController;
use App\Http\Controllers\Api\RegistroCeloController;
use App\Http\Controllers\Api\ReportesController;
use App\Http\Controllers\Api\ReproduccionAnimalController;
use App\Http\Controllers\Api\SemenToroController;
use App\Http\Controllers\Api\ServicioAnimalController;
use App\Http\Controllers\Api\TerrenoController;
use App\Http\Controllers\Api\TipoAnimalController;
use App\Http\Controllers\Api\TipoTrabajadorController;
use App\Http\Controllers\Api\TratamientoController;
use App\Http\Controllers\Api\VacunaController;
use App\Http\Controllers\Api\VacunacionController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API
|--------------------------------------------------------------------------
|
| Aquí es donde se registran las rutas de la API de la aplicación.
| Estas rutas son cargadas por el RouteServiceProvider y a todas se
| les asigna automáticamente el grupo de middleware "api".
|
*/

// Healthcheck público del API
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'service'   => 'GanaderaSoft API Core',
        'version'   => '2.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    
    // Rutas permitidas a todos los usuarios autenticados (incluso si su estado es suspendido)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile/photo', [AuthController::class, 'updatePhoto']);
    Route::delete('/profile/photo', [AuthController::class, 'deletePhoto']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Rutas operativas del sistema (restringidas únicamente a usuarios con estado activo)
    Route::middleware(EnsureUserIsActive::class)->group(function () {
        
        Route::post('/auth/register', [AuthController::class, 'register']);

        // Gestión de Roles y Permisos
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);
        
        // Usuarios y Fincas de Usuarios
        Route::apiResource('users', UserController::class);
        
        // Gestión de Personas
        Route::patch('/personas/{persona}/disable', [PersonaController::class, 'disable']);
        Route::patch('/personas/{persona}/enable', [PersonaController::class, 'enable']);
        Route::apiResource('personas', PersonaController::class);
        Route::patch('users/{user}/disable', [UserController::class, 'disable']);
        Route::patch('users/{user}/enable', [UserController::class, 'enable']);
        
        // Fincas de Usuarios
        Route::apiResource('users.access-fincas', UserFincaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('users/{user}/access-fincas/{access_finca}/disable', [UserFincaController::class, 'disableAccess']);
        Route::patch('users/{user}/access-fincas/{access_finca}/enable', [UserFincaController::class, 'enableAccess']);
        
        // Roles de Usuario y Permisos
        Route::apiResource('users.roles', UserRoleController::class)->only(['index', 'store', 'destroy']);
        Route::get('/users/{user}/permissions', [UserRoleController::class, 'getPermissions']);
        
        // Permisos del Rol
        Route::apiResource('roles.permissions', RolePermissionController::class)->only(['index', 'store', 'destroy']);

        // Fincas
        Route::get('fincas/importar/plantilla', [FincaController::class, 'plantilla']);
        Route::post('fincas/importar', [FincaController::class, 'importar']);
        Route::match(['post', 'patch'], 'fincas/{id}/archivar', [FincaController::class, 'archive']);
        Route::match(['post', 'patch'], 'fincas/{id}/desarchivar', [FincaController::class, 'unarchive']);
        Route::apiResource('fincas', FincaController::class);
        Route::apiResource('propietarios', PropietarioController::class);
        
        // Rebaños
        Route::match(['post', 'patch'], 'rebanos/{id}/archivar', [RebanoController::class, 'archive']);
        Route::match(['post', 'patch'], 'rebanos/{id}/desarchivar', [RebanoController::class, 'unarchive']);
        Route::apiResource('rebanos', RebanoController::class);
        
        // Animales
        Route::post('animales/importar', [AnimalController::class, 'cargarAnimalesMasivo']);
        Route::match(['post', 'patch'], 'animales/{id}/archivar', [AnimalController::class, 'archive']);
        Route::match(['post', 'patch'], 'animales/{id}/desarchivar', [AnimalController::class, 'unarchive']);
        Route::apiResource('animales', AnimalController::class);
        Route::apiResource('inventarios-bufalo', InventarioBufaloController::class);
        Route::apiResource('tipos-animal', TipoAnimalController::class);
        Route::apiResource('estados-salud', EstadoSaludController::class);
        Route::apiResource('estados-animal', EstadoAnimalController::class);
        Route::apiResource('composicion-raza', ComposicionRazaController::class);
        Route::apiResource('etapas', EtapaController::class);
        Route::apiResource('tipos-trabajador', TipoTrabajadorController::class);

        // Nuevas rutas CRUD de entidades
        Route::match(['POST', 'PATCH'], 'personal-finca/{personal_finca}/enable', [PersonalFincaController::class, 'enable']);
        Route::match(['POST', 'PATCH'], 'personal-finca/{personal_finca}/disable', [PersonalFincaController::class, 'disable']);
        Route::post('personal-finca/{personal_finca}/create-user', [PersonalFincaController::class, 'convertToUser']);
        Route::apiResource('personal-finca', PersonalFincaController::class);
        Route::apiResource('peso-corporal', PesoCorporalController::class);
        Route::apiResource('lactancia', LactanciaController::class);
        Route::apiResource('leche', LecheController::class);
        Route::get('medidas-corporales/{id}/indices', [MedidasCorporalesController::class, 'indices']);
        Route::get('animales/{id}/indices-corporales', [MedidasCorporalesController::class, 'evolucionIndicesPorAnimal']);
        Route::apiResource('medidas-corporales', MedidasCorporalesController::class);
        Route::apiResource('cambios-animal', CambiosAnimalController::class);

        // Configuration routes (JSON-based)
        Route::prefix('configuracion')->group(function () {
            Route::get('tipo-explotacion', [ConfiguracionController::class, 'tipoExplotacion']);
            Route::get('metodo-riego', [ConfiguracionController::class, 'metodoRiego']);
            Route::get('ph-suelo', [ConfiguracionController::class, 'phSuelo']);
            Route::get('textura-suelo', [ConfiguracionController::class, 'texturaSuelo']);
            Route::get('fuente-agua', [ConfiguracionController::class, 'fuenteAgua']);
            Route::get('sexo', [ConfiguracionController::class, 'sexo']);
            Route::get('tipo-relieve', [ConfiguracionController::class, 'tipoRelieve']);
        });

        // Reports routes
        Route::prefix('reportes')->group(function () {
            Route::get('fincas', [ReportesController::class, 'estadisticasFincas']);
            Route::get('general', [ReportesController::class, 'general']);
            Route::get('lactancias', [ReportesController::class, 'lactancias']);
            Route::get('reproductivo', [ReportesController::class, 'reproductivo']);
            Route::get('pesaje-leche', [ReportesController::class, 'pesajeLeche']);
            Route::get('rebanos', [ReportesController::class, 'rebanos']);
        });

        // Terreno
        Route::apiResource('terrenos', TerrenoController::class);

        // Reproducción
        Route::apiResource('registro-celo', RegistroCeloController::class);
        Route::apiResource('servicio-animal', ServicioAnimalController::class);
        Route::apiResource('reproduccion-animal', ReproduccionAnimalController::class);
        Route::apiResource('palpacion', PalpacionController::class);
        Route::apiResource('dias-palpacion', DiaPalpacionController::class);
        Route::apiResource('foliculos', FoliculoController::class);
        Route::apiResource('ovarios', OvarioController::class);
        Route::apiResource('cuernos', CuernoController::class);
        Route::apiResource('semen-toro', SemenToroController::class);

        // Salud animal
        Route::apiResource('diagnostico', DiagnosticoController::class);
        Route::apiResource('tratamiento', TratamientoController::class);
        Route::apiResource('vacunas', VacunaController::class);
        Route::apiResource('casas-comerciales', CasaComercialController::class);
        Route::apiResource('vacunaciones', VacunacionController::class);

        // Inventario
        Route::apiResource('inventario-general', InventarioGeneralController::class);
        Route::apiResource('inventario-vacuno', InventarioVacunoController::class);

        // Movimientos de rebaño
        Route::apiResource('movimiento-rebano', MovimientoRebanoController::class);

        // Animal relationship management routes
        Route::prefix('animales/{animal}')->group(function () {
            Route::post('estado-animal', [AnimalController::class, 'createEstadoAnimal']);
            Route::put('estado-animal/{estado}', [AnimalController::class, 'updateEstadoAnimal']);
            Route::post('etapa-animal', [AnimalController::class, 'createEtapaAnimal']);
            Route::put('etapa-animal/{etapa}', [AnimalController::class, 'updateEtapaAnimal']);

            // Árbol genealógico
            Route::get('arbol', [ArbolGenController::class, 'getTree']);
            Route::post('progenitor', [ArbolGenController::class, 'setParent']);
            Route::delete('progenitor/{tipo}', [ArbolGenController::class, 'removeParent']);
            Route::get('progenitores-disponibles', [ArbolGenController::class, 'getAvailableParents']);
        });

    });
});

<?php

use App\Http\Controllers\Api\CameraController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MaintenanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Inventario y Mantenimiento de Cámaras USC
|--------------------------------------------------------------------------
|
| Rutas protegidas mediante:
| - Autenticación Laravel Sanctum.
| - Verificación de usuarios activos.
| - Limitación de solicitudes.
| - Autorización institucional por permisos.
|
*/

Route::middleware([
    'auth:sanctum',
    'active',
    'throttle:60,1',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Usuario autenticado
    |--------------------------------------------------------------------------
    */

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión del inventario de cámaras
    |--------------------------------------------------------------------------
    */

    // Consultar cámaras.
    Route::get('/cameras', [CameraController::class, 'index'])
        ->middleware('can:view-cameras')
        ->name('api.cameras.index');

    // Registrar una cámara.
    Route::post('/cameras', [CameraController::class, 'store'])
        ->middleware('can:create-cameras')
        ->name('api.cameras.store');

    // Consultar una cámara específica.
    Route::get('/cameras/{camera}', [CameraController::class, 'show'])
        ->middleware('can:view-cameras')
        ->name('api.cameras.show');

    // Dar de baja una cámara.
    // El controlador y el servicio existentes gestionan la autorización.
    Route::delete('/cameras/{camera}', [CameraController::class, 'destroy'])
        ->name('api.cameras.destroy');

    /*
    |--------------------------------------------------------------------------
    | Gestión de incidencias
    |--------------------------------------------------------------------------
    |
    | API v1 de incidencias.
    | Incluye registro, consulta, sugerencias de IA
    | y confirmación humana de categorías.
    |
    */

    Route::prefix('v1/incidencias')->group(function () {

        // Registrar incidencia.
        Route::post('/', [IncidentController::class, 'store']);

        // Consultar incidencia.
        Route::get('/{incident}', [IncidentController::class, 'show']);

        // Obtener vista previa para clasificación por IA.
        Route::get(
            '/{incident}/sugerencias-ia/vista-previa',
            [IncidentController::class, 'preview']
        );

        // Solicitar sugerencia de clasificación mediante IA.
        Route::post(
            '/{incident}/sugerencias-ia',
            [IncidentController::class, 'suggest']
        );

        // Confirmar la categoría mediante revisión humana.
        Route::patch(
            '/{incident}/categoria',
            [IncidentController::class, 'confirm']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión de mantenimientos - HU-09
    |--------------------------------------------------------------------------
    |
    | Registra intervenciones técnicas sobre cámaras existentes.
    |
    | Roles autorizados:
    | - Administrador.
    | - Coordinador.
    | - Técnico.
    |
    | Roles sin autorización:
    | - Auxiliar.
    | - Usuario sin rol.
    | - Usuario inactivo.
    |
    | La validación y persistencia se ejecutan mediante
    | MaintenanceRegistrationService.
    |
    */

    Route::post(
        '/v1/mantenimientos',
        [MaintenanceController::class, 'store']
    )
        ->middleware('can:create-maintenances')
        ->name('api.maintenances.store');

});

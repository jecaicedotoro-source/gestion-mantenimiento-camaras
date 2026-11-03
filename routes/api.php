<?php

use App\Http\Controllers\Api\CameraController;
use App\Http\Controllers\Api\IncidentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active', 'verified', 'throttle:60,1'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/cameras', [CameraController::class, 'index'])
        ->middleware('can:view-cameras')
        ->name('api.cameras.index');

    Route::post('/cameras', [CameraController::class, 'store'])
        ->middleware('can:create-cameras')
        ->name('api.cameras.store');

    Route::get('/cameras/{camera}', [CameraController::class, 'show'])
        ->middleware('can:view-cameras')
        ->name('api.cameras.show');

    // API de incidencias definida en el diseño del Módulo 2; comparte servicios con Livewire.
    Route::prefix('v1/incidencias')->group(function () {
        Route::post('/', [IncidentController::class, 'store']);
        Route::get('/{incident}', [IncidentController::class, 'show']);
        Route::get('/{incident}/sugerencias-ia/vista-previa', [IncidentController::class, 'preview']);
        Route::post('/{incident}/sugerencias-ia', [IncidentController::class, 'suggest']);
        Route::patch('/{incident}/categoria', [IncidentController::class, 'confirm']);
    });
});

<?php

use App\Http\Controllers\Api\CameraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/cameras', [CameraController::class, 'index'])
        ->middleware('can:view-cameras')
        ->name('api.cameras.index');

    Route::post('/cameras', [CameraController::class, 'store'])
        ->middleware('can:create-cameras')
        ->name('api.cameras.store');
});

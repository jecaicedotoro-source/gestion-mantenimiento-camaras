<?php

use App\Livewire\Dashboard;
use App\Livewire\Incidents\Index;
use App\Livewire\Maintenances\Create as MaintenanceCreate;
use App\Livewire\Maintenances\History as MaintenanceHistory;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web - Sistema USC
|--------------------------------------------------------------------------
|
| Acceso a las interfaces de autenticación, inventario,
| incidencias, administración y mantenimientos.
|
*/

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Inventario de cámaras
    |--------------------------------------------------------------------------
    */

    Route::livewire('cameras', 'pages::cameras.index')
        ->middleware('can:view-cameras')
        ->name('cameras.index');

    Route::livewire('cameras/create', 'pages::cameras.create')
        ->middleware('can:create-cameras')
        ->name('cameras.create');

    /*
    |--------------------------------------------------------------------------
    | Panel principal
    |--------------------------------------------------------------------------
    */

    Route::get('dashboard', Dashboard::class)
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Gestión de incidencias
    |--------------------------------------------------------------------------
    */

    Route::get('incidents', Index::class)
        ->name('incidents.index');

    /*
    |--------------------------------------------------------------------------
    | HU-09: Registro de mantenimientos
    |--------------------------------------------------------------------------
    |
    | Solo pueden acceder:
    | - Administradores.
    | - Coordinadores.
    | - Técnicos.
    |
    | La autorización se verifica mediante Laravel Gate.
    |
    */

    Route::get('mantenimientos/crear', MaintenanceCreate::class)
        ->middleware('can:create-maintenances')
        ->name('maintenances.create');

    // HU-12: registro correctivo precargado desde la incidencia seleccionada.
    Route::get('incidents/{incident}/mantenimiento-correctivo', MaintenanceCreate::class)
        ->whereNumber('incident')
        ->middleware('can:create-maintenances')
        ->name('maintenances.create-for-incident');

    // HU-10: consulta de historial de una cámara, incluida su baja lógica.
    Route::get('cameras/{camera}/mantenimientos', MaintenanceHistory::class)
        ->whereNumber('camera')
        ->middleware('can:view-cameras')
        ->name('maintenances.history');

    /*
    |--------------------------------------------------------------------------
    | Administración de usuarios
    |--------------------------------------------------------------------------
    */

    Route::livewire('admin/users', 'pages::admin.users.index')
        ->middleware('can:manage-users')
        ->name('admin.users.index');

});

/*
|--------------------------------------------------------------------------
| Configuración de cuenta
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';

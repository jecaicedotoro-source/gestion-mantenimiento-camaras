<?php

use App\Livewire\Dashboard;
use App\Livewire\Incidents\Index;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {

    Route::livewire('cameras', 'pages::cameras.index')
        ->middleware('can:view-cameras')
        ->name('cameras.index');

    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('incidents', Index::class)->name('incidents.index');

    Route::livewire('admin/users', 'pages::admin.users.index')
        ->middleware('can:manage-users')
        ->name('admin.users.index');

    Route::livewire('cameras/create', 'pages::cameras.create')
        ->middleware('can:create-cameras')
        ->name('cameras.create');

});

require __DIR__.'/settings.php';

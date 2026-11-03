<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('administrador puede consultar y registrar cámaras', function () {
    $role = Role::create([
        'name' => 'Administrador',
        'code' => 'administrador',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
    ]);

    expect(Gate::forUser($user)->allows('view-cameras'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create-cameras'))->toBeTrue();
});

test('coordinador puede consultar y registrar cámaras', function () {
    $role = Role::create([
        'name' => 'Coordinador',
        'code' => 'coordinador',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
    ]);

    expect(Gate::forUser($user)->allows('view-cameras'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create-cameras'))->toBeTrue();
});

test('técnico puede consultar pero no registrar cámaras', function () {
    $role = Role::create([
        'name' => 'Técnico',
        'code' => 'tecnico',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
    ]);

    expect(Gate::forUser($user)->allows('view-cameras'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create-cameras'))->toBeTrue();
});

test('auxiliar puede consultar pero no registrar cámaras', function () {
    $role = Role::create([
        'name' => 'Auxiliar',
        'code' => 'auxiliar',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
    ]);

    expect(Gate::forUser($user)->allows('view-cameras'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create-cameras'))->toBeTrue();
});

test('usuario sin rol no puede acceder al inventario', function () {
    $user = User::factory()->create([
        'role_id' => null,
    ]);

    expect(Gate::forUser($user)->denies('view-cameras'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create-cameras'))->toBeTrue();
});

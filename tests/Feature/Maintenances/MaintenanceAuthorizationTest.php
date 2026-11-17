<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/**
 * Crea un usuario activo con un rol institucional.
 */
function hu09CreateUserWithRole(string $code): User
{
    $names = [
        'administrador' => 'Administrador',
        'coordinador' => 'Coordinador',
        'tecnico' => 'Técnico',
        'auxiliar' => 'Auxiliar',
    ];

    $role = Role::create([
        'name' => $names[$code],
        'code' => $code,
    ]);

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

test('HU-09 administrador puede registrar mantenimientos', function () {
    $user = hu09CreateUserWithRole('administrador');

    expect(
        Gate::forUser($user)->allows('create-maintenances')
    )->toBeTrue();
});

test('HU-09 coordinador puede registrar mantenimientos', function () {
    $user = hu09CreateUserWithRole('coordinador');

    expect(
        Gate::forUser($user)->allows('create-maintenances')
    )->toBeTrue();
});

test('HU-09 tecnico puede registrar mantenimientos', function () {
    $user = hu09CreateUserWithRole('tecnico');

    expect(
        Gate::forUser($user)->allows('create-maintenances')
    )->toBeTrue();
});

test('HU-09 auxiliar no puede registrar mantenimientos', function () {
    $user = hu09CreateUserWithRole('auxiliar');

    expect(
        Gate::forUser($user)->denies('create-maintenances')
    )->toBeTrue();
});

test('HU-09 usuario sin rol no puede registrar mantenimientos', function () {
    $user = User::factory()->create([
        'role_id' => null,
    ]);

    expect(
        Gate::forUser($user)->denies('create-maintenances')
    )->toBeTrue();
});


test('HU-09 usuario inactivo no puede registrar mantenimientos', function () {
    $user = hu09CreateUserWithRole('administrador');

    // Desactivación explícita para preparar el escenario de prueba.
    $user->is_active = false;
    $user->save();

    // Recuperamos el estado real almacenado en SQLite.
    $user->refresh();

    expect($user->is_active)->toBeFalse();

    expect(
        Gate::forUser($user)->denies('create-maintenances')
    )->toBeTrue();
});
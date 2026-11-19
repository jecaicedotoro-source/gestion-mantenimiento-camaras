<?php

use App\Models\Camera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('el inventario ofrece confirmación al administrador y protege la acción Livewire', function () {
    $camera = Camera::create([
        'codigo_inventario' => 'BAJA-DEMO', 'marca' => 'Demo', 'modelo' => 'Demo',
        'tipo' => 'IP', 'ubicacion' => 'Laboratorio', 'estado' => 'activo',
    ]);
    $coordinator = Role::create(['name' => 'Coordinador', 'code' => 'coordinador']);
    $admin = Role::create(['name' => 'Administrador', 'code' => 'administrador']);

    $this->actingAs(User::factory()->create(['role_id' => $coordinator->id]));
    Livewire::test('pages::cameras.index')->assertDontSee('Dar de baja')
        ->call('retireCamera', $camera->id)->assertForbidden();
    $this->assertNotSoftDeleted($camera);

    $this->actingAs(User::factory()->create(['role_id' => $admin->id]));
    Livewire::test('pages::cameras.index')->assertSeeHtml('wire:confirm=')
        ->call('retireCamera', $camera->id)->assertHasNoErrors()
        ->assertDontSee('BAJA-DEMO');
    $this->assertSoftDeleted($camera);
});

test('invitado es redirigido al iniciar el registro', function () {
    $this->get(route('cameras.create'))
        ->assertRedirect(route('login'));
});

test('administrador accede al registro de cámaras', function () {
    $role = Role::create([
        'name' => 'Administrador',
        'code' => 'administrador',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('cameras.create'))
        ->assertOk();
});

test('técnico no puede abrir el registro de cámaras', function () {
    $role = Role::create([
        'name' => 'Técnico',
        'code' => 'tecnico',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('cameras.create'))
        ->assertForbidden();
});

test('auxiliar puede consultar el inventario', function () {
    $role = Role::create([
        'name' => 'Auxiliar',
        'code' => 'auxiliar',
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('cameras.index'))
        ->assertOk();
});

test('usuario sin rol no puede consultar cámaras', function () {
    $user = User::factory()->create([
        'role_id' => null,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('cameras.index'))
        ->assertForbidden();
});

<?php

use App\Models\Camera;
use App\Models\Role;
use App\Models\User;
use App\Services\CameraRegistrationService;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

/** Datos sintéticos; nunca corresponden al inventario institucional. */
function completeCameraPayload(): array
{
    return [
        'codigo_inventario' => 'DEMO-01', 'numero_serie' => null,
        'marca' => 'Demostración', 'modelo' => 'MVP', 'tipo' => 'IP',
        'direccion_ip' => null, 'ubicacion' => 'Laboratorio de demostración',
        'estado' => 'activo', 'observaciones' => null,
    ];
}

function completeCameraAdmin(): User
{
    $role = Role::firstOrCreate(['code' => 'administrador'], ['name' => 'Administrador']);

    return User::factory()->create(['role_id' => $role->id]);
}

test('CU01 Livewire guarda una cámara y la consulta después de la operación', function () {
    $this->actingAs(completeCameraAdmin());
    $component = Livewire::test('pages::cameras.create');
    foreach (completeCameraPayload() as $field => $value) {
        $component->set($field, $value ?? '');
    }
    $component->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('cameras', ['codigo_inventario' => 'DEMO-01']);
    $this->get(route('cameras.index'))->assertOk()->assertSee('DEMO-01');
});

test('CU01 normaliza blancos y conserva dos series ausentes como null', function () {
    $data = completeCameraPayload();
    $data['numero_serie'] = '   ';
    $data['marca'] = '  Demostración  ';
    $service = app(CameraRegistrationService::class);
    $service->register($data);
    $service->register($data);

    expect(Camera::count())->toBe(2)
        ->and(Camera::whereNull('numero_serie')->count())->toBe(2)
        ->and(Camera::first()->marca)->toBe('Demostración');
});

test('API consulta individual y búsqueda devuelven el registro persistido', function () {
    Sanctum::actingAs(completeCameraAdmin());
    $id = $this->postJson('/api/cameras', completeCameraPayload())->assertCreated()->json('data.id');
    $this->getJson('/api/cameras/'.$id)->assertOk()->assertJsonPath('data.codigo_inventario', 'DEMO-01');
    $this->getJson('/api/cameras?search=DEMO-01')->assertOk()->assertJsonPath('total', 1);
    $this->getJson('/api/cameras?search=INEXISTENTE')->assertOk()->assertJsonPath('total', 0);
    $this->getJson('/api/cameras/99999')->assertNotFound();
});

test('API exige verificación del correo y no admite datos arbitrarios', function () {
    $admin = completeCameraAdmin();
    $admin->forceFill(['email_verified_at' => null])->save();
    Sanctum::actingAs($admin);
    $this->postJson('/api/cameras', completeCameraPayload())->assertForbidden();
    expect(Camera::count())->toBe(0);
});

test('API valida estado longitud e IP y no filtra datos internos del usuario', function () {
    Sanctum::actingAs(completeCameraAdmin());
    $data = completeCameraPayload();
    $data['estado'] = 'desconocido';
    $data['observaciones'] = str_repeat('x', 2001);
    $data['direccion_ip'] = 'incorrecta';
    $this->postJson('/api/cameras', $data)->assertUnprocessable()
        ->assertJsonValidationErrors(['estado', 'observaciones', 'direccion_ip']);
    expect(Camera::count())->toBe(0);
});

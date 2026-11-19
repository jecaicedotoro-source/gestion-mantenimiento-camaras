<?php

use App\Models\Camera;
use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('solo un administrador retira una cámara y conserva sus incidencias', function () {
    $camera = Camera::create(apiCameraData());
    $coordinator = apiUserWithRole('coordinador');
    $incident = Incident::create([
        'camera_id' => $camera->id,
        'reported_by' => $coordinator->id,
        'description' => 'Pérdida intermitente de imagen.',
    ]);
    $this->deleteJson('/api/cameras/'.$camera->id)->assertUnauthorized();
    Sanctum::actingAs($coordinator);
    $this->deleteJson('/api/cameras/'.$camera->id)->assertForbidden();

    Sanctum::actingAs(apiUserWithRole('administrador'));
    $this->deleteJson('/api/cameras/'.$camera->id)->assertOk();
    $this->assertSoftDeleted($camera);
    $this->getJson('/api/cameras')->assertJsonPath('total', 0);
    $this->getJson('/api/cameras/'.$camera->id)->assertNotFound();
    $this->deleteJson('/api/cameras/'.$camera->id)->assertNotFound();
    expect($incident->fresh()->camera->id)->toBe($camera->id);
    $this->getJson('/api/v1/incidencias/'.$incident->id)->assertOk();
    $this->postJson('/api/v1/incidencias', [
        'camera_id' => $camera->id,
        'description' => 'Esta incidencia debe rechazarse.',
    ])->assertUnprocessable()->assertJsonValidationErrors('camera_id');
});

function apiCameraData(): array
{
    return [
        'codigo_inventario' => 'CAM-API-001',
        'numero_serie' => 'API-SN-001',
        'marca' => 'Hikvision',
        'modelo' => 'DS-2CD',
        'tipo' => 'IP',
        'direccion_ip' => '192.168.10.50',
        'ubicacion' => 'Bloque 1',
        'estado' => 'activo',
        'observaciones' => 'Prueba de API',
    ];
}

function apiUserWithRole(string $code): User
{
    $role = Role::create([
        'name' => ucfirst($code),
        'code' => $code,
    ]);

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('visitante no puede consultar cámaras mediante API', function () {
    $this->getJson('/api/cameras')
        ->assertUnauthorized();
});

test('administrador puede consultar cámaras mediante API', function () {
    Sanctum::actingAs(apiUserWithRole('administrador'));

    $this->getJson('/api/cameras')
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'total',
        ]);
});

test('administrador puede registrar una cámara mediante API', function () {
    Sanctum::actingAs(apiUserWithRole('administrador'));

    $this->postJson('/api/cameras', apiCameraData())
        ->assertCreated()
        ->assertJsonPath('data.numero_serie', 'API-SN-001');

    $this->assertDatabaseHas('cameras', [
        'numero_serie' => 'API-SN-001',
    ]);
});

test('técnico no puede registrar cámaras mediante API', function () {
    Sanctum::actingAs(apiUserWithRole('tecnico'));

    $this->postJson('/api/cameras', apiCameraData())
        ->assertForbidden();

    expect(Camera::count())->toBe(0);
});

test('API rechaza números de serie duplicados', function () {
    Sanctum::actingAs(apiUserWithRole('administrador'));

    $this->postJson('/api/cameras', apiCameraData())
        ->assertCreated();

    $this->postJson('/api/cameras', apiCameraData())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['numero_serie']);

    expect(Camera::count())->toBe(1);
});

test('API rechaza direcciones IP inválidas', function () {
    Sanctum::actingAs(apiUserWithRole('administrador'));

    $data = apiCameraData();
    $data['direccion_ip'] = '999.999.999.999';

    $this->postJson('/api/cameras', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['direccion_ip']);

    expect(Camera::count())->toBe(0);
});

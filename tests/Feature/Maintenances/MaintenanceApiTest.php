<?php

use App\Models\Camera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Crea una cámara para las pruebas de la API.
 */
function hu09ApiCamera(): Camera
{
    return Camera::create([
        'codigo_inventario' => 'CAM-HU09-API',
        'numero_serie' => 'SN-HU09-API',
        'marca' => 'Hikvision',
        'modelo' => 'DS-2CD',
        'tipo' => 'IP',
        'ubicacion' => 'Ciudadela Pampalinda',
        'estado' => 'activo',
    ]);
}

/**
 * Crea un usuario con un rol institucional.
 */
function hu09ApiUser(string $roleCode): User
{
    $role = Role::create([
        'name' => ucfirst($roleCode),
        'code' => $roleCode,
    ]);

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

/**
 * Genera los datos de un mantenimiento válido.
 */
function hu09ApiData(int $cameraId): array
{
    return [
        'camera_id' => $cameraId,
        'maintenance_date' => '2026-11-17',
        'type' => 'preventivo',
        'description' => 'Limpieza de lente y revisión de conexiones',
        'responsible' => 'Técnico USC',
        'result' => 'Cámara operativa',
    ];
}

/**
 * Un visitante no puede registrar mantenimientos.
 */
test('HU-09 API rechaza usuarios no autenticados', function () {
    $camera = hu09ApiCamera();

    $this->postJson(
        '/api/v1/mantenimientos',
        hu09ApiData($camera->id)
    )->assertUnauthorized();

    $this->assertDatabaseCount('maintenances', 0);
});

/**
 * Un auxiliar autenticado no tiene permiso de registro.
 */
test('HU-09 API rechaza registros realizados por auxiliares', function () {
    $camera = hu09ApiCamera();

    Sanctum::actingAs(hu09ApiUser('auxiliar'));

    $this->postJson(
        '/api/v1/mantenimientos',
        hu09ApiData($camera->id)
    )->assertForbidden();

    $this->assertDatabaseCount('maintenances', 0);
});

/**
 * Un técnico autorizado puede registrar una intervención.
 */
test('HU-09 API permite registrar mantenimientos a tecnicos', function () {
    $camera = hu09ApiCamera();

    Sanctum::actingAs(hu09ApiUser('tecnico'));

    $this->postJson(
        '/api/v1/mantenimientos',
        hu09ApiData($camera->id)
    )
        ->assertCreated()
        ->assertJsonPath('data.camera_id', $camera->id)
        ->assertJsonPath('data.type', 'preventivo');

    $this->assertDatabaseHas('maintenances', [
        'camera_id' => $camera->id,
        'type' => 'preventivo',
        'result' => 'Cámara operativa',
    ]);
});

/**
 * La API rechaza referencias a cámaras inexistentes.
 */
test('HU-09 API rechaza una camara inexistente', function () {
    Sanctum::actingAs(hu09ApiUser('administrador'));

    $this->postJson(
        '/api/v1/mantenimientos',
        hu09ApiData(999999)
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('camera_id');

    $this->assertDatabaseCount('maintenances', 0);
});

/**
 * La API rechaza datos obligatorios incompletos.
 */
test('HU-09 API valida los campos obligatorios', function () {
    $camera = hu09ApiCamera();

    Sanctum::actingAs(hu09ApiUser('administrador'));

    $data = hu09ApiData($camera->id);
    $data['description'] = '';

    $this->postJson('/api/v1/mantenimientos', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');

    $this->assertDatabaseCount('maintenances', 0);
});

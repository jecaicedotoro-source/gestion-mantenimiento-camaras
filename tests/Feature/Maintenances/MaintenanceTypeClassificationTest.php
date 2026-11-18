<?php

use App\Livewire\Maintenances\Create;
use App\Models\Camera;
use App\Models\Maintenance;
use App\Models\Role;
use App\Models\User;
use App\Services\MaintenanceRegistrationService;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

function hu11Camera(): Camera
{
    return Camera::create([
        'codigo_inventario' => 'CAM-HU11',
        'marca' => 'Marca de prueba',
        'modelo' => 'Modelo de prueba',
        'tipo' => 'IP',
        'ubicacion' => 'Laboratorio de pruebas',
        'estado' => 'activo',
    ]);
}

function hu11Data(Camera $camera, string $type = 'preventivo'): array
{
    return [
        'camera_id' => $camera->id,
        'maintenance_date' => '2026-11-18',
        'type' => $type,
        'description' => 'Intervención técnica HU-11',
        'responsible' => 'Personal técnico de prueba',
        'result' => 'Resultado documentado',
    ];
}

function hu11Technician(): User
{
    $role = Role::firstOrCreate(
        ['code' => 'tecnico'],
        ['name' => 'Técnico']
    );

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

test('HU-11 CA-11.1 y CA-11.2: servicio persiste ambos tipos permitidos', function (string $type) {
    $camera = hu11Camera();

    $maintenance = app(MaintenanceRegistrationService::class)
        ->register(hu11Data($camera, $type));

    expect($maintenance->exists)->toBeTrue()
        ->and($maintenance->type)->toBe($type)
        ->and($maintenance->camera_id)->toBe($camera->id);

    $this->assertDatabaseHas('maintenances', [
        'id' => $maintenance->id,
        'camera_id' => $camera->id,
        'type' => $type,
    ]);
    $this->assertDatabaseCount('maintenances', 1);
})->with(['preventivo', 'correctivo']);

test('HU-11 CA-11.3: servicio rechaza tipos inválidos y campos de tipo ausentes', function (?string $type) {
    $camera = hu11Camera();
    $data = hu11Data($camera);

    if ($type === null) {
        unset($data['type']);
    } else {
        $data['type'] = $type;
    }

    expect(fn () => app(MaintenanceRegistrationService::class)->register($data))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('maintenances', 0);
})->with([
    'tipo no admitido' => 'predictivo',
    'tipo vacío' => '',
    'tipo omitido' => null,
]);

test('HU-11 Livewire muestra ambos tipos y registra un mantenimiento correctivo', function () {
    $camera = hu11Camera();
    $this->actingAs(hu11Technician());

    Livewire::test(Create::class)
        ->assertSee('Preventivo')
        ->assertSee('Correctivo')
        ->set('cameraId', (string) $camera->id)
        ->set('maintenanceDate', '2026-11-18')
        ->set('type', 'correctivo')
        ->set('description', 'Restauración del enlace de video')
        ->set('responsible', 'Técnico autorizado')
        ->set('result', 'Enlace restablecido')
        ->call('register')
        ->assertHasNoErrors()
        ->assertSee('Mantenimiento registrado correctamente.');

    $this->assertDatabaseHas('maintenances', [
        'camera_id' => $camera->id,
        'type' => 'correctivo',
        'description' => 'Restauración del enlace de video',
        'result' => 'Enlace restablecido',
    ]);
    $this->assertDatabaseCount('maintenances', 1);
});

test('HU-11 API acepta y devuelve un mantenimiento correctivo', function () {
    $camera = hu11Camera();
    Sanctum::actingAs(hu11Technician());

    $this->postJson('/api/v1/mantenimientos', hu11Data($camera, 'correctivo'))
        ->assertCreated()
        ->assertJsonPath('data.camera_id', $camera->id)
        ->assertJsonPath('data.type', 'correctivo');

    $this->assertDatabaseHas('maintenances', [
        'camera_id' => $camera->id,
        'type' => 'correctivo',
    ]);
    $this->assertDatabaseCount('maintenances', 1);
});

test('HU-11 API rechaza tipos inválidos sin insertar registros', function (string $type) {
    $camera = hu11Camera();
    Sanctum::actingAs(hu11Technician());

    $this->postJson('/api/v1/mantenimientos', hu11Data($camera, $type))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');

    $this->assertDatabaseCount('maintenances', 0);
})->with(['predictivo', 'CORRECTIVO']);

test('HU-11 historial muestra el tipo real de ambos mantenimientos de una cámara', function () {
    $camera = hu11Camera();
    $service = app(MaintenanceRegistrationService::class);

    $service->register(hu11Data($camera, 'preventivo'));
    $service->register(hu11Data($camera, 'correctivo'));

    $this->actingAs(hu11Technician());

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('Preventivo')
        ->assertSee('Correctivo')
        ->assertSee('Resultado documentado');

    expect(Maintenance::where('camera_id', $camera->id)->count())->toBe(2);
});

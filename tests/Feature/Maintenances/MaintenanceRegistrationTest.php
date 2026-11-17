<?php

use App\Models\Camera;
use App\Services\MaintenanceRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Crea una cámara válida para las pruebas de mantenimiento.
 */
function hu09CreateCamera(string $suffix = '001'): Camera
{
    return Camera::create([
        'codigo_inventario' => 'CAM-HU09-' . $suffix,
        'numero_serie' => 'SN-HU09-' . $suffix,
        'marca' => 'Hikvision',
        'modelo' => 'DS-2CD',
        'tipo' => 'IP',
        'ubicacion' => 'Bloque 1 - Entrada principal',
        'estado' => 'activo',
    ]);
}

/**
 * Datos válidos para registrar un mantenimiento.
 */
function hu09ValidMaintenanceData(int $cameraId): array
{
    return [
        'camera_id' => $cameraId,
        'maintenance_date' => '2026-11-17',
        'type' => 'preventivo',
        'description' => 'Revisión y limpieza de cámara',
        'responsible' => 'Técnico USC',
        'result' => 'Cámara operativa',
    ];
}

/**
 * CA-09.1: Registro exitoso de un mantenimiento.
 */
test('HU-09 registra un mantenimiento asociado a una cámara existente', function () {
    $camera = hu09CreateCamera('001');

    $data = hu09ValidMaintenanceData($camera->id);

    $maintenance = app(MaintenanceRegistrationService::class)
        ->register($data);

    expect($maintenance->exists)->toBeTrue()
        ->and($maintenance->camera_id)->toBe($camera->id);

    $this->assertDatabaseHas('maintenances', [
        'camera_id' => $camera->id,
        'type' => 'preventivo',
        'result' => 'Cámara operativa',
    ]);
});

/**
 * CA-09.2: Rechazo de una cámara inexistente.
 */
test('HU-09 rechaza un mantenimiento para una cámara inexistente', function () {
    $data = hu09ValidMaintenanceData(999999);

    expect(
        fn () => app(MaintenanceRegistrationService::class)
            ->register($data)
    )->toThrow(ValidationException::class);

    $this->assertDatabaseCount('maintenances', 0);
});

/**
 * CA-09.3: Rechazo de datos obligatorios incompletos.
 */
test('HU-09 rechaza datos obligatorios incompletos', function () {
    $camera = hu09CreateCamera('INCOMPLETA');

    $data = hu09ValidMaintenanceData($camera->id);

    $data['description'] = '   ';

    unset(
        $data['responsible'],
        $data['result']
    );

    expect(
        fn () => app(MaintenanceRegistrationService::class)
            ->register($data)
    )->toThrow(ValidationException::class);

    $this->assertDatabaseCount('maintenances', 0);
});

/**
 * Validación del tipo de mantenimiento.
 */
test('HU-09 rechaza tipos de mantenimiento inválidos', function () {
    $camera = hu09CreateCamera('TIPO');

    $data = hu09ValidMaintenanceData($camera->id);

    $data['type'] = 'desconocido';

    expect(
        fn () => app(MaintenanceRegistrationService::class)
            ->register($data)
    )->toThrow(ValidationException::class);

    $this->assertDatabaseCount('maintenances', 0);
});

/**
 * Protección de cámaras dadas de baja.
 */
test('HU-09 no permite mantenimientos sobre cámaras dadas de baja', function () {
    $camera = hu09CreateCamera('BAJA');

    // Baja lógica mediante SoftDeletes.
    $camera->delete();

    $data = hu09ValidMaintenanceData($camera->id);

    expect(
        fn () => app(MaintenanceRegistrationService::class)
            ->register($data)
    )->toThrow(ValidationException::class);

    $this->assertDatabaseCount('maintenances', 0);
});

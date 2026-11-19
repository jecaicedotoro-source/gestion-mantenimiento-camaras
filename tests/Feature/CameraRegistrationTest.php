<?php

use App\Models\Camera;
use App\Services\CameraRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function validCameraData(): array
{
    return [
        'codigo_inventario' => 'CAM-USC-001',
        'numero_serie' => 'SN-001',
        'marca' => 'Hikvision',
        'modelo' => 'DS-2CD',
        'tipo' => 'IP',
        'direccion_ip' => '192.168.1.100',
        'ubicacion' => 'Bloque 1 - Entrada principal',
        'estado' => 'activo',
        'observaciones' => 'Cámara de prueba',
    ];
}

test('registra una cámara con datos válidos', function () {
    $camera = app(CameraRegistrationService::class)
        ->register(validCameraData());

    expect($camera)->toBeInstanceOf(Camera::class)
        ->and($camera->exists)->toBeTrue();

    $this->assertDatabaseHas('cameras', [
        'codigo_inventario' => 'CAM-USC-001',
        'marca' => 'Hikvision',
        'ubicacion' => 'Bloque 1 - Entrada principal',
    ]);
});

test('permite cámaras con el mismo código de inventario', function () {
    $service = app(CameraRegistrationService::class);

    $service->register(validCameraData());

    $data = validCameraData();
    $data['numero_serie'] = 'SN-002';

    $service->register($data);

    expect(Camera::where(
        'codigo_inventario',
        'CAM-USC-001'
    )->count())->toBe(2);
});

test('rechaza números de serie duplicados', function () {
    $service = app(CameraRegistrationService::class);

    $service->register(validCameraData());

    $data = validCameraData();
    $data['codigo_inventario'] = 'CAM-USC-002';

    expect(fn () => $service->register($data))
        ->toThrow(ValidationException::class);

    expect(Camera::count())->toBe(1);
});

test('rechaza direcciones IP inválidas', function () {
    $data = validCameraData();
    $data['direccion_ip'] = '999.999.999.999';

    expect(fn () => app(CameraRegistrationService::class)
        ->register($data))
        ->toThrow(ValidationException::class);

    expect(Camera::count())->toBe(0);
});

test('rechaza campos obligatorios vacíos', function () {
    $data = validCameraData();
    $data['marca'] = '';

    expect(fn () => app(CameraRegistrationService::class)
        ->register($data))
        ->toThrow(ValidationException::class);

    expect(Camera::count())->toBe(0);
});

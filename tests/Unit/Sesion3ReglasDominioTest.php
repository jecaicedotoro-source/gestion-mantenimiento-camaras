<?php

use App\Models\User;
use App\Services\IncidentTextMinimizer;
use App\Support\CameraValidationRules;
use Illuminate\Validation\Rules\Unique;

/*
|--------------------------------------------------------------------------
| S3 - Pruebas unitarias de reglas de dominio
|--------------------------------------------------------------------------
| No utilizan base de datos, red ni servicios externos.
| Tres funciones reales del sistema USC.
|--------------------------------------------------------------------------
*/

// FUNCION 1: User::initials()

test('S3 U01 genera iniciales de nombre y apellido', function () {
    $user = new User(['name' => 'Ana Lopez']);

    expect($user->initials())->toBe('AL');
});

test('S3 U02 utiliza primera y ultima inicial de un nombre compuesto', function () {
    $user = new User(['name' => 'Juan Carlos Perez']);

    expect($user->initials())->toBe('JP');
});

test('S3 U03 admite nombres de una sola palabra', function () {
    $user = new User(['name' => 'Carlos']);

    expect($user->initials())->toBe('C');
});

// FUNCION 2: CameraValidationRules::rules()

test('S3 U04 exige los campos principales del inventario', function () {
    $rules = CameraValidationRules::rules();

    foreach ([
        'codigo_inventario',
        'marca',
        'modelo',
        'tipo',
        'ubicacion',
        'estado',
    ] as $field) {
        expect($rules[$field])->toContain('required');
    }
});

test('S3 U05 permite codigos de inventario repetidos', function () {
    $rules = CameraValidationRules::rules();

    $uniqueRules = array_filter(
        $rules['codigo_inventario'],
        fn ($rule): bool => $rule instanceof Unique
    );

    expect($uniqueRules)->toBeEmpty()
        ->and($rules['codigo_inventario'])->toContain('max:50');
});

test('S3 U06 permite series nulas pero exige unicidad si existen', function () {
    $rules = CameraValidationRules::rules();

    $uniqueRules = array_filter(
        $rules['numero_serie'],
        fn ($rule): bool => $rule instanceof Unique
    );

    expect($rules['numero_serie'])->toContain('nullable')
        ->and($uniqueRules)->toHaveCount(1);
});

// FUNCION 3: IncidentTextMinimizer::minimize()

test('S3 U07 elimina espacios externos de la descripcion', function () {
    $minimizer = new IncidentTextMinimizer;

    $result = $minimizer->minimize('  Camara sin imagen  ');

    expect($result)->toBe('Camara sin imagen');
});

test('S3 U08 oculta ubicaciones privadas conocidas', function () {
    $minimizer = new IncidentTextMinimizer;

    $result = $minimizer->minimize(
        'La camara de sector norte no responde',
        ['sector norte']
    );

    expect($result)
        ->toBe('La camara de [dato omitido] no responde');
});

test('S3 U09 elimina direcciones IP correos y enlaces', function () {
    $minimizer = new IncidentTextMinimizer;

    $result = $minimizer->minimize(
        'Aviso desde 192.0.2.10 por correo operador@example.com y https://ejemplo.test/estado'
    );

    expect($result)
        ->not->toContain('192.0.2.10')
        ->not->toContain('operador@example.com')
        ->not->toContain('https://ejemplo.test/estado')
        ->toContain('[dato omitido]');
});

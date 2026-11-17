<?php

use App\Livewire\Maintenances\Create;
use App\Models\Camera;
use App\Models\Maintenance;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Datos sintéticos exclusivos de pruebas
|--------------------------------------------------------------------------
|
| Pest utiliza RefreshDatabase con SQLite en memoria.
| No se modifican los datos del inventario institucional.
|
*/

function maintenanceWebActor(string $roleCode): User
{
    $role = Role::firstOrCreate(
        ['code' => $roleCode],
        ['name' => ucfirst($roleCode)]
    );

    $user = User::factory()->create();

    $user->role_id = $role->id;
    $user->save();

    return $user;
}

function maintenanceWebCamera(
    string $code = 'CAM-QA-001'
): Camera {
    return Camera::create([
        'codigo_inventario' => $code,
        'numero_serie' => null,
        'marca' => 'Marca de prueba',
        'modelo' => 'Modelo QA',
        'tipo' => 'IP',
        'direccion_ip' => null,
        'ubicacion' => 'Laboratorio QA',
        'estado' => 'activo',
    ]);
}

function maintenanceWebPayload(Camera $camera): array
{
    return [
        'cameraId' => (string) $camera->id,
        'maintenanceDate' => '2026-11-17',
        'type' => 'preventivo',
        'description' => 'Limpieza y revisión técnica.',
        'responsible' => 'Técnico de prueba',
        'result' => 'Cámara operativa.',
    ];
}

/*
|--------------------------------------------------------------------------
| Acceso y autorización
|--------------------------------------------------------------------------
*/

test('HU-09 visitante debe autenticarse para abrir mantenimientos', function () {
    $this->get(route('maintenances.create'))
        ->assertRedirect(route('login'));
});

test('HU-09 roles autorizados pueden abrir el formulario', function (
    string $roleCode
) {
    $this->actingAs(maintenanceWebActor($roleCode));

    $this->get(route('maintenances.create'))
        ->assertOk()
        ->assertSee('Registro de mantenimiento');
})->with([
    'administrador',
    'coordinador',
    'tecnico',
]);

test('HU-09 auxiliar no puede abrir el formulario ni ejecutar Livewire', function () {
    $this->actingAs(maintenanceWebActor('auxiliar'));

    $this->get(route('maintenances.create'))
        ->assertForbidden();

    Livewire::test(Create::class)
        ->assertForbidden();

    expect(Maintenance::count())->toBe(0);
});

test('HU-09 usuario sin rol no puede abrir el formulario', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('maintenances.create'))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Integración con inventario
|--------------------------------------------------------------------------
*/

test('HU-09 formulario presenta cámaras existentes sin duplicarlas', function () {
    $camera = maintenanceWebCamera('CAM-DISPONIBLE-QA');
    $retired = maintenanceWebCamera('CAM-RETIRADA-QA');

    $retired->delete();

    $this->actingAs(maintenanceWebActor('tecnico'));

    Livewire::test(Create::class)
        ->assertSee($camera->codigo_inventario)
        ->assertDontSee($retired->codigo_inventario);

    expect(Camera::withTrashed()->count())->toBe(2)
        ->and(Camera::count())->toBe(1)
        ->and(Maintenance::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Registro y persistencia
|--------------------------------------------------------------------------
*/

test('HU-09 Livewire guarda mantenimiento asociado a cámara existente', function () {
    $camera = maintenanceWebCamera();

    $this->actingAs(maintenanceWebActor('tecnico'));

    $component = Livewire::test(Create::class);

    foreach (maintenanceWebPayload($camera) as $field => $value) {
        $component->set($field, $value);
    }

    $component
        ->call('register')
        ->assertHasNoErrors();

    /*
     * Verificamos los campos persistidos directamente
     * en la tabla de mantenimientos.
     *
     * La fecha se comprueba mediante el cast Eloquent
     * para evitar diferencias de formato SQLite.
     */

    $this->assertDatabaseHas('maintenances', [
        'camera_id' => $camera->id,
        'type' => 'preventivo',
        'description' => 'Limpieza y revisión técnica.',
        'responsible' => 'Técnico de prueba',
        'result' => 'Cámara operativa.',
    ]);

    $maintenance = Maintenance::query()->sole();

    expect($maintenance->maintenance_date->toDateString())
        ->toBe('2026-11-17');

    expect($maintenance->camera_id)
        ->toBe($camera->id);

    expect($maintenance->camera->id)
        ->toBe($camera->id);

    expect(Maintenance::count())
        ->toBe(1);

    expect(Camera::count())
        ->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Validación de datos
|--------------------------------------------------------------------------
*/

test('HU-09 Livewire rechaza campos obligatorios vacíos', function () {
    $camera = maintenanceWebCamera();

    $this->actingAs(maintenanceWebActor('tecnico'));

    Livewire::test(Create::class)
        ->set('cameraId', (string) $camera->id)
        ->set('maintenanceDate', '2026-11-17')
        ->set('type', 'preventivo')
        ->set('description', '')
        ->set('responsible', '')
        ->set('result', '')
        ->call('register')
        ->assertHasErrors([
            'description' => 'required',
            'responsible' => 'required',
            'result' => 'required',
        ]);

    expect(Maintenance::count())->toBe(0);
});

test('HU-09 Livewire rechaza cámaras inexistentes', function () {
    $this->actingAs(maintenanceWebActor('tecnico'));

    Livewire::test(Create::class)
        ->set('cameraId', '999999')
        ->set('maintenanceDate', '2026-11-17')
        ->set('type', 'preventivo')
        ->set('description', 'Revisión de prueba.')
        ->set('responsible', 'Técnico QA')
        ->set('result', 'Sin novedades.')
        ->call('register')
        ->assertHasErrors('cameraId');

    expect(Maintenance::count())->toBe(0);
});

test('HU-09 Livewire impide registrar mantenimientos en cámaras retiradas', function () {
    $camera = maintenanceWebCamera();
    $camera->delete();

    $this->actingAs(maintenanceWebActor('tecnico'));

    $component = Livewire::test(Create::class);

    foreach (maintenanceWebPayload($camera) as $field => $value) {
        $component->set($field, $value);
    }

    $component
        ->call('register')
        ->assertHasErrors('cameraId');

    expect(Maintenance::count())->toBe(0);
});

test('HU-09 Livewire rechaza tipos de mantenimiento inválidos', function () {
    $camera = maintenanceWebCamera();

    $this->actingAs(maintenanceWebActor('tecnico'));

    $component = Livewire::test(Create::class);

    foreach (maintenanceWebPayload($camera) as $field => $value) {
        $component->set($field, $value);
    }

    $component
        ->set('type', 'desconocido')
        ->call('register')
        ->assertHasErrors('type');

    expect(Maintenance::count())->toBe(0);
});

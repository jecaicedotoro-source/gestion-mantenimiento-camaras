<?php

use App\Livewire\Maintenances\History;
use App\Models\Camera;
use App\Models\Maintenance;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function hu10Actor(string $roleCode = 'tecnico', bool $active = true): User
{
    $role = Role::firstOrCreate(
        ['code' => $roleCode],
        ['name' => ucfirst($roleCode)]
    );

    return User::factory()->create(['role_id' => $role->id, 'is_active' => $active]);
}

function hu10Camera(string $code): Camera
{
    return Camera::create([
        'codigo_inventario' => $code,
        'marca' => 'Marca de prueba',
        'modelo' => 'Modelo HU10',
        'tipo' => 'IP',
        'ubicacion' => 'Área de prueba',
        'estado' => 'activo',
    ]);
}

function hu10Maintenance(Camera $camera, string $description, string $date = '2026-11-18', string $type = 'preventivo'): Maintenance
{
    return Maintenance::create([
        'camera_id' => $camera->id,
        'maintenance_date' => $date,
        'type' => $type,
        'description' => $description,
        'responsible' => 'Técnico de prueba',
        'result' => 'Resultado registrado',
    ]);
}

test('HU-10 exige autenticación para consultar el historial', function () {
    $camera = hu10Camera('CAM-HU10-VISITANTE');

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertRedirect(route('login'));
});

test('HU-10 permite consultar a los cuatro roles activos', function (string $role) {
    $camera = hu10Camera('CAM-HU10-ROL');
    hu10Maintenance($camera, 'Intervención visible para usuario autorizado');

    $this->actingAs(hu10Actor($role));

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('Historial de mantenimientos')
        ->assertSee('Intervención visible para usuario autorizado');
})->with(['administrador', 'coordinador', 'tecnico', 'auxiliar']);

test('HU-10 bloquea cuentas sin rol e inactivas, también en Livewire', function () {
    $camera = hu10Camera('CAM-HU10-PRIVADA');

    $this->actingAs(User::factory()->create(['role_id' => null]));

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertForbidden();

    Livewire::test(History::class, ['camera' => $camera->id])
        ->assertForbidden();

    $this->actingAs(hu10Actor('coordinador', false));

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertForbidden();
});

test('HU-10 aísla los registros por cámara y ordena del más reciente al más antiguo', function () {
    $camera = hu10Camera('CAM-HU10-PRINCIPAL');
    $other = hu10Camera('CAM-HU10-OTRA');

    hu10Maintenance($camera, 'Intervención antigua visible', '2026-10-01', 'preventivo');
    hu10Maintenance($camera, 'Intervención reciente visible', '2026-11-18', 'correctivo');
    hu10Maintenance($other, 'Intervención de cámara ajena', '2026-11-18');

    $this->actingAs(hu10Actor());

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSeeInOrder(['Intervención reciente visible', 'Intervención antigua visible'])
        ->assertDontSee('Intervención de cámara ajena')
        ->assertSee('Correctivo')
        ->assertSee('Preventivo')
        ->assertSee('Resultado registrado');

    expect(Maintenance::count())->toBe(3);
});

test('HU-10 muestra aviso cuando la cámara no tiene mantenimientos', function () {
    $camera = hu10Camera('CAM-HU10-VACIA');
    $this->actingAs(hu10Actor('auxiliar'));

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('todavía no tiene mantenimientos registrados');
});

test('HU-10 conserva la consulta histórica de una cámara dada de baja', function () {
    $camera = hu10Camera('CAM-HU10-RETIRADA');
    hu10Maintenance($camera, 'Mantenimiento histórico conservado');
    $camera->delete();

    $this->actingAs(hu10Actor('coordinador'));

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('está dada de baja')
        ->assertSee('Mantenimiento histórico conservado');
});

test('HU-10 rechaza una cámara inexistente', function () {
    $this->actingAs(hu10Actor());

    $this->get(route('maintenances.history', ['camera' => 999999]))
        ->assertNotFound();
});

test('HU-10 pagina el historial sin mezclar otras cámaras', function () {
    $camera = hu10Camera('CAM-HU10-PAGINADA');
    $other = hu10Camera('CAM-HU10-AJENA');

    for ($i = 1; $i <= 11; $i++) {
        hu10Maintenance($camera, 'Mantenimiento HU10 '.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
    }
    hu10Maintenance($other, 'Mantenimiento otra cámara');

    $this->actingAs(hu10Actor());

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('Mantenimiento HU10 11')
        ->assertDontSee('Mantenimiento HU10 01')
        ->assertDontSee('Mantenimiento otra cámara');

    $this->get(route('maintenances.history', ['camera' => $camera->id]).'?page=2')
        ->assertOk()
        ->assertSee('Mantenimiento HU10 01')
        ->assertDontSee('Mantenimiento HU10 11');
});

test('HU-10 enlaza el historial desde el inventario y no modifica registros', function () {
    $camera = hu10Camera('CAM-HU10-ENLACE');
    $maintenance = hu10Maintenance($camera, 'Consulta de solo lectura');

    $this->actingAs(hu10Actor('auxiliar'));

    $this->get(route('cameras.index'))
        ->assertOk()
        ->assertSee(route('maintenances.history', ['camera' => $camera->id]), false);

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('Consulta de solo lectura');

    $this->assertDatabaseHas('maintenances', [
        'id' => $maintenance->id,
        'camera_id' => $camera->id,
        'description' => 'Consulta de solo lectura',
    ]);
    expect(Maintenance::count())->toBe(1);
});

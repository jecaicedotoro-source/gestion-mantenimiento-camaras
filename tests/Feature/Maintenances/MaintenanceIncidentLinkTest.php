<?php

use App\Livewire\Maintenances\Create;
use App\Models\Camera;
use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use App\Services\MaintenanceRegistrationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

function hu12Actor(string $code = 'tecnico', bool $active = true): User
{
    $role = Role::firstOrCreate(['code' => $code], ['name' => ucfirst($code)]);

    return User::factory()->create(['role_id' => $role->id, 'is_active' => $active]);
}

function hu12Camera(string $code = 'CAM-HU12-001'): Camera
{
    return Camera::create([
        'codigo_inventario' => $code,
        'marca' => 'Marca de prueba',
        'modelo' => 'Modelo HU-12',
        'tipo' => 'IP',
        'ubicacion' => 'Laboratorio QA',
        'estado' => 'activo',
    ]);
}

function hu12Incident(Camera $camera, User $reporter): Incident
{
    return Incident::create([
        'camera_id' => $camera->id,
        'reported_by' => $reporter->id,
        'description' => 'Sin imagen, la cámara sigue desconectada del grabador.',
    ]);
}

function hu12Data(Camera $camera): array
{
    return [
        'camera_id' => $camera->id,
        'maintenance_date' => '2026-11-18',
        'type' => 'correctivo',
        'description' => 'Diagnóstico y reparación del enlace de video.',
        'responsible' => 'Técnico USC',
        'result' => 'Se revisó el cable de enlace.',
    ];
}

function hu12Linked(Camera $camera, Incident $incident, bool $resolved): array
{
    return hu12Data($camera) + [
        'incident_id' => $incident->id,
        'repair_resolved' => $resolved,
    ];
}

test('HU-12 los registros existentes conservan referencia nula y el estado técnico inicial', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $maintenance = app(MaintenanceRegistrationService::class)->register(hu12Data($camera));

    $this->assertDatabaseHas('maintenances', [
        'id' => $maintenance->id,
        'camera_id' => $camera->id,
        'incident_id' => null,
        'repair_resolved' => null,
    ]);
    expect($incident->fresh()->technical_status)->toBe('pendiente')
        ->and($incident->fresh()->review_status)->toBe('pendiente');
});

test('HU-12 una reparación no resuelta deja la incidencia pendiente y conserva sucesivas intervenciones', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $service = app(MaintenanceRegistrationService::class);

    $first = $service->register(hu12Linked($camera, $incident, false), $actor);
    $second = $service->register(hu12Linked($camera, $incident, false), $actor);

    expect($first->repair_resolved)->toBeFalse()
        ->and($second->repair_resolved)->toBeFalse()
        ->and($incident->fresh()->technical_status)->toBe('pendiente')
        ->and($incident->fresh()->resolved_at)->toBeNull()
        ->and($incident->fresh()->resolved_by)->toBeNull()
        ->and($incident->maintenances()->count())->toBe(2);
    $this->assertDatabaseHas('maintenances', ['id' => $first->id, 'incident_id' => $incident->id]);
    $this->assertDatabaseHas('maintenances', ['id' => $second->id, 'incident_id' => $incident->id]);
});

test('HU-12 solo la confirmación humana positiva resuelve la incidencia y no cambia su clasificación', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $incident->forceFill([
        'review_status' => 'manual',
        'confirmed_category' => 'conectividad',
        'confirmed_by' => $actor->id,
        'confirmed_at' => now(),
    ])->save();
    $service = app(MaintenanceRegistrationService::class);

    $previous = $service->register(hu12Linked($camera, $incident, false), $actor);
    $corrected = $service->register(hu12Linked($camera, $incident, true), $actor);
    $now = $incident->fresh();

    expect($corrected->repair_resolved)->toBeTrue()
        ->and($corrected->incident?->id)->toBe($incident->id)
        ->and($previous->fresh()->repair_resolved)->toBeFalse()
        ->and($now->technical_status)->toBe('corregida')
        ->and($now->resolved_by)->toBe($actor->id)
        ->and($now->resolved_at)->not->toBeNull()
        ->and($now->review_status)->toBe('manual')
        ->and($now->confirmed_category)->toBe('conectividad')
        ->and($incident->maintenances()->count())->toBe(2);
});

test('HU-12 impide registrar una intervención sobre incidencia ya corregida', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $service = app(MaintenanceRegistrationService::class);
    $service->register(hu12Linked($camera, $incident, true), $actor);

    expect(fn () => $service->register(hu12Linked($camera, $incident, false), $actor))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('maintenances', 1);
});

test('HU-12 rechaza asociación con incidencia de otra cámara y conserva los datos', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $otherCamera = hu12Camera('CAM-HU12-OTRA');
    $incident = hu12Incident($otherCamera, $actor);
    $data = hu12Linked($camera, $incident, true);

    expect(fn () => app(MaintenanceRegistrationService::class)->register($data, $actor))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('maintenances', 0);
    expect($incident->fresh()->technical_status)->toBe('pendiente');
});

test('HU-12 rechaza incidencia inexistente, asociación preventiva y resultado ambiguo', function (string $scenario) {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $data = hu12Linked($camera, $incident, true);

    if ($scenario === 'incidencia inexistente') {
        $data['incident_id'] = 999999;
    } elseif ($scenario === 'tipo preventivo') {
        $data['type'] = 'preventivo';
    } elseif ($scenario === 'sin confirmación') {
        unset($data['repair_resolved']);
    } elseif ($scenario === 'confirmación inválida') {
        $data['repair_resolved'] = 'quizás';
    }

    expect(fn () => app(MaintenanceRegistrationService::class)->register($data, $actor))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('maintenances', 0);
})->with(['incidencia inexistente', 'tipo preventivo', 'sin confirmación', 'confirmación inválida']);

test('HU-12 no admite confirmar una reparación sin incidencia', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $data = hu12Data($camera);
    $data['repair_resolved'] = true;

    expect(fn () => app(MaintenanceRegistrationService::class)->register($data, $actor))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('maintenances', 0);
});

test('HU-12 servicio exige identidad autorizada al asociar una incidencia', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $data = hu12Linked($camera, $incident, true);
    $auxiliary = hu12Actor('auxiliar');
    $inactive = hu12Actor('coordinador', false);

    foreach ([null, $auxiliary, $inactive, User::factory()->create()] as $forbidden) {
        expect(fn () => app(MaintenanceRegistrationService::class)->register($data, $forbidden))
            ->toThrow(AuthorizationException::class);
    }

    $this->assertDatabaseCount('maintenances', 0);
    expect($incident->fresh()->technical_status)->toBe('pendiente');
});

test('HU-12 API registra un correctivo vinculado sin resolver si la respuesta es negativa', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    Sanctum::actingAs($actor);

    $this->postJson('/api/v1/mantenimientos', hu12Linked($camera, $incident, false))
        ->assertCreated()
        ->assertJsonPath('data.incident_id', $incident->id)
        ->assertJsonPath('data.repair_resolved', false);

    expect($incident->fresh()->technical_status)->toBe('pendiente');
});

test('HU-12 API rechaza vinculación de otro equipo sin resolver incidente', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $otherCamera = hu12Camera('CAM-HU12-AJENA');
    $incident = hu12Incident($otherCamera, $actor);
    Sanctum::actingAs($actor);

    $this->postJson('/api/v1/mantenimientos', hu12Linked($camera, $incident, true))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('incident_id');

    $this->assertDatabaseCount('maintenances', 0);
    expect($incident->fresh()->technical_status)->toBe('pendiente');
});

test('HU-12 rol auxiliar puede ver estado pero no iniciar un correctivo', function () {
    $actor = hu12Actor('auxiliar');
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $this->actingAs($actor);

    $this->get(route('incidents.index'))
        ->assertOk()
        ->assertSee('Pendiente de corrección')
        ->assertDontSee('Registrar mantenimiento correctivo');

    $this->get(route('maintenances.create-for-incident', ['incident' => $incident->id]))
        ->assertForbidden();
});

test('HU-12 acceso a formulario contextual requiere autenticación', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);

    $this->get(route('maintenances.create-for-incident', ['incident' => $incident->id]))
        ->assertRedirect(route('login'));
});

test('HU-12 formulario contextual precarga cámara, incidencia y tipo correctivo', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $this->actingAs($actor);

    $this->get(route('incidents.index'))
        ->assertOk()
        ->assertSee(route('maintenances.create-for-incident', ['incident' => $incident->id]), false);

    $this->get(route('maintenances.create-for-incident', ['incident' => $incident->id]))
        ->assertOk()
        ->assertSee('Mantenimiento correctivo de la incidencia')
        ->assertSee('¿La intervención corrigió realmente la incidencia?');

    Livewire::test(Create::class, ['incident' => $incident->id])
        ->assertSet('incidentId', $incident->id)
        ->assertSet('cameraId', (string) $camera->id)
        ->assertSet('type', 'correctivo');
});

test('HU-12 Livewire exige decisión expresa antes de registrar el correctivo', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $this->actingAs($actor);

    Livewire::test(Create::class, ['incident' => $incident->id])
        ->set('description', 'Diagnóstico del enlace de video.')
        ->set('result', 'Cable de red revisado.')
        ->call('register')
        ->assertHasErrors('repairOutcome');

    $this->assertDatabaseCount('maintenances', 0);
    expect($incident->fresh()->technical_status)->toBe('pendiente');
});

test('HU-12 Livewire conserva incidencia pendiente tras indicar que no se resolvió', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $this->actingAs($actor);

    Livewire::test(Create::class, ['incident' => $incident->id])
        ->set('description', 'Primer intento de reparación.')
        ->set('result', 'El problema continúa.')
        ->set('repairOutcome', 'no')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('incidents.index'));

    $this->assertDatabaseHas('maintenances', [
        'camera_id' => $camera->id,
        'incident_id' => $incident->id,
        'type' => 'correctivo',
        'repair_resolved' => 0,
    ]);
    expect($incident->fresh()->technical_status)->toBe('pendiente');
});

test('HU-12 Livewire confirma la reparación y deja trazabilidad del responsable', function () {
    $actor = hu12Actor('coordinador');
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $this->actingAs($actor);

    Livewire::test(Create::class, ['incident' => $incident->id])
        ->set('description', 'Segundo diagnóstico del enlace.')
        ->set('result', 'Imagen restablecida y verificada.')
        ->set('repairOutcome', 'si')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('incidents.index'));

    $this->assertDatabaseHas('maintenances', [
        'incident_id' => $incident->id,
        'type' => 'correctivo',
        'repair_resolved' => 1,
    ]);
    expect($incident->fresh()->technical_status)->toBe('corregida')
        ->and($incident->fresh()->resolved_by)->toBe($actor->id);

    $this->get(route('incidents.index'))
        ->assertOk()
        ->assertSee('Estado técnico: Corregida')
        ->assertSee('Intervenciones correctivas asociadas: 1');
});

test('HU-12 historial de cámara identifica incidencia vinculada y resultado de cada intervención', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $service = app(MaintenanceRegistrationService::class);
    $service->register(hu12Linked($camera, $incident, false), $actor);
    $service->register(hu12Linked($camera, $incident, true), $actor);
    $this->actingAs($actor);

    $this->get(route('maintenances.history', ['camera' => $camera->id]))
        ->assertOk()
        ->assertSee('Incidencia relacionada:')
        ->assertSee('La reparación no se confirmó en esta intervención')
        ->assertSee('Reparación confirmada en esta intervención')
        ->assertSee('Sin imagen, la cámara sigue desconectada');
});

test('HU-12 no permite nuevas intervenciones sobre cámaras dadas de baja', function () {
    $actor = hu12Actor();
    $camera = hu12Camera();
    $incident = hu12Incident($camera, $actor);
    $camera->delete();
    $this->actingAs($actor);

    $this->get(route('incidents.index'))
        ->assertOk()
        ->assertDontSee('Registrar mantenimiento correctivo');

    $this->get(route('maintenances.create-for-incident', ['incident' => $incident->id]))
        ->assertStatus(409);

    expect(fn () => app(MaintenanceRegistrationService::class)->register(hu12Linked($camera, $incident, true), $actor))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('maintenances', 0);
});

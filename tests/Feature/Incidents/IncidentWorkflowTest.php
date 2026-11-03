<?php

use App\Contracts\IClasificadorIncidencias;
use App\Data\IncidentSuggestion;
use App\Enums\IncidentCategory;
use App\Exceptions\ClassificationUnavailable;
use App\Livewire\Incidents\Index;
use App\Models\Camera;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\Role;
use App\Models\User;
use App\Services\IncidentManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Fakes\FakeIncidentClassifier;

beforeEach(function () {
    Http::preventStrayRequests();
    app()->instance(IClasificadorIncidencias::class, new FakeIncidentClassifier);
});

function incidentActor(string $role = 'tecnico'): User
{
    $roleModel = Role::firstOrCreate(['code' => $role], ['name' => ucfirst($role)]);

    return User::factory()->create(['role_id' => $roleModel->id]);
}

function incidentCamera(): Camera
{
    return Camera::create([
        'codigo_inventario' => 'CAM-PRIVATE-991',
        'numero_serie' => 'SERIAL-PRIVATE-123',
        'marca' => 'Marca de prueba',
        'modelo' => 'Modelo de prueba',
        'tipo' => 'IP',
        'direccion_ip' => '192.168.1.20',
        'ubicacion' => 'Edificio Reservado',
        'estado' => 'activo',
    ]);
}

function workflowIncident(User $actor, ?Camera $camera = null): Incident
{
    return app(IncidentManagementService::class)->register($actor, ($camera ?? incidentCamera())->id, 'La cámara pierde la conexión repetidamente.');
}

function workflowSuggestion(User $actor, Incident $incident): IncidentClassification
{
    $service = app(IncidentManagementService::class);

    return $service->suggest($actor, $incident, hash('sha256', $service->preview($actor, $incident)));
}

test('registra incidencia y conserva sugerencia separada de decisión humana', function () {
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    $suggestion = workflowSuggestion($actor, $incident);

    expect($incident->fresh()->confirmed_category)->toBeNull()
        ->and($incident->fresh()->confirmed_by)->toBeNull()
        ->and($suggestion->suggested_category)->toBe('conectividad')
        ->and($suggestion->status)->toBe('sugerida');
    $this->assertDatabaseHas('incident_classifications', ['id' => $suggestion->id, 'input_tokens' => 20, 'output_tokens' => 15]);

    app(IncidentManagementService::class)->confirm($actor, $incident, 'conectividad', $suggestion->id);

    expect($incident->fresh()->review_status)->toBe('aceptada')
        ->and($incident->fresh()->confirmed_by)->toBe($actor->id)
        ->and($incident->fresh()->confirmed_at)->not->toBeNull();
    $this->assertDatabaseHas('incident_classifications', ['kind' => 'humana', 'suggestion_id' => $suggestion->id, 'status' => 'aceptada']);
    Http::assertNothingSent();
});

test('la corrección humana conserva la categoría propuesta y la confirmada', function () {
    $actor = incidentActor('administrador');
    $incident = workflowIncident($actor);
    $suggestion = workflowSuggestion($actor, $incident);
    app(IncidentManagementService::class)->confirm($actor, $incident, IncidentCategory::Power->value, $suggestion->id);

    $this->assertDatabaseHas('incident_classifications', [
        'kind' => 'humana', 'status' => 'corregida', 'suggested_category' => 'conectividad', 'confirmed_category' => 'alimentacion',
    ]);
    expect($incident->fresh()->confirmed_category)->toBe('alimentacion');
});

test('la clasificación manual funciona aun cuando el proveedor falla', function () {
    app()->instance(IClasificadorIncidencias::class, new class implements IClasificadorIncidencias
    {
        public function classify(string $minimizedDescription): IncidentSuggestion
        {
            throw new ClassificationUnavailable('http_error');
        }
    });
    $actor = incidentActor();
    $incident = workflowIncident($actor);

    expect(fn () => workflowSuggestion($actor, $incident))->toThrow(ClassificationUnavailable::class);
    app(IncidentManagementService::class)->confirm($actor, $incident, 'hardware');

    expect($incident->fresh()->review_status)->toBe('manual');
    $this->assertDatabaseHas('incident_classifications', ['incident_id' => $incident->id, 'status' => 'fallida', 'failure_code' => 'http_error']);
    $this->assertDatabaseHas('incident_classifications', ['incident_id' => $incident->id, 'kind' => 'humana', 'confirmed_category' => 'hardware']);
});

test('no permite confirmar una sugerencia de otra incidencia ni una propuesta anterior', function () {
    $actor = incidentActor();
    $camera = incidentCamera();
    $first = workflowIncident($actor, $camera);
    $second = workflowIncident($actor, $camera);
    $old = workflowSuggestion($actor, $first);
    $latest = workflowSuggestion($actor, $first);
    $service = app(IncidentManagementService::class);

    expect(fn () => $service->confirm($actor, $second, 'conectividad', $latest->id))->toThrow(ValidationException::class)
        ->and(fn () => $service->confirm($actor, $first, 'conectividad', $old->id))->toThrow(ValidationException::class);
    expect($first->fresh()->confirmed_at)->toBeNull()->and($second->fresh()->confirmed_at)->toBeNull();
});

test('impide sobreescribir una decisión humana y mantiene el historial', function () {
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    $service = app(IncidentManagementService::class);
    $service->confirm($actor, $incident, 'imagen');

    expect(fn () => $service->confirm($actor, $incident, 'hardware'))->toThrow(ValidationException::class)
        ->and(fn () => workflowSuggestion($actor, $incident))->toThrow(ValidationException::class)
        ->and($incident->classifications()->count())->toBe(1)
        ->and($incident->fresh()->confirmed_category)->toBe('imagen');
});

test('verifica roles y cuentas desactivadas en cada operación del servicio', function () {
    $technician = incidentActor();
    $incident = workflowIncident($technician);
    $auxiliary = incidentActor('auxiliar');
    $inactive = incidentActor('administrador');
    $inactive->forceFill(['is_active' => false])->save();
    $service = app(IncidentManagementService::class);

    foreach ([$auxiliary, $inactive, User::factory()->create()] as $forbidden) {
        expect(fn () => $service->register($forbidden, $incident->camera_id, 'Una falla de prueba'))->toThrow(AuthorizationException::class)
            ->and(fn () => $service->suggest($forbidden, $incident, 'no'))->toThrow(AuthorizationException::class)
            ->and(fn () => $service->confirm($forbidden, $incident, 'hardware'))->toThrow(AuthorizationException::class);
    }
    expect($incident->classifications()->count())->toBe(0);
});

test('rechaza categoría inválida y descripción corta sin persistir decisiones', function () {
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    $service = app(IncidentManagementService::class);

    expect(fn () => $service->confirm($actor, $incident, 'categoria-inventada'))->toThrow(ValidationException::class)
        ->and(fn () => $service->register($actor, $incident->camera_id, 'corta'))->toThrow(ValidationException::class);
    expect($incident->classifications()->count())->toBe(0)->and(Incident::count())->toBe(1);
});

test('limita solicitudes por usuario y por incidencia', function () {
    config(['ai.requests_per_minute' => 1, 'ai.attempts_per_incident' => 1]);
    $actor = incidentActor();
    $camera = incidentCamera();
    $first = workflowIncident($actor, $camera);
    $second = workflowIncident($actor, $camera);
    workflowSuggestion($actor, $first);

    expect(fn () => workflowSuggestion($actor, $first))->toThrow(ValidationException::class)
        ->and(fn () => workflowSuggestion($actor, $second))->toThrow(ValidationException::class);
    expect($first->fresh()->classification_attempts)->toBe(1)
        ->and($second->fresh()->classification_attempts)->toBe(0);
});

test('una configuración ausente no agota el cupo permanente de la incidencia', function () {
    app()->instance(IClasificadorIncidencias::class, new class implements IClasificadorIncidencias
    {
        public function classify(string $minimizedDescription): IncidentSuggestion
        {
            throw new ClassificationUnavailable('not_configured');
        }
    });
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    for ($attempt = 0; $attempt < 4; $attempt++) {
        expect(fn () => workflowSuggestion($actor, $incident))->toThrow(ClassificationUnavailable::class);
    }
    expect($incident->fresh()->classification_attempts)->toBe(0)
        ->and($incident->classifications()->where('failure_code', 'not_configured')->count())->toBe(4);

    app()->instance(IClasificadorIncidencias::class, new FakeIncidentClassifier);
    expect(workflowSuggestion($actor, $incident)->status)->toBe('sugerida')
        ->and($incident->fresh()->classification_attempts)->toBe(1);
});

test('recupera solicitudes abandonadas sin borrar la trazabilidad', function () {
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    $incident->forceFill(['classification_attempts' => 1])->save();
    $abandoned = $incident->classifications()->create([
        'actor_id' => $actor->id, 'kind' => 'ia', 'status' => 'solicitando',
    ]);
    expect(fn () => workflowSuggestion($actor, $incident))->toThrow(ValidationException::class);
    expect($incident->fresh()->classification_attempts)->toBe(1);
    $abandoned->forceFill(['created_at' => now()->subMinutes(3)])->save();
    $suggestion = workflowSuggestion($actor, $incident);

    expect($abandoned->fresh()->status)->toBe('fallida')
        ->and($abandoned->fresh()->failure_code)->toBe('interrupted_request')
        ->and($suggestion->status)->toBe('sugerida')
        ->and($incident->fresh()->classification_attempts)->toBe(2);
});

test('una respuesta tardía no sustituye la propuesta vigente', function () {
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    app()->instance(IClasificadorIncidencias::class, new class($actor, $incident) implements IClasificadorIncidencias
    {
        private int $calls = 0;

        public function __construct(private User $actor, private Incident $incident) {}

        public function classify(string $minimizedDescription): IncidentSuggestion
        {
            $this->calls++;
            if ($this->calls === 1) {
                $this->incident->classifications()->where('status', 'solicitando')->update(['created_at' => now()->subMinutes(3)]);
                workflowSuggestion($this->actor, $this->incident);

                return new IncidentSuggestion(IncidentCategory::Connectivity, 'Respuesta anterior que llegó tarde.', 'simulado-tardio');
            }

            return new IncidentSuggestion(IncidentCategory::Power, 'Propuesta de la solicitud vigente.', 'simulado-vigente');
        }
    });

    expect(fn () => workflowSuggestion($actor, $incident))->toThrow(ValidationException::class);
    $old = $incident->classifications()->oldest('id')->firstOrFail();
    $latest = $incident->classifications()->latest('id')->firstOrFail();
    expect($old->status)->toBe('superada')->and($latest->status)->toBe('sugerida')
        ->and($latest->suggested_category)->toBe('alimentacion')
        ->and($incident->fresh()->confirmed_category)->toBeNull();
    app(IncidentManagementService::class)->confirm($actor, $incident, 'alimentacion', $latest->id);
    expect($incident->fresh()->review_status)->toBe('aceptada');
});

test('minimiza los datos conocidos y exige revisar el texto vigente', function () {
    $actor = incidentActor();
    $camera = incidentCamera();
    $incident = app(IncidentManagementService::class)->register($actor, $camera->id,
        'Sin señal en CAM-PRIVATE-991 SERIAL-PRIVATE-123 Edificio Reservado 192.168.1.20. Contacto operador@example.com https://privado.local password=secreto sk-prueba123456789.');
    $service = app(IncidentManagementService::class);
    $preview = $service->preview($actor, $incident);

    foreach (['CAM-PRIVATE-991', 'SERIAL-PRIVATE-123', 'Edificio Reservado', '192.168.1.20', 'operador@example.com', 'privado.local', 'secreto', 'sk-prueba123456789'] as $private) {
        expect($preview)->not->toContain($private);
    }
    expect(fn () => $service->suggest($actor, $incident, 'hash-no-revisado'))->toThrow(ValidationException::class);
    expect($incident->fresh()->classification_attempts)->toBe(0);
});

test('Livewire exige consentimiento y permite confirmar la corrección', function () {
    $actor = incidentActor();
    $incident = workflowIncident($actor);
    $this->actingAs($actor);
    $component = Livewire::test(Index::class)
        ->call('prepareClassification', $incident->id)
        ->call('classify')->assertHasErrors('shareApproved')
        ->set('shareApproved', true)->call('classify')->assertHasNoErrors()
        ->assertSee('Aceptar sugerencia')
        ->call('correctClassification')->set('category', 'alimentacion')->call('confirm')->assertHasNoErrors();

    expect($incident->fresh()->review_status)->toBe('corregida');
});

test('las rutas de incidencias exigen autenticación y rol institucional', function () {
    $this->get('/incidents')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->get('/incidents')->assertForbidden();
    $this->actingAs(incidentActor('auxiliar'))->get('/incidents')->assertOk()->assertSee('Incidencias técnicas');
});

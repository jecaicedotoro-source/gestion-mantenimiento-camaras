<?php

use App\Contracts\IClasificadorIncidencias;
use App\Data\IncidentSuggestion;
use App\Exceptions\ClassificationUnavailable;
use App\Models\Camera;
use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use App\Services\IncidentManagementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\FakeIncidentClassifier;

beforeEach(function () {
    Http::preventStrayRequests();
    app()->instance(IClasificadorIncidencias::class, new FakeIncidentClassifier);
});

function apiIncidentUser(string $role = 'tecnico', bool $active = true): User
{
    $model = Role::firstOrCreate(['code' => $role], ['name' => ucfirst($role)]);

    return User::factory()->create(['role_id' => $model->id, 'is_active' => $active]);
}

function apiIncidentCamera(): Camera
{
    return Camera::create([
        'codigo_inventario' => 'CAM-INC-001', 'marca' => 'Prueba', 'modelo' => 'MVP',
        'tipo' => 'IP', 'ubicacion' => 'Área de pruebas', 'estado' => 'activo',
    ]);
}

test('API registra consulta sugiere y confirma una incidencia con trazabilidad', function () {
    $actor = apiIncidentUser();
    $camera = apiIncidentCamera();
    Sanctum::actingAs($actor);
    $id = $this->postJson('/api/v1/incidencias', ['camera_id' => $camera->id, 'description' => 'Se pierde la conexión de forma repetida.'])
        ->assertCreated()->assertJsonPath('data.review_status', 'pendiente')->json('data.id');
    $this->getJson('/api/v1/incidencias/'.$id)->assertOk()->assertJsonPath('data.confirmed_category', null);
    $hash = $this->getJson('/api/v1/incidencias/'.$id.'/sugerencias-ia/vista-previa')->assertOk()->json('data.approved_input_hash');
    $suggestion = $this->postJson('/api/v1/incidencias/'.$id.'/sugerencias-ia', ['approved_input_hash' => $hash, 'consent' => true])
        ->assertCreated()->assertJsonPath('data.suggested_category', 'conectividad')->json('data.id');
    expect(Incident::findOrFail($id)->confirmed_category)->toBeNull();
    $this->patchJson('/api/v1/incidencias/'.$id.'/categoria', ['category' => 'alimentacion', 'suggestion_id' => $suggestion])
        ->assertOk()->assertJsonPath('data.review_status', 'corregida')->assertJsonPath('data.confirmed_by', $actor->id);
    $this->assertDatabaseHas('incident_classifications', ['kind' => 'humana', 'status' => 'corregida', 'suggestion_id' => $suggestion]);
    Http::assertNothingSent();
});

test('API exige consentimiento y permite alternativa manual tras fallo de IA', function () {
    app()->instance(IClasificadorIncidencias::class, new class implements IClasificadorIncidencias
    {
        public function classify(string $minimizedDescription): IncidentSuggestion
        {
            throw new ClassificationUnavailable('timeout_or_connection');
        }
    });
    $actor = apiIncidentUser();
    $incident = app(IncidentManagementService::class)->register($actor, apiIncidentCamera()->id, 'La cámara pierde conexión de forma repetida.');
    Sanctum::actingAs($actor);
    $hash = $this->getJson('/api/v1/incidencias/'.$incident->id.'/sugerencias-ia/vista-previa')->assertOk()->json('data.approved_input_hash');
    $this->postJson('/api/v1/incidencias/'.$incident->id.'/sugerencias-ia', ['approved_input_hash' => $hash, 'consent' => false])
        ->assertUnprocessable()->assertJsonValidationErrors('consent');
    $this->postJson('/api/v1/incidencias/'.$incident->id.'/sugerencias-ia', ['approved_input_hash' => $hash, 'consent' => true])
        ->assertStatus(503)->assertJsonPath('manual_available', true);
    $this->patchJson('/api/v1/incidencias/'.$incident->id.'/categoria', ['category' => 'hardware'])
        ->assertOk()->assertJsonPath('data.review_status', 'manual');
});

test('API bloquea visitantes auxiliares y cuentas inactivas en acciones de incidencia', function () {
    $actor = apiIncidentUser();
    $incident = app(IncidentManagementService::class)->register($actor, apiIncidentCamera()->id, 'La cámara pierde conexión de forma repetida.');
    $path = '/api/v1/incidencias/'.$incident->id;
    $this->getJson($path)->assertUnauthorized();
    Sanctum::actingAs(apiIncidentUser('auxiliar'));
    $this->getJson($path)->assertOk();
    $this->postJson($path.'/sugerencias-ia', [])->assertForbidden();
    $this->patchJson($path.'/categoria', ['category' => 'hardware'])->assertForbidden();
    Sanctum::actingAs(apiIncidentUser('administrador', false));
    $this->getJson($path)->assertForbidden();
    $this->patchJson($path.'/categoria', ['category' => 'hardware'])->assertForbidden();
    expect($incident->fresh()->confirmed_at)->toBeNull();
});

test('API rechaza decisiones inventadas y cámaras inexistentes', function () {
    $actor = apiIncidentUser();
    $incident = app(IncidentManagementService::class)->register($actor, apiIncidentCamera()->id, 'La cámara pierde conexión de forma repetida.');
    Sanctum::actingAs($actor);
    $this->patchJson('/api/v1/incidencias/'.$incident->id.'/categoria', ['category' => 'inventada'])
        ->assertUnprocessable()->assertJsonValidationErrors('category');
    $this->postJson('/api/v1/incidencias', ['camera_id' => 999999, 'description' => 'Una falla técnica de prueba.'])
        ->assertUnprocessable()->assertJsonValidationErrors('camera_id');
    expect(Incident::count())->toBe(1)->and($incident->classifications()->count())->toBe(0);
});

test('API aplica la autorización view en consultas individuales', function () {
    $reporter = apiIncidentUser('tecnico');

    $incident = app(IncidentManagementService::class)->register(
        $reporter,
        apiIncidentCamera()->id,
        'La cámara pierde conexión de forma repetida.'
    );

    $path = '/api/v1/incidencias/'.$incident->id;

    // Los cuatro roles autorizados pueden consultar incidencias.
    foreach (['administrador', 'coordinador', 'tecnico', 'auxiliar'] as $role) {
        $user = apiIncidentUser($role);

        expect(Gate::forUser($user)->allows('view', $incident))->toBeTrue();

        Sanctum::actingAs($user);

        $this->getJson($path)
            ->assertOk()
            ->assertJsonPath('data.id', $incident->id);
    }

    // Un usuario sin rol institucional no puede consultar.
    $withoutRole = User::factory()->create([
        'role_id' => null,
        'is_active' => true,
    ]);

    expect(Gate::forUser($withoutRole)->allows('view', $incident))->toBeFalse();

    Sanctum::actingAs($withoutRole);

    $this->getJson($path)->assertForbidden();

    // Una cuenta inactiva tampoco puede consultar.
    $inactive = apiIncidentUser('administrador', false);

    expect(Gate::forUser($inactive)->allows('view', $incident))->toBeFalse();

    Sanctum::actingAs($inactive);

    $this->getJson($path)->assertForbidden();
});

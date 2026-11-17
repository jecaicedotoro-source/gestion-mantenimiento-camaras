<?php

use App\Models\Camera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    // Estas pruebas solo admiten SQLite en memoria y datos sintéticos.
    expect(app()->environment('testing'))->toBeTrue()
        ->and(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:');
    Http::preventStrayRequests();

    $role = Role::firstOrCreate(['code' => 'administrador'], ['name' => 'Administrador']);
    $admin = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

    // Se valida Sanctum con un token efímero real, sin mostrarlo ni guardarlo en evidencias.
    $this->withToken($admin->createToken('ejecucion-cinco-casos')->plainTextToken);
});

/** @return array<string, string|null> */
function executionPlanCameraPayload(): array
{
    return [
        'codigo_inventario' => 'QA-CAM-001',
        'numero_serie' => 'QA-SN-001',
        'marca' => 'Marca QA',
        'modelo' => 'Modelo QA',
        'tipo' => 'IP',
        'direccion_ip' => '192.0.2.10',
        'ubicacion' => 'Laboratorio QA',
        'estado' => 'activo',
        'observaciones' => 'Prueba sintética',
    ];
}

/** @param array<string, string|null> $payload */
function assertExecutionPlanCamera(TestResponse $response, array $payload): void
{
    // Se comparan todos los atributos de negocio del JSON, incluidos los valores nulos.
    foreach ($payload as $field => $value) {
        $response->assertJsonPath('data.'.$field, $value);
    }
}

test('CP-01 registra y consulta una cámara válida con persistencia íntegra', function () {
    $payload = executionPlanCameraPayload();
    $before = Camera::count();

    $created = $this->postJson('/api/cameras', $payload)->assertCreated();
    $id = $created->json('data.id');
    expect($id)->toBeInt();
    assertExecutionPlanCamera($created, $payload);
    assertExecutionPlanCamera($this->getJson('/api/cameras/'.$id)->assertOk(), $payload);

    $this->assertDatabaseHas('cameras', ['id' => $id, ...$payload]);
    expect(Camera::count())->toBe($before + 1)
        ->and(DB::table('cameras')->where('codigo_inventario', 'QA-CAM-001')->where('numero_serie', 'QA-SN-001')->count())->toBe(1);
    Http::assertNothingSent();
});

test('CP-02 rechaza código de inventario vacío sin escribir registros', function () {
    $payload = executionPlanCameraPayload();
    $payload['codigo_inventario'] = '';
    $payload['numero_serie'] = 'QA-SN-VALIDACION';
    $before = Camera::count();

    $this->postJson('/api/cameras', $payload)->assertUnprocessable()
        ->assertJsonValidationErrors('codigo_inventario');

    expect(Camera::count())->toBe($before);
    $this->assertDatabaseMissing('cameras', ['numero_serie' => 'QA-SN-VALIDACION']);
    Http::assertNothingSent();
});

test('CP-03 permite código de inventario repetido con series distintas', function () {
    // Se recrea CP-01 como precondición local: el resultado no depende del orden de ejecución.
    $first = executionPlanCameraPayload();
    $firstId = $this->postJson('/api/cameras', $first)->assertCreated()->json('data.id');
    $second = $first;
    $second['numero_serie'] = 'QA-SN-002';
    $before = Camera::count();

    $created = $this->postJson('/api/cameras', $second)->assertCreated();
    $secondId = $created->json('data.id');
    expect($secondId)->toBeInt()->not->toBe($firstId);
    assertExecutionPlanCamera($created, $second);
    assertExecutionPlanCamera($this->getJson('/api/cameras/'.$firstId)->assertOk(), $first);
    assertExecutionPlanCamera($this->getJson('/api/cameras/'.$secondId)->assertOk(), $second);

    $this->assertDatabaseHas('cameras', ['id' => $firstId, ...$first]);
    $this->assertDatabaseHas('cameras', ['id' => $secondId, ...$second]);
    expect(Camera::count())->toBe($before + 1)
        ->and(DB::table('cameras')->where('codigo_inventario', 'QA-CAM-001')->count())->toBe(2);
    Http::assertNothingSent();
});

test('CP-04 rechaza serie duplicada y conserva intacta la cámara original', function () {
    // Cada caso construye su propia precondición CP-01 dentro de SQLite en memoria.
    $original = executionPlanCameraPayload();
    $id = $this->postJson('/api/cameras', $original)->assertCreated()->json('data.id');
    $duplicate = $original;
    $duplicate['codigo_inventario'] = 'QA-CAM-DUP';
    $before = Camera::count();

    $this->postJson('/api/cameras', $duplicate)->assertUnprocessable()
        ->assertJsonValidationErrors('numero_serie');

    expect(Camera::count())->toBe($before)
        ->and(Camera::findOrFail($id)->only(array_keys($original)))->toBe($original);
    $this->assertDatabaseMissing('cameras', ['codigo_inventario' => 'QA-CAM-DUP']);
    $this->assertDatabaseHas('cameras', ['id' => $id, ...$original]);
    assertExecutionPlanCamera($this->getJson('/api/cameras/'.$id)->assertOk(), $original);
    Http::assertNothingSent();
});

test('CP-05 admite serie JSON nula y conserva NULL en SQLite', function () {
    $payload = executionPlanCameraPayload();
    $payload['codigo_inventario'] = 'QA-CAM-SIN-SERIE';
    $payload['numero_serie'] = null;
    $before = Camera::count();

    $created = $this->postJson('/api/cameras', $payload)->assertCreated();
    $id = $created->json('data.id');
    expect($id)->toBeInt();
    assertExecutionPlanCamera($created, $payload);
    assertExecutionPlanCamera($this->getJson('/api/cameras/'.$id)->assertOk(), $payload);

    $this->assertDatabaseHas('cameras', ['id' => $id, ...$payload]);
    expect(Camera::count())->toBe($before + 1)
        ->and(DB::table('cameras')->where('id', $id)->whereNull('numero_serie')->count())->toBe(1);
    Http::assertNothingSent();
});

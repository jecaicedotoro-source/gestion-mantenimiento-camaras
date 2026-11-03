<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

function errorResponseAdmin(bool $verified = true): User
{
    $role = Role::firstOrCreate(['code' => 'administrador'], ['name' => 'Administrador']);

    return User::factory()->create([
        'role_id' => $role->id,
        'email_verified_at' => $verified ? now() : null,
    ]);
}

test('API devuelve autenticación y recurso inexistente en español sin detalles internos', function () {
    $this->getJson('/api/cameras')->assertUnauthorized()
        ->assertExactJson(['message' => 'Debes autenticarte para acceder a este recurso.'])
        ->assertHeader('Content-Type', 'application/json');
    $this->getJson('/api/recurso-de-prueba-inexistente')->assertNotFound()
        ->assertExactJson(['message' => 'El recurso solicitado no existe.']);
});

test('API traduce rechazo por correo sin verificar y por permisos insuficientes', function () {
    Sanctum::actingAs(errorResponseAdmin(false));
    $this->getJson('/api/cameras')->assertForbidden()
        ->assertExactJson(['message' => 'No tienes permiso para realizar esta operación.']);

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/cameras', [])->assertForbidden()
        ->assertExactJson(['message' => 'No tienes permiso para realizar esta operación.']);
});

test('API conserva Retry-After y estado 429 al traducir el límite de solicitudes', function () {
    Route::get('/api/pruebas-internas/limite', fn () => response()->json(['message' => 'Solicitud aceptada.']))
        ->middleware('throttle:1,1');
    $this->getJson('/api/pruebas-internas/limite')->assertOk()
        ->assertExactJson(['message' => 'Solicitud aceptada.']);
    $response = $this->getJson('/api/pruebas-internas/limite')->assertTooManyRequests()
        ->assertExactJson(['message' => 'Has realizado demasiadas solicitudes. Inténtalo de nuevo más tarde.'])
        ->assertHeader('Retry-After')->assertHeader('Content-Type', 'application/json');

    expect((int) $response->headers->get('Retry-After'))->toBeGreaterThan(0)
        ->and((int) $response->headers->get('X-RateLimit-Limit'))->toBe(1);
});

test('API oculta mensaje y traza de un error interno incluso con debug activo', function () {
    config(['app.debug' => true]);
    Route::get('/api/pruebas-internas/error', function () {
        throw new RuntimeException('SECRETO_FICTICIO_SOLO_PRUEBA_12345');
    });
    $response = $this->getJson('/api/pruebas-internas/error')->assertStatus(500)
        ->assertExactJson(['message' => 'Ocurrió un error interno. Inténtalo de nuevo más tarde.'])
        ->assertHeader('Content-Type', 'application/json');

    expect($response->getContent())->not->toContain('SECRETO_FICTICIO', 'RuntimeException', 'trace', 'exception', 'file', 'line')
        ->and(config('app.debug'))->toBeTrue();
});

test('la normalización conserva errores de validación y redirecciones web', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    Sanctum::actingAs(errorResponseAdmin());
    $this->postJson('/api/cameras', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['codigo_inventario', 'marca', 'modelo', 'tipo', 'ubicacion', 'estado']);
});

test('las respuestas 503 seguras del servicio se conservan directamente', function () {
    Route::get('/api/pruebas-internas/servicio', fn () => response()->json([
        'message' => 'Puedes clasificar la incidencia manualmente.', 'manual_available' => true,
    ], 503));
    $this->getJson('/api/pruebas-internas/servicio')->assertStatus(503)->assertExactJson([
        'message' => 'Puedes clasificar la incidencia manualmente.', 'manual_available' => true,
    ]);
});

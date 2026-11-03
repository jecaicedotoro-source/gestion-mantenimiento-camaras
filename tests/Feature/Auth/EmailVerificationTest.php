<?php

use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Sanctum\Sanctum;

function provisionedAccount(string $roleCode = 'administrador'): User
{
    $role = Role::firstOrCreate(['code' => $roleCode], ['name' => ucfirst($roleCode)]);

    return User::factory()->create(['role_id' => $role->id, 'email_verified_at' => null]);
}

test('no existen rutas de confirmación de correo ni registro público', function () {
    expect(Features::enabled(Features::emailVerification()))->toBeFalse()
        ->and(Route::has('verification.notice'))->toBeFalse()
        ->and(Route::has('verification.verify'))->toBeFalse()
        ->and(Route::has('verification.send'))->toBeFalse();
    $this->get('/email/verify')->assertNotFound();
    $this->post('/email/verification-notification')->assertNotFound();
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

test('una cuenta creada por el administrador accede sin confirmar su correo y sin notificación', function () {
    Notification::fake();
    $admin = provisionedAccount();
    $role = Role::firstOrCreate(['code' => 'tecnico'], ['name' => 'Técnico']);
    $account = app(UserManagementService::class)->create($admin, [
        'name' => 'Cuenta Administrada', 'email' => 'administrada@example.test',
        'role_id' => $role->id, 'password' => 'ClaveSegura!2026',
        'password_confirmation' => 'ClaveSegura!2026',
    ]);
    expect($account->email_verified_at)->toBeNull();
    Notification::assertNothingSent();
    $this->post('/login', ['email' => $account->email, 'password' => 'ClaveSegura!2026'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($account);
    $this->get('/dashboard')->assertOk();
    $this->get('/cameras')->assertOk();
});

test('los cuatro roles consultan la API sin confirmar correo pero solo el administrador gestiona usuarios', function () {
    foreach (['administrador', 'coordinador', 'tecnico', 'auxiliar'] as $code) {
        $account = provisionedAccount($code);
        Sanctum::actingAs($account);
        $this->getJson('/api/cameras')->assertOk();
        $this->actingAs($account)->get('/admin/users')->assertStatus($code === 'administrador' ? 200 : 403);
    }
});

test('una cuenta inactiva sin correo confirmado sigue bloqueada por contraseña y API', function () {
    $account = provisionedAccount();
    $account->forceFill(['is_active' => false])->save();
    $this->post('/login', ['email' => $account->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    Sanctum::actingAs($account);
    $this->getJson('/api/cameras')->assertForbidden();
});

test('el perfil no ofrece enlaces ni acciones para confirmar el correo', function () {
    $account = provisionedAccount();
    $this->actingAs($account)->get('/settings/profile')->assertOk()
        ->assertDontSee('resendVerificationNotification', false)
        ->assertDontSee('verification-link-sent', false);
});

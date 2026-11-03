<?php

use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Passkeys;
use Livewire\Livewire;

function managedUser(string $roleCode = 'administrador', array $attributes = []): User
{
    $role = Role::firstOrCreate(['code' => $roleCode], ['name' => ucfirst($roleCode)]);

    return User::factory()->create(['role_id' => $role->id, ...$attributes]);
}

function managedUserData(array $attributes = []): array
{
    $role = Role::firstOrCreate(['code' => 'tecnico'], ['name' => 'Técnico']);

    return [
        'name' => ' Nueva Persona ',
        'email' => ' NUEVA@example.com ',
        'role_id' => $role->id,
        'password' => 'ClaveSegura!2026',
        'password_confirmation' => 'ClaveSegura!2026',
        ...$attributes,
    ];
}

test('servicio crea una cuenta activa sin enviar confirmación de correo', function () {
    Notification::fake();
    $admin = managedUser();
    $user = app(UserManagementService::class)->create($admin, managedUserData(['is_active' => false]));

    expect($user->name)->toBe('Nueva Persona')
        ->and($user->email)->toBe('nueva@example.com')
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('ClaveSegura!2026', $user->password))->toBeTrue();
    Notification::assertNothingSent();
});

test('servicio rechaza correos repetidos y contraseñas débiles', function () {
    $admin = managedUser();
    managedUser('tecnico', ['email' => 'nueva@example.com']);

    try {
        app(UserManagementService::class)->create($admin, managedUserData(['password' => 'breve']));
        $this->fail('La validación debió rechazar los datos.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKeys(['email', 'password']);
    }
});

test('técnico y administrador desactivado no pueden administrar usuarios', function () {
    $technician = managedUser('tecnico');
    $inactive = managedUser('administrador', ['is_active' => false, 'email_verified_at' => null]);

    foreach ([$technician, $inactive] as $actor) {
        expect(fn () => app(UserManagementService::class)->create($actor, managedUserData()))
            ->toThrow(AuthorizationException::class);
    }
    $this->actingAs($technician)->get('/admin/users')->assertForbidden();
});

test('cuenta desactivada no puede reutilizar un actor cacheado para modificar usuarios', function () {
    $admin = managedUser();
    User::whereKey($admin->id)->update(['is_active' => false]);

    expect(fn () => app(UserManagementService::class)->create($admin, managedUserData()))
        ->toThrow(AuthorizationException::class);
});

test('actualización conserva contraseña vacía y acepta el correo nuevo sin confirmación', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $password = $target->password;
    $updated = app(UserManagementService::class)->update($admin, $target, managedUserData([
        'password' => null, 'password_confirmation' => null,
    ]));

    expect($updated->password)->toBe($password)
        ->and($updated->email_verified_at)->toBeNull()
        ->and($updated->email)->toBe('nueva@example.com');
});

test('administrador puede cambiar rol y contraseña de otra cuenta', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $coordinator = Role::firstOrCreate(['code' => 'coordinador'], ['name' => 'Coordinador']);
    $token = $target->createToken('anterior')->plainTextToken;
    $updated = app(UserManagementService::class)->update($admin, $target, managedUserData([
        'email' => $target->email, 'role_id' => $coordinator->id,
    ]));

    expect($updated->role_id)->toBe($coordinator->id)
        ->and(Hash::check('ClaveSegura!2026', $updated->password))->toBeTrue()
        ->and($updated->tokens()->count())->toBe(0);
    $this->withToken($token)->getJson('/api/cameras')->assertUnauthorized();
});

test('administrador no puede cambiar su propio rol ni desactivar su propia cuenta', function () {
    $admin = managedUser();
    expect(fn () => app(UserManagementService::class)->update($admin, $admin, managedUserData()))
        ->toThrow(ValidationException::class);
    expect(fn () => app(UserManagementService::class)->deactivate($admin, $admin))
        ->toThrow(AuthorizationException::class);
    expect(fn () => app(UserManagementService::class)->deactivateOwn($admin))
        ->toThrow(AuthorizationException::class);
    expect($admin->fresh()->is_active)->toBeTrue()
        ->and(User::where('is_active', true)->where('role_id', $admin->role_id)->count())->toBe(1);
});

test('cambios consecutivos nunca dejan el sistema sin administrador activo', function () {
    $first = managedUser();
    $second = managedUser();
    $service = app(UserManagementService::class);
    $service->deactivate($first, $second);

    // El segundo actor existe en memoria, pero se consulta otra vez bajo bloqueo.
    expect(fn () => $service->deactivate($second, $first))->toThrow(AuthorizationException::class);
    expect(fn () => $service->deactivateOwn($first))->toThrow(AuthorizationException::class);
    expect(User::where('role_id', $first->role_id)->where('is_active', true)->count())->toBe(1);
});

test('desactivación conserva identidad y passkeys pero revoca sesiones tokens y recuerdo', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $oldRememberToken = $target->remember_token;
    $target->createToken('integracion');
    $passkey = $target->passkeys()->create(['name' => 'Llave prueba', 'credential_id' => 'prueba', 'credential' => []]);
    DB::table('sessions')->insert(['id' => 'sesion-prueba', 'user_id' => $target->id, 'payload' => 'prueba', 'last_activity' => time()]);

    app(UserManagementService::class)->deactivate($admin, $target);

    $target->refresh();
    expect($target->is_active)->toBeFalse()
        ->and($target->tokens()->count())->toBe(0)
        ->and($target->passkeys()->whereKey($passkey->id)->exists())->toBeTrue()
        ->and($target->remember_token)->not->toBe($oldRememberToken);
    $this->assertDatabaseMissing('sessions', ['id' => 'sesion-prueba']);
    $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
});

test('reactivación exige permisos y no restaura tokens ni sesiones anteriores', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $token = $target->createToken('anterior')->plainTextToken;
    $service = app(UserManagementService::class);
    $service->deactivate($admin, $target);
    expect(fn () => $service->reactivate($target, $target))->toThrow(AuthorizationException::class);
    $service->reactivate($admin, $target);

    expect($target->fresh()->is_active)->toBeTrue()->and($target->tokens()->count())->toBe(0);
    $this->withToken($token)->getJson('/api/cameras')->assertUnauthorized();
});

test('usuario desactivado no inicia sesión mediante contraseña', function () {
    $target = managedUser('tecnico', ['is_active' => false]);
    $this->post('/login', ['email' => $target->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('sesión existente pierde acceso tras desactivación', function () {
    $target = managedUser('tecnico');
    $this->actingAs($target);
    User::whereKey($target->id)->update(['is_active' => false]);
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('reactivación no devuelve acceso a una sesión antigua file o cookie', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $this->actingAs($target)->withSession(['auth.account_version' => 0]);
    $service = app(UserManagementService::class);
    $service->deactivate($admin, $target);
    $service->reactivate($admin, $target);

    expect($target->fresh()->auth_version)->toBe(1);
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('cuenta reactivada puede iniciar una sesión nueva con contraseña', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $service = app(UserManagementService::class);
    $service->deactivate($admin, $target);
    $service->reactivate($admin, $target);

    $this->post('/login', ['email' => $target->email, 'password' => 'password'])
        ->assertSessionHasNoErrors()->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertOk();
});

test('reactivación no restaura un desafío 2FA creado antes de revocar la cuenta', function () {
    $admin = managedUser();
    $target = managedUser('tecnico');
    $service = app(UserManagementService::class);
    $service->deactivate($admin, $target);
    $service->reactivate($admin, $target);

    $this->withSession(['login.id' => $target->id, 'auth.challenge_version' => 0])
        ->post('/two-factor-challenge', ['recovery_code' => 'invalido'])
        ->assertRedirect(route('login'))->assertSessionMissing('login.id');
    $this->assertGuest();
});

test('desafío 2FA pendiente no permite el acceso después de la desactivación', function () {
    $target = managedUser('tecnico', ['is_active' => false]);
    $this->withSession(['login.id' => $target->id, 'login.remember' => true])
        ->post('/two-factor-challenge', ['recovery_code' => 'invalido'])
        ->assertRedirect(route('login'))
        ->assertSessionMissing('login.id');
    $this->assertGuest();
});

test('autorización de passkeys rechaza cuentas inactivas y permite activas', function () {
    $target = managedUser('tecnico');
    $passkey = $target->passkeys()->create(['name' => 'Llave prueba', 'credential_id' => 'prueba', 'credential' => []]);
    expect(Passkeys::allowsLogin(Request::create('/passkeys/login'), $passkey))->toBeTrue();
    User::whereKey($target->id)->update(['is_active' => false]);
    expect(Passkeys::allowsLogin(Request::create('/passkeys/login'), $passkey))->toBeFalse();
});

test('API rechaza usuario inactivo incluso si conserva un token por un cambio externo', function () {
    $target = managedUser('tecnico');
    $token = $target->createToken('integracion')->plainTextToken;
    User::whereKey($target->id)->update(['is_active' => false]);
    $this->withToken($token)->getJson('/api/cameras')->assertForbidden();
});

test('API permite acceso al administrador activo sin confirmación de correo', function () {
    $target = managedUser('administrador', ['email_verified_at' => null]);
    $token = $target->createToken('integracion')->plainTextToken;
    $this->withToken($token)->getJson('/api/cameras')->assertOk();
});

test('acciones Livewire crean editan desactivan y reactivan sin eliminación física', function () {
    $admin = managedUser();
    $data = managedUserData();
    $this->actingAs($admin);
    $component = Livewire::test('pages::admin.users.index')
        ->call('openCreateForm')
        ->set('newName', 'Persona Prueba')
        ->set('newEmail', 'persona@example.com')
        ->set('newRoleId', (string) $data['role_id'])
        ->set('newPassword', $data['password'])
        ->set('newPasswordConfirmation', $data['password_confirmation'])
        ->call('saveUser')->assertHasNoErrors();

    $target = User::where('email', 'persona@example.com')->firstOrFail();
    $component->call('editUser', $target->id)->set('newName', 'Persona Editada')
        ->call('saveUser')->assertHasNoErrors();
    expect($target->fresh()->name)->toBe('Persona Editada');
    $component->call('confirmDelete', $target->id)->call('deleteUser')->assertHasNoErrors();
    expect($target->fresh()->is_active)->toBeFalse();
    $component->call('reactivateUser', $target->id)->assertHasNoErrors();
    expect($target->fresh()->is_active)->toBeTrue();
});

test('acciones Livewire manipuladas de técnico se rechazan y ningún policy permite borrar', function () {
    $technician = managedUser('tecnico');
    $admin = managedUser();
    $this->actingAs($technician);
    Livewire::test('pages::admin.users.index')->assertForbidden();
    expect(Gate::forUser($admin)->denies('delete', $technician))->toBeTrue();
});

test('administrador no puede autodesactivarse por el componente de perfil', function () {
    $admin = managedUser();
    $this->actingAs($admin);
    Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')
        ->call('deleteUser')->assertForbidden();
    expect($admin->fresh()->is_active)->toBeTrue();
});

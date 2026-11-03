<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('alta administrativa interactiva crea una cuenta verificada sin contraseña predeterminada', function () {
    $password = 'PruebaAdministrativa!2026';

    $this->artisan('app:create-admin')
        ->expectsQuestion('Nombre del administrador', 'Administrador de prueba')
        ->expectsQuestion('Correo electrónico', ' OPERADOR@example.com ')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas, número y símbolo)', $password)
        ->expectsQuestion('Confirma la contraseña', $password)
        ->expectsOutput('Administrador creado. No se han mostrado contraseñas ni tokens.')
        ->assertSuccessful();

    $user = User::where('email', 'operador@example.com')->sole();
    expect($user->name)->toBe('Administrador de prueba')
        ->and($user->role?->code)->toBe('administrador')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and(Role::count())->toBe(4);
});

test('alta administrativa rechaza contraseña débil y conserva la base sin cuentas nuevas', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Nombre del administrador', 'Administrador de prueba')
        ->expectsQuestion('Correo electrónico', 'operador@example.com')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas, número y símbolo)', 'breve')
        ->expectsQuestion('Confirma la contraseña', 'breve')
        ->assertFailed();

    expect(User::count())->toBe(0)->and(Role::count())->toBe(0);
});

test('alta administrativa rechaza correo repetido sin modificar la cuenta existente', function () {
    $existing = User::factory()->create(['email' => 'operador@example.com']);
    $password = $existing->password;
    $newPassword = 'PruebaAdministrativa!2026';

    $this->artisan('app:create-admin')
        ->expectsQuestion('Nombre del administrador', 'Otra persona')
        ->expectsQuestion('Correo electrónico', 'operador@example.com')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas, número y símbolo)', $newPassword)
        ->expectsQuestion('Confirma la contraseña', $newPassword)
        ->assertFailed();

    expect(User::count())->toBe(1)
        ->and($existing->fresh()->password)->toBe($password)
        ->and($existing->fresh()->role_id)->toBeNull();
});

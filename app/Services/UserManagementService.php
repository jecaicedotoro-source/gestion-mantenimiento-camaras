<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Coordina validación, autorización y cambios transaccionales de cuentas.
 * @phpstan-type UserData array{name: string, email: string, role_id: int|string, password?: string|null}
 */
final class UserManagementService
{
    /**
     * @param array<string, mixed> $data
     * @return UserData
     */
    public function validate(User $actor, array $data, ?User $target = null): array
    {
        Gate::forUser($actor)->authorize($target === null ? 'create' : 'update', $target ?? User::class);

        $data['name'] = is_string($data['name'] ?? null) ? trim($data['name']) : ($data['name'] ?? null);
        $data['email'] = is_string($data['email'] ?? null) ? Str::lower(trim($data['email'])) : ($data['email'] ?? null);

        /** @var UserData $validated */
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'password' => [$target === null ? 'required' : 'nullable', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [], [
            'name' => 'nombre', 'email' => 'correo electrónico',
            'role_id' => 'rol institucional', 'password' => 'contraseña',
        ])->validate();

        return $validated;
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): User
    {
        $user = DB::transaction(function () use ($actor, $data): User {
            $this->lockAdministration();
            $validated = $this->validate(User::findOrFail($actor->id), $data);
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role_id = (int) $validated['role_id'];
            $user->password = (string) ($validated['password'] ?? '');
            $user->is_active = true;
            $user->saveOrFail();

            return $user;
        });

        // No enviar notificaciones antes de confirmar la transacción.
        event(new Registered($user));

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, User $target, array $data): User
    {
        [$user, $emailChanged] = DB::transaction(function () use ($actor, $target, $data): array {
            $this->lockAdministration();
            $actor = User::findOrFail($actor->id);
            $user = User::with('role')->findOrFail($target->id);
            $validated = $this->validate($actor, $data, $user);
            $roleChanged = (int) $user->role_id !== (int) $validated['role_id'];

            if ($roleChanged && $actor->is($user)) {
                throw ValidationException::withMessages(['role_id' => 'No puedes modificar tu propio rol institucional.']);
            }

            if ($roleChanged) {
                $this->ensureAdministratorRemains($user);
            }

            $emailChanged = $user->email !== $validated['email'];
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role_id = (int) $validated['role_id'];

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
                $this->revokeAccess($user);
            }

            if ($emailChanged) {
                $user->email_verified_at = null;
                $this->revokeAccess($user);
            }

            if ($roleChanged) {
                // Ningún token conserva permisos de un rol anterior.
                $this->revokeAccess($user);
            }

            $user->saveOrFail();

            return [$user, $emailChanged];
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    public function deactivate(User $actor, User $target): User
    {
        return $this->setActive($actor, $target, false);
    }

    public function reactivate(User $actor, User $target): User
    {
        return $this->setActive($actor, $target, true);
    }

    public function deactivateOwn(User $actor): User
    {
        return $this->setActive($actor, $actor, false, true);
    }

    private function setActive(User $actor, User $target, bool $active, bool $own = false): User
    {
        return DB::transaction(function () use ($actor, $target, $active, $own): User {
            $this->lockAdministration();
            $actor = User::findOrFail($actor->id);
            $user = User::with('role')->findOrFail($target->id);
            Gate::forUser($actor)->authorize($own ? 'deactivateOwn' : ($active ? 'reactivate' : 'deactivate'), $user);

            if (! $active) {
                $this->ensureAdministratorRemains($user);
                $this->revokeAccess($user);
            }

            $user->is_active = $active;
            $user->saveOrFail();

            return $user;
        });
    }

    private function ensureAdministratorRemains(User $user): void
    {
        if ($user->is_active && $user->role?->code === 'administrador'
            && User::query()->where('role_id', $user->role_id)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['operation' => 'Debe permanecer al menos un administrador activo.']);
        }
    }

    private function lockAdministration(): void
    {
        // SQLite ignora SELECT FOR UPDATE: adquirir primero el bloqueo de escritura
        // serializa cambios de rol y desactivaciones antes de contar administradores.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::update('UPDATE roles SET code = code WHERE code = ?', ['administrador']);
        } else {
            Role::query()->where('code', 'administrador')->lockForUpdate()->first();
        }
    }

    private function revokeAccess(User $user): void
    {
        // La versión invalida también sesiones file/cookie tras una reactivación.
        $user->auth_version++;
        $user->remember_token = Str::random(60);
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}

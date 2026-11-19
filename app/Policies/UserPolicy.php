<?php

namespace App\Policies;

use App\Models\User;

/** Autorización central del módulo institucional de usuarios. */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active
            && $actor->role?->code === 'administrador';
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->viewAny($actor);
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $this->viewAny($actor) && ! $actor->is($target);
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $this->viewAny($actor) && ! $actor->is($target);
    }

    public function deactivateOwn(User $actor, User $target): bool
    {
        // Los administradores deben conservar acceso y recurrir a otro administrador.
        return $actor->is($target) && $actor->is_active
            && $actor->role?->code !== 'administrador';
    }

    public function delete(User $actor, User $target): bool
    {
        // El producto conserva la identidad, las referencias y los historiales.
        return false;
    }
}

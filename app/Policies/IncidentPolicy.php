<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

/** Permisos institucionales provisionales del MVP, explícitos y comprobados en servidor. */
final class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->role?->code, ['administrador', 'coordinador', 'tecnico', 'auxiliar'], true);
    }

    public function view(User $user, Incident $incident): bool
    {
    return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && in_array($user->role?->code, ['administrador', 'coordinador', 'tecnico'], true);
    }

    public function classify(User $user, Incident $incident): bool
    {
        return $this->create($user);
    }

    public function confirm(User $user, Incident $incident): bool
    {
        return $this->create($user);
    }
}

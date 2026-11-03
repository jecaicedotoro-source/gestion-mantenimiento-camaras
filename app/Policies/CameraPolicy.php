<?php

namespace App\Policies;

use App\Models\User;

final class CameraPolicy
{
    public function delete(User $actor): bool
    {
        return $actor->is_active && $actor->role?->code === 'administrador';
    }
}

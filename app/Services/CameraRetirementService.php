<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Support\Facades\Gate;

final class CameraRetirementService
{
    public function retire(int $cameraId): void
    {
        Gate::authorize('delete', Camera::class);

        // La baja conserva el registro y las referencias de sus incidencias.
        Camera::query()->findOrFail($cameraId)->deleteOrFail();
    }
}

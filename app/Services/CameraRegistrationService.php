<?php

namespace App\Services;

use App\Models\Camera;
use App\Support\CameraValidationRules;
use Illuminate\Support\Facades\Validator;

final class CameraRegistrationService
{
    /**
     * Valida y registra una cámara en el inventario.
     */
    public function register(array $data): Camera
    {
        $validated = Validator::make(
            $data,
            CameraValidationRules::rules()
        )->validate();

        return Camera::create($validated);
    }
}

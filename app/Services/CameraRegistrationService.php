<?php

namespace App\Services;

use App\Models\Camera;
use App\Support\CameraValidationRules;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CameraRegistrationService
{
    /**
     * Valida y registra una cámara en el inventario.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Camera
    {
        // Un dato opcional vacío se conserva como NULL; varias cámaras pueden no tener serie.
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $data[$field] = trim($value);
            }
        }
        foreach (['numero_serie', 'direccion_ip', 'observaciones'] as $field) {
            if (($data[$field] ?? '') === '') {
                $data[$field] = null;
            }
        }

        $validated = Validator::make(
            $data,
            CameraValidationRules::rules()
        )->validate();

        try {
            return DB::transaction(fn (): Camera => Camera::create($validated));
        } catch (UniqueConstraintViolationException) {
            // La restricción de SQLite también protege frente a registros simultáneos.
            throw ValidationException::withMessages([
                'numero_serie' => 'El número de serie ya está registrado.',
            ]);
        }
    }
}

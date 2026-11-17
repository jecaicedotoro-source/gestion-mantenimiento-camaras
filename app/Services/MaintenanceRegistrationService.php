<?php

namespace App\Services;

use App\Models\Maintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class MaintenanceRegistrationService
{
    /**
     * Valida y registra un mantenimiento.
     *
     * @param array<string, mixed> $data
     */
    public function register(array $data): Maintenance
    {
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $data[$field] = trim($value);
            }
        }

        $validated = Validator::make($data, [
            'camera_id' => [
                'required',
                'integer',
                Rule::exists('cameras', 'id')
                    ->whereNull('deleted_at'),
            ],
            'maintenance_date' => [
                'required',
                'date',
            ],
            'type' => [
                'required',
                Rule::in(['preventivo', 'correctivo']),
            ],
            'description' => [
                'required',
                'string',
                'max:2000',
            ],
            'responsible' => [
                'required',
                'string',
                'max:150',
            ],
            'result' => [
                'required',
                'string',
                'max:2000',
            ],
        ])->validate();

        return DB::transaction(
            fn (): Maintenance => Maintenance::create($validated)
        );
    }
}
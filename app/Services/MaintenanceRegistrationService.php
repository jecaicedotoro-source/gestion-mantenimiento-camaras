<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class MaintenanceRegistrationService
{
    /**
     * HU-09/HU-12: registra una intervención y, si procede,
     * la confirmación humana de reparación en una única transacción.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, ?User $actor = null): Maintenance
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
                Rule::exists('cameras', 'id')->whereNull('deleted_at'),
            ],
            'maintenance_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['preventivo', 'correctivo'])],
            'description' => ['required', 'string', 'max:2000'],
            'responsible' => ['required', 'string', 'max:150'],
            'result' => ['required', 'string', 'max:2000'],
            'incident_id' => ['nullable', 'integer', Rule::exists('incidents', 'id')],
            'repair_resolved' => ['nullable', 'boolean'],
        ])->validate();

        $incidentId = $validated['incident_id'] ?? null;
        $resolved = $validated['repair_resolved'] ?? null;

        if ($incidentId === null && $resolved !== null) {
            throw ValidationException::withMessages([
                'repair_resolved' => 'Solo puede confirmarse la reparación de una incidencia vinculada.',
            ]);
        }

        if ($incidentId !== null) {
            if ($validated['type'] !== 'correctivo') {
                throw ValidationException::withMessages([
                    'type' => 'Una incidencia solo puede vincularse con mantenimiento correctivo.',
                ]);
            }
            if ($resolved === null) {
                throw ValidationException::withMessages([
                    'repair_resolved' => 'Confirma expresamente si la intervención solucionó la incidencia.',
                ]);
            }
            if ($actor === null) {
                throw new AuthorizationException('Se requiere un usuario autorizado para asociar incidencias.');
            }

            Gate::forUser($actor)->authorize('create-maintenances');
        }

        return DB::transaction(function () use ($validated, $incidentId, $resolved, $actor): Maintenance {
            // Revalidación en la transacción: se prohíbe actuar sobre cámaras dadas de baja.
            if (Camera::query()->lockForUpdate()->find($validated['camera_id']) === null) {
                throw ValidationException::withMessages([
                    'camera_id' => 'Selecciona una cámara activa del inventario.',
                ]);
            }

            $incident = null;

            if ($incidentId !== null) {
                $incident = Incident::query()->lockForUpdate()->findOrFail($incidentId);

                if ($incident->camera_id !== (int) $validated['camera_id']) {
                    throw ValidationException::withMessages([
                        'incident_id' => 'La incidencia no pertenece a la cámara seleccionada.',
                    ]);
                }

                if ($incident->technical_status !== 'pendiente') {
                    throw ValidationException::withMessages([
                        'incident_id' => 'Esta incidencia ya figura corregida. No se admiten nuevas intervenciones vinculadas.',
                    ]);
                }
            }

            $maintenance = Maintenance::create([
                'camera_id' => $validated['camera_id'],
                'incident_id' => $incident?->id,
                'maintenance_date' => $validated['maintenance_date'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'responsible' => $validated['responsible'],
                'result' => $validated['result'],
                // null: intervención sin incidencia; false: no corrigió; true: corrigió.
                'repair_resolved' => $incident === null ? null : (bool) $resolved,
            ]);

            // Nunca se infiere el éxito de "result"; solo vale confirmación explícita.
            if ($incident !== null && (bool) $resolved) {
                $incident->forceFill([
                    'technical_status' => 'corregida',
                    'resolved_at' => now(),
                    'resolved_by' => $actor->id,
                ])->save();
            }

            return $maintenance;
        }, 3);
    }
}

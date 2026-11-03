<?php

namespace App\Services;

use App\Contracts\IClasificadorIncidencias;
use App\Enums\IncidentCategory;
use App\Exceptions\ClassificationUnavailable;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Coordina persistencia, cuotas y revisión humana, sin acoplar las vistas al proveedor. */
final class IncidentManagementService
{
    public function __construct(
        private readonly IClasificadorIncidencias $classifier,
        private readonly IncidentTextMinimizer $minimizer,
    ) {}

    public function register(User $actor, int $cameraId, string $description): Incident
    {
        Gate::forUser($actor)->authorize('create', Incident::class);
        $data = Validator::make(['camera_id' => $cameraId, 'description' => trim($description)], [
            'camera_id' => ['required', 'integer', 'exists:cameras,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        return DB::transaction(fn (): Incident => Incident::create([
            'camera_id' => $data['camera_id'],
            'description' => $data['description'],
            'reported_by' => $actor->id,
        ]));
    }

    /** La vista previa no se guarda como una segunda copia del texto enviado. */
    public function preview(User $actor, Incident $incident): string
    {
        Gate::forUser($actor)->authorize('classify', $incident);
        $incident->loadMissing('camera');

        return $this->minimizer->minimize($incident->description, [
            (string) $incident->camera->getAttribute('codigo_inventario'),
            (string) $incident->camera->getAttribute('numero_serie'),
            (string) $incident->camera->getAttribute('direccion_ip'),
            (string) $incident->camera->getAttribute('ubicacion'),
            $actor->name,
            $actor->email,
        ]);
    }

    public function suggest(User $actor, Incident $incident, string $approvedInputHash): IncidentClassification
    {
        Gate::forUser($actor)->authorize('classify', $incident);
        $incident->refresh();
        $text = $this->preview($actor, $incident);
        if (! hash_equals(hash('sha256', $text), $approvedInputHash)) {
            throw ValidationException::withMessages(['classification' => 'La descripción cambió. Revisa de nuevo el texto antes de enviarlo.']);
        }
        if (mb_strlen($text) < 10 || mb_strlen($text) > 2000) {
            throw ValidationException::withMessages(['classification' => 'La descripción minimizada debe contener entre 10 y 2000 caracteres.']);
        }

        // El bloqueo compartido evita carreras de cuota entre solicitudes del mismo usuario.
        $attempt = DB::transaction(function () use ($actor, $incident, $approvedInputHash): IncidentClassification {
            $current = $this->lockIncident($incident->id);
            Gate::forUser($actor)->authorize('classify', $current);
            $this->requirePending($current);

            // Una caída del proceso no puede bloquear para siempre la clasificación asistida.
            $abandonedAfter = max(1, min(30, (int) config('ai.timeout', 15))) + 60;
            $current->classifications()->where('kind', 'ia')->where('status', 'solicitando')
                ->where('created_at', '<=', now()->subSeconds($abandonedAfter))
                ->update(['status' => 'fallida', 'failure_code' => 'interrupted_request', 'updated_at' => now()]);

            $limit = max(1, min(10, (int) config('ai.attempts_per_incident', 3)));
            if ($current->classification_attempts >= $limit) {
                throw ValidationException::withMessages(['classification' => 'Se alcanzó el límite de solicitudes para esta incidencia. Utiliza la clasificación manual.']);
            }
            if ($current->classifications()->where('status', 'solicitando')->exists()) {
                throw ValidationException::withMessages(['classification' => 'Ya hay una clasificación en proceso para esta incidencia.']);
            }
            foreach ($this->quotas($actor) as [$key, $maximum, $seconds]) {
                if (RateLimiter::tooManyAttempts($key, $maximum)) {
                    throw ValidationException::withMessages(['classification' => 'Se alcanzó el límite temporal de solicitudes de IA. Puedes clasificar manualmente.']);
                }
            }
            foreach ($this->quotas($actor) as [$key, $maximum, $seconds]) {
                RateLimiter::hit($key, $seconds);
            }
            $current->increment('classification_attempts');

            return $current->classifications()->create([
                'actor_id' => $actor->id,
                'kind' => 'ia',
                'status' => 'solicitando',
                'input_hash' => $approvedInputHash,
            ]);
        }, 3);

        try {
            // La llamada externa ocurre fuera de la transacción para no bloquear SQLite.
            $suggestion = $this->classifier->classify($text);
        } catch (ClassificationUnavailable $exception) {
            DB::transaction(function () use ($attempt, $incident, $exception): void {
                $current = $this->lockIncident($incident->id);
                $attempt->refresh();
                // Sin configuración no hubo solicitud externa: se conserva el registro sin agotar el cupo permanente.
                if ($exception->reason === 'not_configured' && $attempt->failure_code !== 'not_configured' && $current->classification_attempts > 0) {
                    $current->decrement('classification_attempts');
                }
                $attempt->forceFill(['status' => 'fallida', 'failure_code' => $exception->reason])->save();
            }, 3);
            throw $exception;
        }

        $completed = DB::transaction(function () use ($attempt, $incident, $suggestion): IncidentClassification {
            $current = $this->lockIncident($incident->id);
            $attempt->refresh();
            // Una respuesta tardía jamás vuelve vigente una solicitud ya interrumpida o reemplazada.
            $isCurrent = $current->confirmed_at === null && $attempt->status === 'solicitando'
                && ! $current->classifications()->where('kind', 'ia')->where('id', '>', $attempt->id)->exists();
            $attempt->forceFill([
                'status' => $isCurrent ? 'sugerida' : 'superada',
                'suggested_category' => $suggestion->category->value,
                'explanation' => $suggestion->explanation,
                'model' => mb_substr($suggestion->model, 0, 150),
                'input_tokens' => $suggestion->inputTokens,
                'output_tokens' => $suggestion->outputTokens,
            ])->save();

            return $attempt;
        }, 3);

        if ($completed->status !== 'sugerida') {
            throw ValidationException::withMessages(['classification' => 'La solicitud dejó de estar vigente. Revisa la sugerencia o decisión humana actual.']);
        }

        return $completed;
    }

    public function confirm(User $actor, Incident $incident, string $category, ?int $suggestionId = null): Incident
    {
        Gate::forUser($actor)->authorize('confirm', $incident);
        if (IncidentCategory::tryFrom($category) === null) {
            throw ValidationException::withMessages(['category' => 'Selecciona una categoría técnica válida.']);
        }

        return DB::transaction(function () use ($actor, $incident, $category, $suggestionId): Incident {
            $current = $this->lockIncident($incident->id);
            Gate::forUser($actor)->authorize('confirm', $current);
            $this->requirePending($current);
            $suggestion = null;
            if ($suggestionId !== null) {
                $suggestion = $current->classifications()->where('kind', 'ia')->where('status', 'sugerida')->latest('id')->first();
                if ($suggestion === null || $suggestion->id !== $suggestionId) {
                    throw ValidationException::withMessages(['classification' => 'La sugerencia no corresponde a la propuesta vigente de esta incidencia.']);
                }
            }
            $status = $suggestion === null ? 'manual' : ($suggestion->suggested_category === $category ? 'aceptada' : 'corregida');
            $current->forceFill([
                'confirmed_category' => $category,
                'review_status' => $status,
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
            ])->save();
            $current->classifications()->create([
                'actor_id' => $actor->id,
                'kind' => 'humana',
                'status' => $status,
                'suggestion_id' => $suggestion?->id,
                'suggested_category' => $suggestion?->suggested_category,
                'confirmed_category' => $category,
            ]);

            return $current;
        }, 3);
    }

    private function lockIncident(int $id): Incident
    {
        // Escribir antes de leer serializa también decisiones concurrentes en SQLite.
        Incident::query()->whereKey($id)->increment('revision');

        return Incident::query()->lockForUpdate()->findOrFail($id);
    }

    private function requirePending(Incident $incident): void
    {
        if ($incident->confirmed_at !== null) {
            throw ValidationException::withMessages(['classification' => 'Esta incidencia ya tiene una decisión humana registrada.']);
        }
    }

    /** @return list<array{string, int, int}> */
    private function quotas(User $actor): array
    {
        return [
            ['incident-ai:minute:'.$actor->id, max(1, min(10, (int) config('ai.requests_per_minute', 5))), 60],
            ['incident-ai:day:'.$actor->id, max(1, min(100, (int) config('ai.requests_per_day', 50))), 86400],
        ];
    }
}

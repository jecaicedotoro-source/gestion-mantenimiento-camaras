<?php

namespace App\Http\Controllers\Api;

use App\Enums\IncidentCategory;
use App\Exceptions\ClassificationUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\User;
use App\Services\IncidentManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Adaptador HTTP: comparte autorización y reglas con Livewire mediante el servicio. */
final class IncidentController extends Controller
{
    public function store(Request $request, IncidentManagementService $service): JsonResponse
    {
        Gate::authorize('create', Incident::class);
        $data = $request->validate([
            'camera_id' => ['required', 'integer', 'exists:cameras,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $incident = $service->register($this->actor($request), (int) $data['camera_id'], $data['description']);

        return response()->json(['message' => 'Incidencia registrada.', 'data' => $this->present($incident)], 201);
    }

    public function show(Incident $incident): JsonResponse
    {
        Gate::authorize('viewAny', Incident::class);

        return response()->json(['data' => $this->present($incident)]);
    }

    public function preview(Request $request, Incident $incident, IncidentManagementService $service): JsonResponse
    {
        $text = $service->preview($this->actor($request), $incident);

        return response()->json(['data' => [
            'description' => $text,
            'approved_input_hash' => hash('sha256', $text),
            'notice' => 'Revisa el texto y elimina datos sensibles antes de autorizar la solicitud a OpenAI.',
        ]]);
    }

    public function suggest(Request $request, Incident $incident, IncidentManagementService $service): JsonResponse
    {
        Gate::authorize('classify', $incident);
        $data = $request->validate([
            'approved_input_hash' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'consent' => ['required', 'accepted'],
        ]);
        try {
            $suggestion = $service->suggest($this->actor($request), $incident, $data['approved_input_hash']);
        } catch (ClassificationUnavailable $exception) {
            return response()->json(['message' => $exception->getMessage(), 'manual_available' => true], 503);
        }

        return response()->json(['message' => 'Sugerencia pendiente de confirmación humana.', 'data' => [
            'id' => $suggestion->id,
            'incident_id' => $suggestion->incident_id,
            'suggested_category' => $suggestion->suggested_category,
            'explanation' => $suggestion->explanation,
            'status' => $suggestion->status,
        ]], 201);
    }

    public function confirm(Request $request, Incident $incident, IncidentManagementService $service): JsonResponse
    {
        Gate::authorize('confirm', $incident);
        $data = $request->validate([
            'category' => ['required', Rule::enum(IncidentCategory::class)],
            'suggestion_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $confirmed = $service->confirm(
            $this->actor($request), $incident, $data['category'],
            isset($data['suggestion_id']) ? (int) $data['suggestion_id'] : null,
        );

        return response()->json(['message' => 'Decisión humana registrada.', 'data' => $this->present($confirmed)]);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    /** @return array<string, mixed> */
    private function present(Incident $incident): array
    {
        return [
            'id' => $incident->id,
            'camera_id' => $incident->camera_id,
            'description' => $incident->description,
            'review_status' => $incident->review_status,
            'confirmed_category' => $incident->confirmed_category,
            'confirmed_by' => $incident->confirmed_by,
            'confirmed_at' => $incident->confirmed_at?->toIso8601String(),
        ];
    }
}

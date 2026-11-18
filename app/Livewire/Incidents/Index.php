<?php

namespace App\Livewire\Incidents;

use App\Enums\IncidentCategory;
use App\Exceptions\ClassificationUnavailable;
use App\Models\Camera;
use App\Models\Incident;
use App\Models\User;
use App\Services\IncidentManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/** Presentación: toda modificación y regla institucional se delega al servicio. */
class Index extends Component
{
    use WithPagination;

    public string $cameraId = '';

    public string $description = '';

    public string $category = '';

    public bool $shareApproved = false;

    #[Locked]
    public ?int $selectedIncidentId = null;

    #[Locked]
    public ?int $suggestionId = null;

    #[Locked]
    public string $previewText = '';

    #[Locked]
    public string $previewHash = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', Incident::class);
    }

    public function register(IncidentManagementService $service): void
    {
        $this->validate(['cameraId' => ['required', 'integer'], 'description' => ['required', 'string', 'min:10', 'max:2000']]);
        $service->register($this->actor(), (int) $this->cameraId, $this->description);
        $this->reset('cameraId', 'description');
        $this->resetPage();
        session()->flash('success', 'Incidencia registrada. Su clasificación queda pendiente de revisión humana.');
    }

    public function prepareClassification(int $id, IncidentManagementService $service): void
    {
        $incident = Incident::findOrFail($id);
        Gate::authorize('classify', $incident);
        $this->resetValidation();
        $this->selectedIncidentId = $incident->id;
        $this->suggestionId = null;
        $this->previewText = $service->preview($this->actor(), $incident);
        $this->previewHash = hash('sha256', $this->previewText);
        $this->shareApproved = false;
        $this->category = '';
    }

    public function classify(IncidentManagementService $service): void
    {
        $this->validate(['shareApproved' => ['accepted']]);
        abort_if($this->selectedIncidentId === null || $this->previewHash === '', 409);
        try {
            $attempt = $service->suggest($this->actor(), Incident::findOrFail($this->selectedIncidentId), $this->previewHash);
            $this->suggestionId = $attempt->id;
            $this->category = $attempt->suggested_category ?? '';
            $this->previewHash = '';
            $this->previewText = '';
            $this->shareApproved = false;
            session()->flash('success', 'La IA propuso una categoría. Acepta o corrige la sugerencia para guardar tu decisión.');
        } catch (ClassificationUnavailable $exception) {
            $this->addError('classification', $exception->getMessage());
        }
    }

    public function prepareReview(int $id): void
    {
        $incident = Incident::findOrFail($id);
        Gate::authorize('confirm', $incident);
        $suggestion = $incident->classifications()->where('kind', 'ia')->where('status', 'sugerida')->latest('id')->first();
        $this->resetValidation();
        $this->selectedIncidentId = $incident->id;
        $this->suggestionId = $suggestion?->id;
        $this->category = $suggestion->suggested_category ?? '';
        $this->previewText = '';
        $this->previewHash = '';
        $this->shareApproved = false;
    }

    public function correctClassification(): void
    {
        abort_if($this->selectedIncidentId === null, 409);
        Gate::authorize('confirm', Incident::findOrFail($this->selectedIncidentId));
        $this->category = '';
    }

    public function acceptSuggestion(IncidentManagementService $service): void
    {
        abort_if($this->selectedIncidentId === null || $this->suggestionId === null, 409);
        $incident = Incident::findOrFail($this->selectedIncidentId);
        Gate::authorize('confirm', $incident);
        $suggestion = $incident->classifications()->findOrFail($this->suggestionId);
        $service->confirm($this->actor(), $incident, $suggestion->suggested_category ?? '', $suggestion->id);
        $this->finishReview();
    }

    public function confirm(IncidentManagementService $service): void
    {
        abort_if($this->selectedIncidentId === null, 409);
        $service->confirm($this->actor(), Incident::findOrFail($this->selectedIncidentId), $this->category, $this->suggestionId);
        $this->finishReview();
    }

    private function finishReview(): void
    {
        $this->reset('selectedIncidentId', 'suggestionId', 'category', 'previewText', 'previewHash', 'shareApproved');
        $this->resetValidation();
        session()->flash('success', 'Decisión humana registrada con responsable, fecha y trazabilidad.');
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    public function render(): View
    {
        return view('livewire.incidents.index', [
            'incidents' => Incident::with(['camera', 'reviewer', 'repairResolver', 'classifications' => fn ($query) => $query->latest('id')])->withCount('maintenances')->latest()->paginate(10),
            'cameras' => Camera::query()->orderBy('codigo_inventario')->get(['id', 'codigo_inventario', 'ubicacion']),
            'categories' => IncidentCategory::cases(),
        ])->layout('layouts::app', ['title' => 'Incidencias técnicas']);
    }
}

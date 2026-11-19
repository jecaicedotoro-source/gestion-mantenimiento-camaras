<?php

namespace App\Livewire\Maintenances;

use App\Models\Camera;
use App\Models\Incident;
use App\Models\User;
use App\Services\MaintenanceRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Create extends Component
{
    public string $cameraId = '';

    public string $maintenanceDate = '';

    public string $type = '';

    public string $description = '';

    public string $responsible = '';

    public string $result = '';

    public string $repairOutcome = '';

    #[Locked]
    public ?int $incidentId = null;

    /** Comprueba permisos también en cada solicitud Livewire. */
    public function boot(): void
    {
        Gate::authorize('create-maintenances');
    }

    /**
     * Entrada normal (HU-09) o preseleccionada desde una incidencia (HU-12).
     * El identificador de la incidencia permanece bloqueado durante la edición.
     */
    public function mount(?int $incident = null): void
    {
        $this->maintenanceDate = now()->toDateString();
        $this->responsible = Auth::user()->name;

        if ($incident !== null) {
            $selected = Incident::with('camera')->findOrFail($incident);
            Gate::authorize('view', $selected);
            abort_unless($selected->technical_status === 'pendiente' && ! $selected->camera->trashed(), 409);

            $this->incidentId = $selected->id;
            $this->cameraId = (string) $selected->camera_id;
            $this->type = 'correctivo';
        }
    }

    /** Registra el mantenimiento, validando nuevamente todas las relaciones en el servicio. */
    public function register(MaintenanceRegistrationService $service): void
    {
        $rules = [
            'cameraId' => [
                'required',
                'integer',
                Rule::exists('cameras', 'id')->whereNull('deleted_at'),
            ],
            'maintenanceDate' => ['required', 'date'],
            'type' => ['required', Rule::in(['preventivo', 'correctivo'])],
            'description' => ['required', 'string', 'max:2000'],
            'responsible' => ['required', 'string', 'max:150'],
            'result' => ['required', 'string', 'max:2000'],
        ];

        if ($this->incidentId !== null) {
            $rules['repairOutcome'] = ['required', Rule::in(['si', 'no'])];
        }

        $this->validate($rules);

        $data = [
            'camera_id' => (int) $this->cameraId,
            'maintenance_date' => $this->maintenanceDate,
            'type' => $this->type,
            'description' => $this->description,
            'responsible' => $this->responsible,
            'result' => $this->result,
        ];

        if ($this->incidentId !== null) {
            $data['incident_id'] = $this->incidentId;
            $data['repair_resolved'] = $this->repairOutcome === 'si';
        }

        $actor = Auth::user();
        abort_unless($actor instanceof User, 401);

        $service->register($data, $actor);

        if ($this->incidentId !== null) {
            session()->flash(
                'success',
                $this->repairOutcome === 'si'
                    ? 'Mantenimiento registrado y reparación confirmada por el usuario.'
                    : 'Mantenimiento registrado. La incidencia continúa pendiente de corrección.'
            );
            $this->redirectRoute('incidents.index', navigate: true);

            return;
        }

        $this->reset('cameraId', 'type', 'description', 'result', 'repairOutcome');
        $this->maintenanceDate = now()->toDateString();
        $this->resetValidation();

        session()->flash('success', 'Mantenimiento registrado correctamente.');
    }

    /** Presenta cámaras disponibles y, cuando corresponde, la incidencia preseleccionada. */
    public function render(): View
    {
        return view('livewire.maintenances.create', [
            'cameras' => Camera::query()
                ->orderBy('codigo_inventario')
                ->get(['id', 'codigo_inventario', 'ubicacion']),
            'linkedIncident' => $this->incidentId === null
                ? null
                : Incident::with('camera')->findOrFail($this->incidentId),
        ])->layout('layouts::app', [
            'title' => 'Registrar mantenimiento',
        ]);
    }
}

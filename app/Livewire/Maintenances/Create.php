<?php

namespace App\Livewire\Maintenances;

use App\Models\Camera;
use App\Services\MaintenanceRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public string $cameraId = '';

    public string $maintenanceDate = '';

    public string $type = '';

    public string $description = '';

    public string $responsible = '';

    public string $result = '';

    /**
     * Comprueba los permisos en cada solicitud Livewire.
     */
    public function boot(): void
    {
        Gate::authorize('create-maintenances');
    }

    /**
     * Prepara los valores iniciales del formulario.
     */
    public function mount(): void
    {
        $this->maintenanceDate = now()->toDateString();

        $this->responsible = Auth::user()?->name ?? '';
    }

    /**
     * HU-09: Registra una intervención técnica.
     */
    public function register(
        MaintenanceRegistrationService $service
    ): void {
        $this->validate([
            'cameraId' => [
                'required',
                'integer',
                Rule::exists('cameras', 'id')
                    ->whereNull('deleted_at'),
            ],
            'maintenanceDate' => [
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
        ]);

        $service->register([
            'camera_id' => (int) $this->cameraId,
            'maintenance_date' => $this->maintenanceDate,
            'type' => $this->type,
            'description' => $this->description,
            'responsible' => $this->responsible,
            'result' => $this->result,
        ]);

        $this->reset(
            'cameraId',
            'type',
            'description',
            'result'
        );

        $this->maintenanceDate = now()->toDateString();

        $this->resetValidation();

        session()->flash(
            'success',
            'Mantenimiento registrado correctamente.'
        );
    }

    /**
     * Presenta el formulario y las cámaras disponibles.
     */
    public function render(): View
    {
        return view('livewire.maintenances.create', [
            'cameras' => Camera::query()
                ->orderBy('codigo_inventario')
                ->get([
                    'id',
                    'codigo_inventario',
                    'ubicacion',
                ]),
        ])->layout('layouts::app', [
            'title' => 'Registrar mantenimiento',
        ]);
    }
}

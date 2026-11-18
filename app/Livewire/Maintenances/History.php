<?php

namespace App\Livewire\Maintenances;

use App\Models\Camera;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * HU-10: consulta de intervenciones de una cámara en la misma base SQLite
 * utilizada por inventario e incidencias. No modifica registros ni estados.
 */
class History extends Component
{
    use WithPagination;

    #[Locked]
    public int $cameraId = 0;

    public function boot(): void
    {
        Gate::authorize('view-cameras');
    }

    public function mount(int $camera): void
    {
        $this->cameraId = Camera::withTrashed()->findOrFail($camera)->id;
    }

    public function render(): View
    {
        $camera = Camera::withTrashed()->findOrFail($this->cameraId);

        return view('livewire.maintenances.history', [
            'camera' => $camera,
            'maintenances' => $camera->maintenances()
                ->with('incident')
                ->orderByDesc('maintenance_date')
                ->orderByDesc('id')
                ->paginate(10),
        ])->layout('layouts::app', ['title' => 'Historial de mantenimientos']);
    }
}

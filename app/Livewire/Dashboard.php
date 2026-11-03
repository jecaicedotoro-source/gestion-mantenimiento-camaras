<?php

namespace App\Livewire;

use App\Models\Camera;
use App\Models\Incident;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/** Consulta de presentación; los indicadores no modifican registros ni eluden permisos. */
class Dashboard extends Component
{
    public function render(): View
    {
        // Una cuenta sin rol puede abrir su panel sin consultar información restringida.
        $canViewCameras = Gate::allows('view-cameras');
        $canViewIncidents = Gate::allows('viewAny', Incident::class);

        return view('dashboard', [
            'cameraCount' => $canViewCameras ? Camera::query()->count() : 0,
            'operativeCameraCount' => $canViewCameras ? Camera::query()->where('estado', 'activo')->count() : 0,
            'pendingIncidentCount' => $canViewIncidents ? Incident::query()->whereNull('confirmed_category')->count() : 0,
            'recentCameras' => $canViewCameras
                ? Camera::query()->latest('id')->limit(5)->get(['id', 'codigo_inventario', 'marca', 'modelo', 'estado'])
                : collect(),
        ])->layout('layouts::app', ['title' => 'Panel de gestión']);
    }
}

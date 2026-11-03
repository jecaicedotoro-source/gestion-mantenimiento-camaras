<?php

use App\Models\Camera;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('view-cameras');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'cameras' => Camera::query()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('codigo_inventario', 'like', '%' . $this->search . '%')
                            ->orWhere('numero_serie', 'like', '%' . $this->search . '%')
                            ->orWhere('ubicacion', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderByDesc('id')
                ->paginate(10),
        ];
    }
};

?>

<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                Inventario de cámaras
            </flux:heading>
            <flux:text>
                Cámaras de seguridad registradas
            </flux:text>
        </div>

        @can('create-cameras')
            <flux:button variant="primary" :href="route('cameras.create')" wire:navigate>
                Registrar cámara
            </flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por código, serie o ubicación" />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Código</th>
                    <th class="p-3">Serie</th>
                    <th class="p-3">Marca</th>
                    <th class="p-3">Modelo</th>
                    <th class="p-3">Ubicación</th>
                    <th class="p-3">Estado</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($cameras as $camera)
                    <tr wire:key="camera-{{ $camera->id }}" class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="p-3">{{ $camera->codigo_inventario }}</td>
                        <td class="p-3">{{ $camera->numero_serie ?? '—' }}</td>
                        <td class="p-3">{{ $camera->marca }}</td>
                        <td class="p-3">{{ $camera->modelo }}</td>
                        <td class="p-3">{{ $camera->ubicacion }}</td>
                        <td class="p-3">{{ $camera->estado }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center">
                            No se encontraron cámaras.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $cameras->links() }}
</div>

<?php

use App\Models\Camera;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function boot(): void
    {
        Gate::authorize('view-cameras');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $this->validate(['search' => ['string', 'max:100']]);

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
    <div class="flex flex-wrap items-center justify-between gap-4">
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

    <flux:input label="Buscar cámaras" wire:model.live.debounce.300ms="search" placeholder="Buscar por código, serie o ubicación" />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Inventario de cámaras registradas en el sistema.</caption>
            <thead class="bg-indigo-50 dark:bg-indigo-950">
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
                        <td class="p-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $camera->estado === 'activo' ? 'bg-teal-100 text-teal-900 dark:bg-teal-950 dark:text-teal-200' : 'bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-200' }}">{{ __($camera->estado) }}</span></td>
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

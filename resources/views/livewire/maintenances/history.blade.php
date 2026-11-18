<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-2">
            <flux:heading size="xl">Historial de mantenimientos</flux:heading>
            <flux:text>Cámara {{ $camera->codigo_inventario }} · {{ $camera->ubicacion }}</flux:text>
        </div>
        <flux:button :href="route('cameras.index')" wire:navigate>Volver al inventario</flux:button>
    </div>

    @if ($camera->trashed())
        <p role="status" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
            Esta cámara está dada de baja. Sus intervenciones históricas permanecen disponibles para consulta.
        </p>
    @endif

    <div class="space-y-4">
        @forelse ($maintenances as $maintenance)
            <article wire:key="maintenance-{{ $maintenance->id }}" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <flux:heading size="lg">Mantenimiento #{{ $maintenance->id }}</flux:heading>
                    <span class="rounded-full px-3 py-1 text-sm font-medium {{ $maintenance->type === 'preventivo' ? 'bg-teal-100 text-teal-900 dark:bg-teal-950 dark:text-teal-100' : 'bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-100' }}">
                        {{ ucfirst($maintenance->type) }}
                    </span>
                </div>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    <strong>Fecha:</strong> {{ $maintenance->maintenance_date->format('d/m/Y') }}
                    · <strong>Responsable:</strong> {{ $maintenance->responsible }}
                </p>
                <div>
                    <p class="text-sm font-semibold">Descripción de la intervención</p>
                    <p class="whitespace-pre-wrap break-words text-sm">{{ $maintenance->description }}</p>
                </div>
                @if ($maintenance->incident_id)
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm dark:border-indigo-800 dark:bg-indigo-950">
                        <p><strong>Incidencia relacionada:</strong> #{{ $maintenance->incident_id }}</p>
                        <p class="mt-1 whitespace-pre-wrap break-words">{{ $maintenance->incident?->description }}</p>
                        <p class="mt-1 font-medium">
                            {{ $maintenance->repair_resolved ? 'Reparación confirmada en esta intervención' : 'La reparación no se confirmó en esta intervención' }}
                        </p>
                        <a class="mt-2 inline-block text-indigo-700 underline dark:text-indigo-200" href="{{ route('incidents.index') }}" wire:navigate>Consultar incidencias</a>
                    </div>
                @endif
                <div>
                    <p class="text-sm font-semibold">Resultado</p>
                    <p class="whitespace-pre-wrap break-words text-sm">{{ $maintenance->result }}</p>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                Esta cámara todavía no tiene mantenimientos registrados.
            </p>
        @endforelse
    </div>

    {{ $maintenances->links() }}
</div>

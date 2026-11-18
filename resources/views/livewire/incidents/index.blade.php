<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div>
        <flux:heading size="xl">Incidencias técnicas</flux:heading>
        <flux:text class="mt-2">Registra la falla y confirma su categoría. La IA solo propone; la decisión es humana.</flux:text>
    </div>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-teal-300 bg-teal-50 p-4 text-teal-900 dark:border-teal-800 dark:bg-teal-950 dark:text-teal-100">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            <p class="font-semibold">Revisa la operación:</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @can('create', \App\Models\Incident::class)
        <form wire:submit="register" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Registrar incidencia</flux:heading>
            <flux:select wire:model="cameraId" label="Cámara afectada" required>
                <flux:select.option value="">Selecciona una cámara</flux:select.option>
                @foreach ($cameras as $camera)
                    <flux:select.option :value="$camera->id">#{{ $camera->id }} · {{ $camera->codigo_inventario }} · {{ $camera->ubicacion }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="description" label="Descripción de la falla" rows="3" maxlength="2000" required />
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Describe síntomas técnicos. Evita nombres, contraseñas, tokens y datos personales. Entre 10 y 2000 caracteres.</p>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="register">Guardar incidencia</flux:button>
        </form>
    @endcan

    @if ($selectedIncidentId !== null)
        <section aria-labelledby="review-heading" class="space-y-4 rounded-2xl border border-indigo-300 bg-indigo-50 p-5 dark:border-indigo-800 dark:bg-indigo-950">
            <h2 id="review-heading" class="text-lg font-semibold">Revisión de incidencia #{{ $selectedIncidentId }}</h2>
            @if ($previewHash !== '')
                <p class="text-sm">Solo se enviará el siguiente texto a {{ config('ai.provider', 'openai') === 'groq' ? 'Groq' : 'OpenAI' }}. Revisa que no contenga información sensible; la minimización automática puede ser incompleta.</p>
                <pre class="whitespace-pre-wrap break-words rounded-lg bg-white p-4 text-sm dark:bg-zinc-900">{{ $previewText }}</pre>
                <flux:checkbox wire:model="shareApproved" label="He revisado el texto y autorizo esta solicitud de clasificación." />
                <flux:button variant="primary" wire:click="classify" wire:loading.attr="disabled" wire:target="classify">Clasificar con IA</flux:button>
                <span wire:loading wire:target="classify" role="status" class="text-sm">Consultando clasificación…</span>
                <p class="text-sm">La solicitud utiliza la cuenta API configurada y puede generar costo. Si falla, puedes continuar con la clasificación manual.</p>
            @endif
            @if ($suggestionId !== null)
                <div class="flex flex-wrap gap-3">
                    <flux:button variant="primary" wire:click="acceptSuggestion" wire:loading.attr="disabled">Aceptar sugerencia</flux:button>
                    <flux:button wire:click="correctClassification">Corregir clasificación</flux:button>
                </div>
            @endif
            <form wire:submit="confirm" class="space-y-3">
                <flux:select wire:model="category" label="Categoría que confirmas" required>
                    <flux:select.option value="">Selecciona una categoría</flux:select.option>
                    @foreach ($categories as $option)<flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>@endforeach
                </flux:select>
                <flux:button type="submit" wire:loading.attr="disabled">Guardar decisión humana</flux:button>
            </form>
        </section>
    @endif

    <div class="space-y-4">
        @forelse ($incidents as $incident)
            @php($suggestion = $incident->classifications->first(fn ($entry) => $entry->kind === 'ia' && $entry->status === 'sugerida'))
            <article wire:key="incident-{{ $incident->id }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h2 class="font-semibold">Incidencia #{{ $incident->id }} · Cámara #{{ $incident->camera_id }} · {{ $incident->camera->codigo_inventario }}</h2>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-950 dark:bg-amber-950 dark:text-amber-100">Clasificación: {{ ucfirst($incident->review_status) }}</span>
                </div>
                <p class="whitespace-pre-wrap break-words text-sm">{{ $incident->description }}</p>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="rounded-full px-3 py-1 font-medium {{ $incident->technical_status === 'corregida' ? 'bg-teal-100 text-teal-900 dark:bg-teal-950 dark:text-teal-100' : 'bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-100' }}">
                        Estado técnico: {{ $incident->technical_status === 'corregida' ? 'Corregida' : 'Pendiente de corrección' }}
                    </span>
                    @if ($incident->technical_status === 'corregida' && $incident->resolved_at)
                        <span>Confirmada por {{ $incident->repairResolver?->name ?? 'usuario autorizado' }} · {{ $incident->resolved_at->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
                @if ($incident->maintenances_count > 0)
                    <p class="text-sm">Intervenciones correctivas asociadas: {{ $incident->maintenances_count }} · <a class="text-indigo-700 underline dark:text-indigo-300" href="{{ route('maintenances.history', ['camera' => $incident->camera_id]) }}" wire:navigate>Ver historial de la cámara</a></p>
                @endif
                @if ($incident->technical_status === 'pendiente' && ! $incident->camera->trashed())
                    @can('create-maintenances')
                        <flux:button size="sm" :href="route('maintenances.create-for-incident', ['incident' => $incident->id])" wire:navigate>Registrar mantenimiento correctivo</flux:button>
                    @endcan
                @endif
                @if ($suggestion)
                    <div class="rounded-lg border-l-4 border-indigo-500 bg-indigo-50 p-3 text-sm dark:bg-indigo-950">
                        <p class="font-semibold">Sugerencia IA: {{ \App\Enums\IncidentCategory::tryFrom($suggestion->suggested_category)?->label() }}</p>
                        <p class="mt-1">{{ $suggestion->explanation }}</p>
                        <p class="mt-1 text-xs">Propuesta de apoyo. Requiere confirmación humana.</p>
                    </div>
                @endif
                @if ($incident->confirmed_at)
                    <p class="text-sm text-teal-800 dark:text-teal-200">Categoría confirmada: <strong>{{ \App\Enums\IncidentCategory::tryFrom($incident->confirmed_category)?->label() }}</strong> · {{ $incident->reviewer?->name }} · {{ $incident->confirmed_at->format('d/m/Y H:i') }}</p>
                @else
                    @can('classify', $incident)
                        <div class="flex flex-wrap gap-3">
                            <flux:button size="sm" wire:click="prepareClassification({{ $incident->id }})">Preparar clasificación IA</flux:button>
                            <flux:button size="sm" wire:click="prepareReview({{ $incident->id }})">{{ $suggestion ? 'Revisar sugerencia' : 'Clasificación manual' }}</flux:button>
                        </div>
                    @endcan
                @endif
                <details class="text-sm text-zinc-600 dark:text-zinc-300">
                    <summary class="cursor-pointer">Trazabilidad ({{ $incident->classifications->count() }} registros)</summary>
                    <ul class="mt-2 space-y-1">
                        @foreach ($incident->classifications as $entry)
                            <li>#{{ $entry->id }} · {{ $entry->kind === 'ia' ? 'Solicitud IA' : 'Decisión humana' }} · {{ $entry->status }} · Responsable #{{ $entry->actor_id }} · {{ $entry->created_at->format('d/m/Y H:i') }}</li>
                        @endforeach
                    </ul>
                </details>
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-600 dark:text-zinc-300">Todavía no hay incidencias registradas.</p>
        @endforelse
    </div>
    {{ $incidents->links() }}
</div>

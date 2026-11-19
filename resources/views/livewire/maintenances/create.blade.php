<div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">

    <div class="space-y-2">
        <flux:heading size="xl">
            Registro de mantenimiento
        </flux:heading>

        <flux:text>
            Registra las intervenciones técnicas realizadas
            a las cámaras de seguridad de la USC.
        </flux:text>
    </div>

    @if ($linkedIncident)
        <section class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 text-indigo-950 dark:border-indigo-800 dark:bg-indigo-950 dark:text-indigo-100" aria-label="Incidencia vinculada">
            <p class="font-semibold">Mantenimiento correctivo de la incidencia #{{ $linkedIncident->id }}</p>
            <p class="mt-2 text-sm">Cámara: {{ $linkedIncident->camera->codigo_inventario }} · {{ $linkedIncident->camera->ubicacion }}</p>
            <p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $linkedIncident->description }}</p>
            <p class="mt-2 text-xs">La incidencia está pendiente de corrección técnica. Esta intervención quedará relacionada con ella, sin depender de la clasificación por IA.</p>
        </section>
    @endif

    @if (session('success'))
        <div role="status"
            class="rounded-xl border border-teal-300 bg-teal-50 p-4 text-teal-900 dark:border-teal-800 dark:bg-teal-950 dark:text-teal-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
            class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            <p class="font-semibold">
                Revisa los datos del mantenimiento:
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($cameras->isEmpty())
        <div
            class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
            No hay cámaras disponibles para registrar
            mantenimientos. Primero debe existir una
            cámara activa en el inventario.
        </div>
    @else
        <form wire:submit="register"
            class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="space-y-4">
                <flux:heading size="lg">
                    Información de la intervención
                </flux:heading>

                @if ($linkedIncident)
                    <p class="text-sm"><strong>Cámara intervenida:</strong> {{ $linkedIncident->camera->codigo_inventario }}</p>
                @else
                    <flux:select wire:model="cameraId" label="Cámara intervenida" required>
                        <flux:select.option value="">Selecciona una cámara</flux:select.option>
                        @foreach ($cameras as $camera)
                            <flux:select.option :value="$camera->id">
                                {{ $camera->codigo_inventario }} · {{ $camera->ubicacion }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:input wire:model="maintenanceDate" type="date" label="Fecha del mantenimiento" required />

                @if ($linkedIncident)
                    <p class="text-sm"><strong>Tipo de mantenimiento:</strong> Correctivo</p>
                @else
                    <flux:select wire:model="type" label="Tipo de mantenimiento" required>
                        <flux:select.option value="">Selecciona un tipo</flux:select.option>
                        <flux:select.option value="preventivo">Preventivo</flux:select.option>
                        <flux:select.option value="correctivo">Correctivo</flux:select.option>
                    </flux:select>
                @endif

                <flux:textarea wire:model="description" label="Descripción de la intervención" rows="4"
                    maxlength="2000" placeholder="Describe las actividades técnicas realizadas." required />

                <flux:input wire:model="responsible" label="Responsable del mantenimiento" maxlength="150" required />

                <flux:textarea wire:model="result" label="Resultado de la intervención" rows="3" maxlength="2000"
                    placeholder="Describe el estado final de la cámara." required />

                @if ($linkedIncident)
                    <div class="space-y-2 rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                        <p class="text-sm font-semibold">Confirmación humana del resultado</p>
                        <flux:select wire:model="repairOutcome" label="¿La intervención corrigió realmente la incidencia?" required>
                            <flux:select.option value="">Selecciona una respuesta</flux:select.option>
                            <flux:select.option value="si">Sí, el fallo quedó corregido</flux:select.option>
                            <flux:select.option value="no">No, la incidencia sigue pendiente</flux:select.option>
                        </flux:select>
                        <p class="text-xs">Seleccionar «No» registra el trabajo sin cerrar la incidencia. Solo «Sí» confirma su corrección técnica.</p>
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="register">
                    Guardar mantenimiento
                </flux:button>

                <span wire:loading wire:target="register" role="status"
                    class="text-sm text-zinc-600 dark:text-zinc-300">
                    Guardando mantenimiento...
                </span>
            </div>
        </form>
    @endif

    <p class="text-sm text-zinc-600 dark:text-zinc-300">
        HU-09/HU-12 · Las intervenciones se almacenan en SQLite. Una reparación solo se considera corregida con confirmación humana.
    </p>
</div>

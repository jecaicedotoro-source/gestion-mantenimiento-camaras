<div class="mx-auto flex w-full max-w-7xl flex-col gap-8">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold tracking-widest text-indigo-600 uppercase dark:text-indigo-300">Ciudadela Pampalinda</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Panel de gestión</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">Consulta el inventario y continúa con las tareas de tu perfil institucional.</p>
        </div>
        @can('create-cameras')
            <flux:button :href="route('cameras.create')" variant="primary" icon="plus" wire:navigate>Registrar cámara</flux:button>
        @endcan
    </header>

    {{-- Los indicadores se calculan en el componente y respetan la autorización. --}}
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Resumen del inventario">
        @can('view-cameras')
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-600 dark:text-slate-300">Cámaras registradas</h2>
                    <span class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300"><flux:icon.video-camera class="size-5" /></span>
                </div>
                <p class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($cameraCount, 0, ',', '.') }}</p>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Registros actuales del inventario.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-600 dark:text-slate-300">Cámaras activas</h2>
                    <span class="rounded-xl bg-teal-50 p-2.5 text-teal-700 dark:bg-teal-950 dark:text-teal-300"><flux:icon.check-circle class="size-5" /></span>
                </div>
                <p class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($operativeCameraCount, 0, ',', '.') }}</p>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Según el estado registrado en el sistema.</p>
            </article>
        @endcan
        @can('viewAny', \App\Models\Incident::class)
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-600 dark:text-slate-300">Clasificaciones pendientes</h2>
                    <span class="rounded-xl bg-amber-50 p-2.5 text-amber-700 dark:bg-amber-950 dark:text-amber-300"><flux:icon.clipboard-document-list class="size-5" /></span>
                </div>
                <p class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($pendingIncidentCount, 0, ',', '.') }}</p>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Incidencias sin una categoría confirmada.</p>
            </article>
        @endcan
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-zinc-800">
                <h2 class="font-semibold text-slate-900 dark:text-white">Últimas cámaras registradas</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Los cinco registros más recientes del inventario.</p>
            </div>
            @can('view-cameras')
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Código de inventario y estado de las últimas cámaras</caption>
                        <thead class="bg-slate-50 text-xs text-slate-600 dark:bg-zinc-800 dark:text-slate-300"><tr><th scope="col" class="px-6 py-3 font-medium">Inventario</th><th scope="col" class="px-6 py-3 font-medium">Marca y modelo</th><th scope="col" class="px-6 py-3 font-medium">Estado</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                            @forelse ($recentCameras as $camera)
                                <tr><td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ $camera->codigo_inventario }}</td><td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $camera->marca }} {{ $camera->modelo }}</td><td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ __($camera->estado) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Todavía no hay cámaras registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800"><flux:link :href="route('cameras.index')" wire:navigate>Consultar inventario completo</flux:link></div>
            @else
                <p class="px-6 py-8 text-sm text-slate-600 dark:text-slate-300">Tu cuenta aún no tiene acceso al inventario. Solicita la asignación del perfil correspondiente al administrador.</p>
            @endcan
        </article>
        <article class="flex flex-col rounded-2xl bg-brand-navy p-6 text-white">
            <span class="mb-5 w-fit rounded-xl bg-white/10 p-3 text-teal-200"><flux:icon.shield-check class="size-6" /></span>
            <h2 class="text-xl font-semibold">Información técnica, decisiones humanas</h2>
            <p class="mt-3 text-sm leading-6 text-blue-100">El inventario conserva la información de cada cámara. En las incidencias, una sugerencia de IA requiere tu revisión antes de confirmar una categoría.</p>
            <div class="mt-6 flex flex-wrap gap-2 text-xs"><span class="rounded-full bg-teal-300/15 px-3 py-1.5 text-teal-100">Acceso por perfil</span><span class="rounded-full bg-brand-coral/15 px-3 py-1.5 text-rose-100">Revisión humana</span></div>
            @can('viewAny', \App\Models\Incident::class)
                @if (Route::has('incidents.index'))
                    <a href="{{ route('incidents.index') }}" wire:navigate class="mt-auto pt-8 text-sm font-semibold text-white underline decoration-teal-300 underline-offset-4">Consultar incidencias <span aria-hidden="true">→</span></a>
                @endif
            @endcan
        </article>
    </section>
</div>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Inventario y mantenimiento de cámaras'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-zinc-950 dark:text-white">
    <a href="#contenido" class="sr-only rounded-lg bg-white p-3 text-indigo-700 focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50">Saltar al contenido</a>
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-5 sm:px-8">
        <header class="flex flex-wrap items-center justify-between gap-6 py-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Inicio de Gestión de cámaras">
                <span class="rounded-xl bg-brand-indigo p-3 text-white"><flux:icon.video-camera class="size-6" /></span>
                <span class="text-sm font-semibold leading-5">Gestión de cámaras<span class="block text-xs font-normal text-slate-500 dark:text-slate-400">Ciudadela Pampalinda</span></span>
            </a>
            <nav aria-label="Acceso" class="flex items-center gap-4">
                @auth
                    <flux:button :href="route('dashboard')" variant="primary" wire:navigate>Abrir panel</flux:button>
                @else
                    <flux:button :href="route('login')" variant="primary" wire:navigate>Iniciar sesión</flux:button>
                @endauth
            </nav>
        </header>
        <main id="contenido" class="flex flex-1 flex-col justify-center py-10 sm:py-16">
            <div class="grid items-center gap-12 lg:grid-cols-[1.25fr_1fr]">
                <section>
                    <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-800 dark:border-teal-900 dark:bg-teal-950 dark:text-teal-200"><span class="size-1.5 rounded-full bg-brand-teal" aria-hidden="true"></span>Universidad Santiago de Cali</p>
                    <h1 class="max-w-2xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">Un inventario claro.<br><span class="text-indigo-600 dark:text-indigo-300">Un mantenimiento con trazabilidad.</span></h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">Sistema web para organizar la información técnica de las cámaras de seguridad de la Ciudadela Pampalinda y apoyar la gestión de sus incidencias.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        @auth
                            <flux:button :href="route('dashboard')" variant="primary" icon-trailing="arrow-right" wire:navigate>Ir al panel de gestión</flux:button>
                        @else
                            <flux:button :href="route('login')" variant="primary" icon-trailing="arrow-right" wire:navigate>Acceder al sistema</flux:button>
                        @endauth
                        <a href="https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras#readme" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-4 dark:text-slate-300">Documentación del proyecto <span class="sr-only">(abre una pestaña nueva)</span></a>
                    </div>
                    <p class="mt-6 text-xs leading-5 text-slate-500 dark:text-slate-400">El acceso requiere una cuenta habilitada por el administrador.</p>
                </section>
                <aside class="relative overflow-hidden rounded-3xl bg-brand-navy p-7 text-white sm:p-9" aria-label="Áreas de trabajo">
                    <div class="absolute -top-16 -right-16 size-48 rounded-full border-[30px] border-white/5" aria-hidden="true"></div>
                    <p class="relative mb-8 text-xs font-medium tracking-widest text-blue-200 uppercase">Información centralizada</p>
                    <div class="relative space-y-7">
                        <div class="flex gap-4"><span class="h-fit rounded-xl bg-indigo-400/20 p-3 text-indigo-100"><flux:icon.video-camera class="size-5" /></span><div><h2 class="font-semibold">Inventario de cámaras</h2><p class="mt-1 text-sm leading-6 text-blue-100">Identificación, características y estado en un mismo lugar.</p></div></div>
                        <div class="flex gap-4"><span class="h-fit rounded-xl bg-teal-300/15 p-3 text-teal-200"><flux:icon.clipboard-document-list class="size-5" /></span><div><h2 class="font-semibold">Incidencias técnicas</h2><p class="mt-1 text-sm leading-6 text-blue-100">Registro de novedades y clasificación con revisión humana.</p></div></div>
                        <div class="flex gap-4"><span class="h-fit rounded-xl bg-brand-coral/15 p-3 text-rose-200"><flux:icon.shield-check class="size-5" /></span><div><h2 class="font-semibold">Acceso responsable</h2><p class="mt-1 text-sm leading-6 text-blue-100">Funciones disponibles según el perfil de cada usuario.</p></div></div>
                    </div>
                </aside>
            </div>
        </main>
        <footer class="flex flex-wrap justify-between gap-3 border-t border-slate-200 py-6 text-xs text-slate-500 dark:border-zinc-800 dark:text-slate-400"><p>Proyecto académico · Inventario y mantenimiento de cámaras</p><p>Ciudadela Pampalinda · Santiago de Cali</p></footer>
    </div>
    @fluxScripts
</body>
</html>

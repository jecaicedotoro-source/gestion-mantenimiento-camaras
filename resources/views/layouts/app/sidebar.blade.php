<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-slate-50 antialiased dark:bg-zinc-950">
    <a href="#contenido-principal" class="sr-only rounded-lg bg-white p-3 text-indigo-700 focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50">Saltar al contenido</a>
    <flux:sidebar sticky collapsible="mobile" class="institutional-sidebar dark border-e border-blue-950 bg-brand-navy text-white">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" aria-label="Cerrar navegación" />
        </flux:sidebar.header>
        <p class="px-2 pb-2 text-xs leading-5 text-blue-200">Inventario y mantenimiento<br>Ciudadela Pampalinda</p>
        <flux:sidebar.nav aria-label="Navegación principal">
            <flux:sidebar.group heading="Gestión" class="grid">
                <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Panel de gestión</flux:sidebar.item>
                @can('view-cameras')
                    <flux:sidebar.item icon="video-camera" :href="route('cameras.index')" :current="request()->routeIs('cameras.index')" wire:navigate>Inventario de cámaras</flux:sidebar.item>
                @endcan
                @can('create-cameras')
                    <flux:sidebar.item icon="plus-circle" :href="route('cameras.create')" :current="request()->routeIs('cameras.create')" wire:navigate>Registrar cámara</flux:sidebar.item>
                @endcan
                @can('viewAny', \App\Models\Incident::class)
                    @if (Route::has('incidents.index'))
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('incidents.index')" :current="request()->routeIs('incidents.*')" wire:navigate>Incidencias</flux:sidebar.item>
                    @endif
                @endcan
            </flux:sidebar.group>
            @can('manage-users')
                <flux:sidebar.group heading="Administración" class="mt-5 grid">
                    <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>Usuarios</flux:sidebar.item>
                </flux:sidebar.group>
            @endcan
        </flux:sidebar.nav>
        <flux:spacer />
        <flux:sidebar.nav aria-label="Recursos del proyecto">
            <flux:sidebar.item icon="folder-git-2" href="https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras" target="_blank" rel="noopener noreferrer">Repositorio <span class="sr-only">(abre una pestaña nueva)</span></flux:sidebar.item>
            <flux:sidebar.item icon="book-open-text" href="https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras#readme" target="_blank" rel="noopener noreferrer">Documentación <span class="sr-only">(abre una pestaña nueva)</span></flux:sidebar.item>
        </flux:sidebar.nav>
        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>
    <flux:header class="border-b border-slate-200 bg-white lg:hidden dark:border-zinc-800 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Abrir navegación" />
        <flux:spacer />
        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" aria-label="Menú de cuenta" />
            <flux:menu>
                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                    <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                    <div class="grid min-w-0 flex-1 text-start text-sm leading-tight"><flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading><flux:text class="truncate">{{ auth()->user()->email }}</flux:text></div>
                </div>
                <flux:menu.separator />
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Configuración</flux:menu.item>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">Cerrar sesión</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>
    <div id="contenido-principal" class="contents">{{ $slot }}</div>
    @persist('toast')
        <flux:toast.group><flux:toast /></flux:toast.group>
    @endpersist
    @fluxScripts
</body>
</html>

@props(['sidebar' => false])

{{-- Símbolo funcional genérico; no representa un escudo ni logotipo oficial. --}}
<a {{ $attributes->class('flex min-w-0 items-center gap-3 rounded-lg') }} aria-label="Panel de Gestión de cámaras">
    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-indigo text-white"><flux:icon.video-camera class="size-5" /></span>
    <span class="min-w-0 text-sm font-semibold leading-tight {{ $sidebar ? 'text-white' : 'text-slate-900 dark:text-white' }}">Gestión de cámaras<span class="mt-1 block text-xs font-normal {{ $sidebar ? 'text-blue-200' : 'text-slate-500 dark:text-slate-400' }}">Ciudadela Pampalinda</span></span>
</a>

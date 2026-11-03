<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main id="contenido-principal" tabindex="-1" class="min-w-0">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

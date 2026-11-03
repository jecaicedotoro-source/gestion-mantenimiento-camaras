<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>Desactivar cuenta</flux:heading>
        <flux:subheading>Tu identidad y tus historiales se conservarán.</flux:subheading>
    </div>

    @if (auth()->user()->role?->code === 'administrador')
        <p class="text-sm text-amber-700 dark:text-amber-300">Por seguridad, un administrador no puede desactivar su propia cuenta. Solicita la gestión a otro administrador.</p>
    @else
    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" data-test="delete-user-button">
            Desactivar cuenta
        </flux:button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
    @endif
</section>

<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Conservar la identidad al cerrar voluntariamente una cuenta no administrativa.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        app(UserManagementService::class)->deactivateOwn(Auth::user());
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">¿Quieres desactivar tu cuenta?</flux:heading>

            <flux:subheading>
                Se conservarán tu identidad y tus historiales. Perderás el acceso hasta que un administrador reactive la cuenta. Introduce tu contraseña para confirmar.
            </flux:subheading>
        </div>

        <flux:input wire:model="password" label="Contraseña" type="password" viewable />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                Desactivar cuenta
            </flux:button>
        </div>
    </form>
</flux:modal>

<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Estado de la interfaz
    public bool $showForm = false;
    public string $search = '';

    // Identificadores protegidos contra modificaciones del navegador
    #[Locked]
    public ?int $editingUserId = null;

    #[Locked]
    public ?int $deletingUserId = null;

    public string $deletingUserName = '';

    // Campos del formulario
    public string $newName = '';
    public string $newEmail = '';
    public string $newRoleId = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    /**
     * Autorizar todas las solicitudes del componente.
     */
    public function boot(): void
    {
        Gate::authorize('manage-users');
    }

    /**
     * Consultar usuarios con búsqueda y paginación.
     */
    #[Computed]
    public function users()
    {
        $search = trim($this->search);

        return User::query()
            ->with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    /**
     * Obtener roles institucionales.
     */
    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * Reiniciar la paginación al buscar.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reglas de validación para crear y editar.
     */
    protected function rules(): array
    {
        $uniqueEmail = Rule::unique('users', 'email');

        if ($this->editingUserId !== null) {
            $uniqueEmail->ignore($this->editingUserId);
        }

        $passwordRule = Password::min(12)->mixedCase()->numbers()->symbols();

        return [
            'newName' => ['required', 'string', 'min:3', 'max:255'],

            'newEmail' => ['required', 'email:rfc', 'max:255', $uniqueEmail],

            'newRoleId' => ['required', 'integer', Rule::exists('roles', 'id')],

            'newPassword' => $this->editingUserId === null ? ['required', 'string', $passwordRule] : ['nullable', 'string', $passwordRule],

            'newPasswordConfirmation' => $this->editingUserId === null ? ['required', 'same:newPassword'] : ['nullable', 'required_with:newPassword', 'same:newPassword'],
        ];
    }

    /**
     * Preparar formulario de creación.
     */
    public function openCreateForm(): void
    {
        Gate::authorize('manage-users');

        $this->cancelDelete();

        $this->reset('newName', 'newEmail', 'newRoleId', 'newPassword', 'newPasswordConfirmation', 'editingUserId');

        $this->resetValidation();

        $this->showForm = true;
    }

    /**
     * Cargar usuario para edición.
     */
    public function editUser(int $id): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($id);

        $this->cancelDelete();
        $this->resetValidation();

        $this->editingUserId = $user->id;

        $this->newName = $user->name;
        $this->newEmail = $user->email;
        $this->newRoleId = (string) ($user->role_id ?? '');

        // Nunca mostrar la contraseña almacenada.
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        $this->showForm = true;
    }

    /**
     * Cancelar creación o edición.
     */
    public function cancelForm(): void
    {
        $this->reset('showForm', 'editingUserId', 'newName', 'newEmail', 'newRoleId', 'newPassword', 'newPasswordConfirmation');

        $this->resetValidation();
    }

    /**
     * Validar datos sin guardarlos.
     */
    public function validateNewUser(): void
    {
        Gate::authorize('manage-users');

        $this->normalizeForm();

        $this->validate();

        session()->flash('validated', 'Los datos cumplen las reglas de validación.');
    }

    /**
     * Normalizar nombre y correo.
     */
    protected function normalizeForm(): void
    {
        $this->newName = trim($this->newName);

        $this->newEmail = strtolower(trim($this->newEmail));
    }

    /**
     * Decidir si se crea o actualiza un usuario.
     */
    public function saveUser(): void
    {
        Gate::authorize('manage-users');

        if ($this->editingUserId === null) {
            $this->createUser();
        } else {
            $this->updateUser();
        }
    }

    /**
     * Registrar un nuevo usuario institucional.
     */
    public function createUser(): void
    {
        Gate::authorize('manage-users');

        if ($this->editingUserId !== null) {
            abort(409, 'El formulario está en modo edición.');
        }

        $this->normalizeForm();

        $data = $this->validate();

        $user = new User();

        $user->name = $data['newName'];
        $user->email = $data['newEmail'];
        $user->role_id = (int) $data['newRoleId'];

        // El modelo User aplica automáticamente el hash.
        $user->password = $data['newPassword'];

        $user->saveOrFail();

        // Activar el proceso estándar de verificación.
        event(new Registered($user));

        $this->finishForm('Usuario institucional registrado correctamente.');
    }

    /**
     * Actualizar un usuario existente.
     */
    public function updateUser(): void
    {
        Gate::authorize('manage-users');

        if ($this->editingUserId === null) {
            abort(409, 'No hay un usuario seleccionado.');
        }

        $user = User::with('role')->findOrFail($this->editingUserId);

        $this->normalizeForm();

        $data = $this->validate();

        $newRoleId = (int) $data['newRoleId'];

        // No permitir que un administrador cambie su propio rol.
        if ((int) $user->id === (int) Auth::id() && (int) $user->role_id !== $newRoleId) {
            $this->addError('newRoleId', 'No puedes modificar tu propio rol institucional.');

            return;
        }

        // Evitar dejar el sistema sin administradores.
        if ($user->role?->code === 'administrador' && (int) $user->role_id !== $newRoleId && User::where('role_id', $user->role_id)->count() <= 1) {
            $this->addError('newRoleId', 'Debe permanecer al menos un administrador.');

            return;
        }

        $emailChanged = $user->email !== $data['newEmail'];

        $user->name = $data['newName'];
        $user->email = $data['newEmail'];
        $user->role_id = $newRoleId;

        // Cambiar contraseña solo si se proporciona una nueva.
        if (!empty($data['newPassword'])) {
            $user->password = $data['newPassword'];
        }

        // Un correo modificado debe verificarse nuevamente.
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->saveOrFail();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->finishForm('Usuario institucional actualizado correctamente.');
    }

    /**
     * Finalizar creación o edición.
     */
    protected function finishForm(string $message): void
    {
        $this->cancelForm();

        $this->resetPage();

        session()->flash('success', $message);
    }

    /**
     * Comprobar si un usuario puede eliminarse.
     */
    protected function deletionError(User $user): ?string
    {
        if ((int) $user->id === (int) Auth::id()) {
            return 'No puedes eliminar tu propia cuenta.';
        }

        if ($user->role?->code === 'administrador' && User::where('role_id', $user->role_id)->count() <= 1) {
            return 'No puedes eliminar al último administrador.';
        }

        return null;
    }

    /**
     * Solicitar confirmación de eliminación.
     */
    public function confirmDelete(int $id): void
    {
        Gate::authorize('manage-users');

        $this->resetValidation();

        $user = User::with('role')->findOrFail($id);

        $error = $this->deletionError($user);

        if ($error !== null) {
            $this->addError('operation', $error);
            return;
        }

        $this->cancelForm();

        $this->deletingUserId = $user->id;
        $this->deletingUserName = $user->name;
    }

    /**
     * Cancelar eliminación.
     */
    public function cancelDelete(): void
    {
        $this->reset('deletingUserId', 'deletingUserName');

        $this->resetValidation();
    }

    /**
     * Eliminar usuario tras confirmar.
     */
    public function deleteUser(): void
    {
        Gate::authorize('manage-users');

        if ($this->deletingUserId === null) {
            abort(409, 'No hay un usuario seleccionado.');
        }

        $user = User::with('role')->findOrFail($this->deletingUserId);

        // Revalidar las restricciones antes de eliminar.
        $error = $this->deletionError($user);

        if ($error !== null) {
            $this->addError('operation', $error);
            return;
        }

        $user->delete();

        $this->cancelDelete();

        $this->resetPage();

        session()->flash('success', 'Usuario institucional eliminado correctamente.');
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6 p-6">

    {{-- ENCABEZADO --}}

    <div class="flex flex-wrap items-center justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold">
                Administración de usuarios
            </h1>

            <p class="mt-2 text-sm text-zinc-500">
                Sistema de inventario y mantenimiento de cámaras USC.
            </p>
        </div>

        <flux:button variant="primary" wire:click="openCreateForm">
            + Nuevo usuario
        </flux:button>

    </div>


    {{-- MENSAJES DEL SISTEMA --}}

    @if (session()->has('success'))
        <div role="status"
            class="rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('validated'))
        <div role="status"
            class="rounded-lg border border-blue-300 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300">
            {{ session('validated') }}
        </div>
    @endif

    @error('operation')
        <div role="alert" class="rounded-lg border border-red-300 p-4 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror


    {{-- FORMULARIO CREAR / EDITAR --}}

    @if ($showForm)

        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">

            <h2 class="text-lg font-semibold">
                {{ $editingUserId === null ? 'Registrar usuario institucional' : 'Editar usuario institucional' }}
            </h2>

            <p class="mt-2 text-sm text-zinc-500">
                {{ $editingUserId === null
                    ? 'Completa los datos para crear una nueva cuenta.'
                    : 'Actualiza la información del usuario seleccionado.' }}
            </p>

            <form wire:submit="saveUser" class="mt-6 space-y-4">

                {{-- NOMBRE --}}

                <flux:input wire:model="newName" label="Nombre completo" placeholder="Nombre y apellidos"
                    type="text" />

                {{-- CORREO --}}

                <flux:input wire:model="newEmail" label="Correo electrónico" placeholder="usuario@universidad.edu.co"
                    type="email" />

                {{-- ROL --}}

                <flux:select wire:model="newRoleId" label="Rol institucional" placeholder="Selecciona un rol">

                    @foreach ($this->roles as $role)
                        <flux:select.option :value="$role->id">
                            {{ $role->name }}
                        </flux:select.option>
                    @endforeach

                </flux:select>

                @if ($editingUserId !== null && (int) $editingUserId === (int) auth()->id())
                    <p class="text-xs text-amber-600">
                        Por seguridad, no puedes cambiar tu propio rol.
                    </p>
                @endif

                {{-- CONTRASEÑA --}}

                <flux:input wire:model="newPassword"
                    label="{{ $editingUserId === null ? 'Contraseña' : 'Nueva contraseña (opcional)' }}" type="password"
                    autocomplete="new-password" placeholder="Mínimo 12 caracteres" viewable />

                <flux:input wire:model="newPasswordConfirmation" label="Confirmar contraseña" type="password"
                    autocomplete="new-password" placeholder="Repite la contraseña" viewable />

                @if ($editingUserId !== null)
                    <p class="text-xs text-zinc-500">
                        Deja los campos de contraseña vacíos
                        para conservar la contraseña actual.
                    </p>
                @endif

                {{-- ERRORES DE VALIDACIÓN --}}

                @if ($errors->any())

                    <div role="alert"
                        class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400">
                        <p class="font-semibold">
                            Corrige los siguientes errores:
                        </p>

                        <ul class="mt-2 list-disc space-y-1 pl-5">

                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach

                        </ul>
                    </div>

                @endif

                {{-- ACCIONES DEL FORMULARIO --}}

                <div class="flex flex-wrap gap-3 pt-4">

                    <flux:button type="button" wire:click="validateNewUser">
                        Validar datos
                    </flux:button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveUser">
                        {{ $editingUserId === null ? 'Guardar usuario' : 'Guardar cambios' }}
                    </flux:button>

                    <flux:button type="button" variant="ghost" wire:click="cancelForm">
                        Cancelar
                    </flux:button>

                </div>

            </form>

        </div>

    @endif


    {{-- CONFIRMACIÓN DE ELIMINACIÓN --}}

    @if ($deletingUserId !== null)
        <div class="rounded-xl border border-red-300 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/20">

            <h2 class="font-semibold text-red-700 dark:text-red-400">
                Confirmar eliminación
            </h2>

            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                ¿Estás seguro de eliminar al usuario
                <strong>{{ $deletingUserName }}</strong>?
            </p>

            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                Esta operación elimina la cuenta de la base de datos.
                No se puede deshacer desde esta pantalla.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">

                <flux:button variant="danger" wire:click="deleteUser" wire:loading.attr="disabled"
                    wire:target="deleteUser">
                    Sí, eliminar usuario
                </flux:button>

                <flux:button variant="ghost" wire:click="cancelDelete">
                    Cancelar
                </flux:button>

            </div>

        </div>
    @endif


    {{-- BÚSQUEDA --}}

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

        <flux:input wire:model.live.debounce.400ms="search" label="Buscar usuarios"
            placeholder="Buscar por nombre o correo electrónico" type="search" />

    </div>


    {{-- TABLA DE USUARIOS --}}

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">

        <table class="w-full text-left text-sm">

            <thead class="bg-zinc-100 dark:bg-zinc-800">

                <tr>
                    <th class="p-3">Nombre</th>
                    <th class="p-3">Correo electrónico</th>
                    <th class="p-3">Rol</th>
                    <th class="p-3">Verificación</th>
                    <th class="p-3">Acciones</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($this->users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="border-t border-zinc-200 dark:border-zinc-700">

                        <td class="p-3 font-medium">
                            {{ $user->name }}
                        </td>

                        <td class="p-3">
                            {{ $user->email }}
                        </td>

                        <td class="p-3">
                            {{ $user->role?->name ?? 'Sin rol' }}
                        </td>

                        <td class="p-3">

                            @if ($user->hasVerifiedEmail())
                                <span class="text-green-600 dark:text-green-400">
                                    Verificado
                                </span>
                            @else
                                <span class="text-amber-600 dark:text-amber-400">
                                    Pendiente
                                </span>
                            @endif

                        </td>

                        <td class="p-3">

                            <div class="flex flex-wrap items-center gap-2">

                                <flux:button size="sm" wire:click="editUser({{ $user->id }})">
                                    Editar
                                </flux:button>

                                @if ((int) $user->id !== (int) auth()->id())
                                    <flux:button size="sm" variant="danger"
                                        wire:click="confirmDelete({{ $user->id }})">
                                        Eliminar
                                    </flux:button>
                                @else
                                    <span class="text-xs text-zinc-500">
                                        Tu cuenta
                                    </span>
                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="p-6 text-center text-zinc-500">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                @endforelse

            </tbody>

        </table>

    </div>


    {{-- PAGINACIÓN --}}

    <div>
        {{ $this->users->links() }}
    </div>

</div>

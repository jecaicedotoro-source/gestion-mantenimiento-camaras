<?php

use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $showForm = false;
    public string $search = '';
    #[Locked]
    public ?int $editingUserId = null;
    #[Locked]
    public ?int $deletingUserId = null;
    public string $deletingUserName = '';
    public string $newName = '';
    public string $newEmail = '';
    public string $newRoleId = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    /** La autorización se revalida en cada solicitud Livewire. */
    public function boot(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    #[Computed]
    public function users()
    {
        $search = trim($this->search);

        return User::query()->with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
                });
            })->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        Gate::authorize('create', User::class);
        $this->cancelDelete();
        $this->cancelForm();
        $this->showForm = true;
    }

    public function editUser(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);
        $this->cancelDelete();
        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->newName = $user->name;
        $this->newEmail = $user->email;
        $this->newRoleId = (string) ($user->role_id ?? '');
        // Nunca trasladar el hash a una propiedad visible del navegador.
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset('showForm', 'editingUserId', 'newName', 'newEmail', 'newRoleId', 'newPassword', 'newPasswordConfirmation');
        $this->resetValidation();
    }

    public function validateNewUser(): void
    {
        $this->manage(fn () => app(UserManagementService::class)->validate(
            Auth::user(), $this->payload(), $this->editingUserId === null ? null : User::findOrFail($this->editingUserId),
        ));
        session()->flash('validated', 'Los datos cumplen las reglas de validación.');
    }

    public function saveUser(): void
    {
        $this->editingUserId === null ? $this->createUser() : $this->updateUser();
    }

    public function createUser(): void
    {
        abort_if($this->editingUserId !== null, 409, 'El formulario está en modo edición.');
        $this->manage(fn () => app(UserManagementService::class)->create(Auth::user(), $this->payload()));
        $this->finishForm('Usuario institucional registrado correctamente.');
    }

    public function updateUser(): void
    {
        abort_if($this->editingUserId === null, 409, 'No hay un usuario seleccionado.');
        $this->manage(fn () => app(UserManagementService::class)->update(
            Auth::user(), User::findOrFail($this->editingUserId), $this->payload(),
        ));
        $this->finishForm('Usuario institucional actualizado correctamente.');
    }

    /** Se conserva el nombre de la acción existente; ahora solicita desactivación. */
    public function confirmDelete(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('deactivate', $user);
        $this->cancelForm();
        $this->deletingUserId = $user->id;
        $this->deletingUserName = $user->name;
    }

    public function cancelDelete(): void
    {
        $this->reset('deletingUserId', 'deletingUserName');
        $this->resetValidation();
    }

    /** La identidad y sus relaciones se conservan; el servicio revoca accesos. */
    public function deleteUser(): void
    {
        abort_if($this->deletingUserId === null, 409, 'No hay un usuario seleccionado.');
        $this->manage(fn () => app(UserManagementService::class)->deactivate(Auth::user(), User::findOrFail($this->deletingUserId)));
        $this->cancelDelete();
        $this->resetPage();
        session()->flash('success', 'Cuenta desactivada. Se conservan su identidad y sus historiales.');
    }

    public function reactivateUser(int $id): void
    {
        $this->manage(fn () => app(UserManagementService::class)->reactivate(Auth::user(), User::findOrFail($id)));
        session()->flash('success', 'Cuenta reactivada. El usuario debe iniciar sesión nuevamente.');
    }

    private function finishForm(string $message): void
    {
        $this->cancelForm();
        $this->resetPage();
        session()->flash('success', $message);
    }

    /** Adaptar únicamente los campos de presentación al contrato del servicio. */
    private function payload(): array
    {
        return [
            'name' => $this->newName,
            'email' => $this->newEmail,
            'role_id' => $this->newRoleId,
            'password' => $this->newPassword === '' && $this->editingUserId !== null ? null : $this->newPassword,
            'password_confirmation' => $this->newPasswordConfirmation,
        ];
    }

    /** Mantener los errores de aplicación asociados al formulario visible. */
    private function manage(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (ValidationException $exception) {
            $fields = ['name' => 'newName', 'email' => 'newEmail', 'role_id' => 'newRoleId', 'password' => 'newPassword'];
            $errors = [];
            foreach ($exception->errors() as $key => $messages) {
                $errors[$fields[$key] ?? $key] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }
}; ?>
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


    {{-- CONFIRMACIÓN DE DESACTIVACIÓN --}}

    @if ($deletingUserId !== null)
        <div class="rounded-xl border border-red-300 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/20">

            <h2 class="font-semibold text-red-700 dark:text-red-400">
                Confirmar desactivación
            </h2>

            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                ¿Estás seguro de desactivar al usuario
                <strong>{{ $deletingUserName }}</strong>?
            </p>

            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                La cuenta conservará su identidad y su historial.
                Se revocarán las sesiones y los tokens. Puedes reactivarla posteriormente.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">

                <flux:button variant="danger" wire:click="deleteUser" wire:loading.attr="disabled"
                    wire:target="deleteUser">
                    Sí, desactivar usuario
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
                    <th class="p-3">Verificación</th><th class="p-3">Estado</th>
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
                            <span class="{{ $user->is_active ? 'text-teal-700 dark:text-teal-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $user->is_active ? 'Activo' : 'Desactivado' }}</span>
                        </td>

                        <td class="p-3">

                            <div class="flex flex-wrap items-center gap-2">

                                <flux:button size="sm" wire:click="editUser({{ $user->id }})">
                                    Editar
                                </flux:button>

                                @if ((int) $user->id !== (int) auth()->id())
                                    @if ($user->is_active)
                                        <flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $user->id }})">Desactivar</flux:button>
                                    @else
                                        <flux:button size="sm" wire:click="reactivateUser({{ $user->id }})" wire:loading.attr="disabled">Reactivar</flux:button>
                                    @endif
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
                        <td colspan="6" class="p-6 text-center text-zinc-500">
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



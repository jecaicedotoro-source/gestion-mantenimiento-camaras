<?php

use App\Services\CameraRegistrationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public string $codigo_inventario = '';
    public string $numero_serie = '';
    public string $marca = '';
    public string $modelo = '';
    public string $tipo = '';
    public string $direccion_ip = '';
    public string $ubicacion = '';
    public string $estado = 'activo';
    public string $observaciones = '';

    public function mount(): void
    {
        Gate::authorize('create-cameras');
    }

    public function save(CameraRegistrationService $service): void
    {
        Gate::authorize('create-cameras');

        $service->register([
            'codigo_inventario' => $this->codigo_inventario,
            'numero_serie' => $this->numero_serie ?: null,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'tipo' => $this->tipo,
            'direccion_ip' => $this->direccion_ip ?: null,
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones ?: null,
        ]);

        $this->reset();

        session()->flash('success', 'Cámara registrada correctamente.');
    }
};

?>

<div class="mx-auto max-w-5xl space-y-6 p-6">

    <div>
        <flux:heading size="xl">
            Registrar cámara de seguridad
        </flux:heading>

        <flux:text>
            Sistema de Inventario y Mantenimiento - USC
        </flux:text>
    </div>

    @if (session('success'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        <div class="grid gap-5 md:grid-cols-2">

            <flux:input label="Código de inventario" wire:model="codigo_inventario" required />

            <flux:input label="Número de serie" wire:model="numero_serie" />

            <flux:input label="Marca" wire:model="marca" required />

            <flux:input label="Modelo" wire:model="modelo" required />

            <flux:select label="Tipo de cámara" wire:model="tipo">
                <flux:select.option value="">Seleccione un tipo</flux:select.option>
                <flux:select.option value="IP">IP</flux:select.option>
                <flux:select.option value="Analogica">Analógica</flux:select.option>
                <flux:select.option value="PTZ">PTZ</flux:select.option>
                <flux:select.option value="Otra">Otra</flux:select.option>
            </flux:select>

            <flux:input label="Dirección IP" wire:model="direccion_ip" placeholder="192.168.1.100" />

            <flux:input label="Ubicación" wire:model="ubicacion" required />

            <flux:select label="Estado operativo" wire:model="estado">
                <flux:select.option value="activo">Activo</flux:select.option>
                <flux:select.option value="inactivo">Inactivo</flux:select.option>
                <flux:select.option value="en_mantenimiento">En mantenimiento</flux:select.option>
                <flux:select.option value="fuera_de_servicio">Fuera de servicio</flux:select.option>
            </flux:select>

        </div>

        <flux:textarea label="Observaciones" wire:model="observaciones" rows="3" />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                Guardar cámara
            </flux:button>
        </div>

    </form>

</div>

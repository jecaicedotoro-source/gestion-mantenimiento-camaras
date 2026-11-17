<?php

namespace App\Providers;

use App\Contracts\IClasificadorIncidencias;
use App\Infrastructure\AI\GroqIncidentClassifier;
use App\Infrastructure\AI\OpenAIIncidentClassifier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El dominio conoce el contrato; el adaptador HTTP
        // pertenece a infraestructura.
        $this->app->bind(
            IClasificadorIncidencias::class,
            function (): IClasificadorIncidencias {
                return match (config('ai.provider', 'openai')) {
                    'openai' => new OpenAIIncidentClassifier,
                    'groq' => new GroqIncidentClassifier,
                    default => throw new \LogicException('Proveedor IA no admitido.'),
                };
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        /*
         * Permiso para administrar usuarios.
         *
         * Exclusivo del administrador activo.
         */
        Gate::define('manage-users', function (User $user): bool {
            return $user->is_active
                && $user->role?->code === 'administrador';
        });

        /*
         * Permiso para consultar el inventario de cámaras.
         *
         * Disponible para los cuatro roles institucionales.
         */
        Gate::define('view-cameras', function (User $user): bool {
            return $user->is_active && in_array(
                $user->role?->code,
                [
                    'administrador',
                    'coordinador',
                    'tecnico',
                    'auxiliar',
                ],
                true
            );
        });

        /*
         * Permiso para registrar cámaras de seguridad.
         *
         * Disponible para administradores y coordinadores.
         */
        Gate::define('create-cameras', function (User $user): bool {
            return $user->is_active && in_array(
                $user->role?->code,
                [
                    'administrador',
                    'coordinador',
                ],
                true
            );
        });

        /*
         * HU-09: Permiso para registrar mantenimientos.
         *
         * Roles autorizados:
         * - Administrador
         * - Coordinador
         * - Técnico
         *
         * Acceso denegado:
         * - Auxiliar
         * - Usuario sin rol
         * - Usuario inactivo
         */
        Gate::define('create-maintenances', function (User $user): bool {
            return $user->is_active && in_array(
                $user->role?->code,
                [
                    'administrador',
                    'coordinador',
                    'tecnico',
                ],
                true
            );
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

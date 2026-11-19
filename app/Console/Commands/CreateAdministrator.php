<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** Alta inicial ejecutada por el operador; no introduce contraseñas predeterminadas. */
final class CreateAdministrator extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Crea una cuenta administrativa mediante preguntas interactivas.';

    public function handle(): int
    {
        $name = $this->ask('Nombre del administrador');
        $email = Str::lower(trim((string) $this->ask('Correo electrónico')));
        $password = $this->secret('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas, número y símbolo)');
        $confirmation = $this->secret('Confirma la contraseña');
        $validator = Validator::make([
            'name' => $name, 'email' => $email,
            'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();
        DB::transaction(function () use ($data): void {
            (new RoleSeeder)->run();
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role_id = Role::query()->where('code', 'administrador')->sole()->id;
            // El operador autoriza el alta; no se declara una verificación de propiedad del correo.
            $user->is_active = true;
            $user->saveOrFail();
        });
        $this->info('Administrador creado. No se han mostrado contraseñas ni tokens.');

        return self::SUCCESS;
    }
}

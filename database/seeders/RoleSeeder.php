<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'code' => 'administrador',
                'description' => 'Administra el sistema y gestiona los usuarios.',
            ],
            [
                'name' => 'Coordinador',
                'code' => 'coordinador',
                'description' => 'Coordina el inventario y las actividades de mantenimiento.',
            ],
            [
                'name' => 'Técnico',
                'code' => 'tecnico',
                'description' => 'Ejecuta y registra las actividades de mantenimiento.',
            ],
            [
                'name' => 'Auxiliar',
                'code' => 'auxiliar',
                'description' => 'Apoya el registro y la consulta de información.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['code' => $role['code']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
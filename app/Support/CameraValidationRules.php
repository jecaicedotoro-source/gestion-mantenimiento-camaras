<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;

final class CameraValidationRules
{
    /** @return array<string, list<string|In|Unique>> */
    public static function rules(): array
    {
        return [
            'codigo_inventario' => [
                'required',
                'string',
                'max:50',
            ],
            'numero_serie' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('cameras', 'numero_serie'),
            ],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'string', 'max:50'],
            'direccion_ip' => ['nullable', 'ip', 'max:45'],
            'ubicacion' => ['required', 'string', 'max:150'],
            'estado' => [
                'required',
                Rule::in([
                    'activo',
                    'inactivo',
                    'en_mantenimiento',
                    'fuera_de_servicio',
                ]),
            ],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

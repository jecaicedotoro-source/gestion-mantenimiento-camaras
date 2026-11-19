<?php

return [
    'accepted' => 'Debes aceptar :attribute antes de continuar.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'enum' => 'El valor seleccionado para :attribute no es válido.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'ip' => 'El campo :attribute debe contener una dirección IP válida.',
    'regex' => 'El formato de :attribute no es válido.',
    'size' => ['string' => 'El campo :attribute debe contener :size caracteres.'],
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe contener un correo electrónico válido.',
    'string' => 'El campo :attribute debe contener texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'unique' => 'El :attribute ya se encuentra registrado.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'same' => 'El campo :attribute debe coincidir con :other.',
    'required_with' => 'El campo :attribute es obligatorio cuando se proporciona :values.',

    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],

    'max' => [
        'numeric' => 'El campo :attribute no debe superar :max.',
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],

    'password' => [
        'letters' => 'La contraseña debe contener al menos una letra.',
        'mixed' => 'La contraseña debe contener letras mayúsculas y minúsculas.',
        'numbers' => 'La contraseña debe contener al menos un número.',
        'symbols' => 'La contraseña debe contener al menos un símbolo.',
        'uncompromised' => 'Esta contraseña ha aparecido en una filtración de datos. Elige otra.',
    ],

    'custom' => [
        'newEmail' => [
            'unique' => 'Este correo electrónico ya está registrado.',
        ],

        'newPasswordConfirmation' => [
            'required' => 'Debes confirmar la contraseña.',
            'required_with' => 'Debes confirmar la nueva contraseña.',
            'same' => 'Las contraseñas no coinciden.',
        ],
    ],

    'attributes' => [
        'shareApproved' => 'la autorización de envío',
        'consent' => 'la autorización de envío',
        'cameraId' => 'cámara',
        'camera_id' => 'cámara',
        'description' => 'descripción',
        'category' => 'categoría',
        'codigo_inventario' => 'código de inventario',
        'numero_serie' => 'número de serie',
        'direccion_ip' => 'dirección IP',
        'newName' => 'nombre completo',
        'newEmail' => 'correo electrónico',
        'newRoleId' => 'rol institucional',
        'newPassword' => 'contraseña',
        'newPasswordConfirmation' => 'confirmación de contraseña',
    ],
];

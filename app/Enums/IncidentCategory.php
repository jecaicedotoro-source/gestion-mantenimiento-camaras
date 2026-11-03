<?php

namespace App\Enums;

/** Categorías técnicas del MVP; ninguna representa un diagnóstico definitivo. */
enum IncidentCategory: string
{
    case Connectivity = 'conectividad';
    case Power = 'alimentacion';
    case Image = 'imagen';
    case Hardware = 'hardware';
    case Configuration = 'configuracion';
    case Other = 'otra';

    public function label(): string
    {
        return match ($this) {
            self::Connectivity => 'Conectividad',
            self::Power => 'Alimentación eléctrica',
            self::Image => 'Calidad de imagen',
            self::Hardware => 'Hardware',
            self::Configuration => 'Configuración',
            self::Other => 'Otra / requiere revisión',
        };
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

/** Solo expone códigos y mensajes seguros; nunca cuerpos HTTP ni credenciales. */
final class ClassificationUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason = 'unavailable')
    {
        parent::__construct('No se pudo obtener una sugerencia de IA. Puedes clasificar la incidencia manualmente.');
    }
}

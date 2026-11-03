<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    /**
     * Atributos permitidos para asignación masiva.
     */
    protected $fillable = [
        'codigo_inventario',
        'numero_serie',
        'marca',
        'modelo',
        'tipo',
        'direccion_ip',
        'ubicacion',
        'estado',
        'observaciones',
    ];
}

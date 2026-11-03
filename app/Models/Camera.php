<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Camera extends Model
{
    use SoftDeletes;

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

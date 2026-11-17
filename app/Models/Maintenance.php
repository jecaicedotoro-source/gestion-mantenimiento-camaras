<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    protected $fillable = [
        'camera_id',
        'maintenance_date',
        'type',
        'description',
        'responsible',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
        ];
    }

    /**
     * Cámara asociada al mantenimiento.
     *
     * @return BelongsTo<Camera, $this>
     */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}

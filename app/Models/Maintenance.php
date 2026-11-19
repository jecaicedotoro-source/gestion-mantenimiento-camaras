<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    protected $fillable = [
        'camera_id',
        'incident_id',
        'repair_resolved',
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
            'repair_resolved' => 'boolean',
        ];
    }

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
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

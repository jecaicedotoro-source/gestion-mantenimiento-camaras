<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $incident_id
 * @property int $actor_id
 * @property string $kind
 * @property string $status
 * @property string|null $suggested_category
 * @property string|null $confirmed_category
 * @property string|null $explanation
 * @property string|null $model
 * @property string|null $failure_code
 * @property string|null $input_hash
 */
class IncidentClassification extends Model
{
    // Solo el servicio de aplicación construye el historial; no se aceptan arrays del navegador.
    protected $guarded = ['id'];

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

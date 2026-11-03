<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camera_id
 * @property int $reported_by
 * @property string $description
 * @property string|null $confirmed_category
 * @property string $review_status
 * @property int|null $confirmed_by
 * @property Carbon|null $confirmed_at
 * @property int $classification_attempts
 * @property int $revision
 * @property-read Camera $camera
 */
class Incident extends Model
{
    protected $fillable = ['camera_id', 'reported_by', 'description'];

    /** @var array<string, mixed> Refleja los valores iniciales sin depender de recargar la base. */
    protected $attributes = ['review_status' => 'pendiente', 'classification_attempts' => 0, 'revision' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'classification_attempts' => 'integer', 'revision' => 'integer'];
    }

    /** @return BelongsTo<Camera, $this> */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class)->withTrashed();
    }

    /** @return HasMany<IncidentClassification, $this> */
    public function classifications(): HasMany
    {
        return $this->hasMany(IncidentClassification::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}

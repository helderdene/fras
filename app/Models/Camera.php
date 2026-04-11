<?php

namespace App\Models;

use Database\Factories\CameraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['device_id', 'name', 'location_label', 'latitude', 'longitude'])]
class Camera extends Model
{
    /** @use HasFactory<CameraFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /** Get all enrollment records for this camera. */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CameraEnrollment::class);
    }

    /** Get all recognition events from this camera. */
    public function recognitionEvents(): HasMany
    {
        return $this->hasMany(RecognitionEvent::class);
    }

    /** Get all personnel enrolled to this camera. */
    public function enrolledPersonnel(): BelongsToMany
    {
        return $this->belongsToMany(Personnel::class, 'camera_enrollments')
            ->withPivot('status', 'enrolled_at', 'last_error', 'photo_hash')
            ->withTimestamps();
    }
}

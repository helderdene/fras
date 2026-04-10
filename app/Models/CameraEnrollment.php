<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['camera_id', 'personnel_id', 'status', 'enrolled_at', 'photo_hash', 'last_error'])]
class CameraEnrollment extends Model
{
    const STATUS_PENDING = 'pending';

    const STATUS_ENROLLED = 'enrolled';

    const STATUS_FAILED = 'failed';

    /** @var string */
    protected $table = 'camera_enrollments';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
        ];
    }

    /** Get the camera this enrollment belongs to. */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    /** Get the personnel this enrollment belongs to. */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}

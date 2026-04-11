<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use Database\Factories\RecognitionEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['raw_payload', 'face_image_path', 'scene_image_path'])]
#[Fillable([
    'camera_id',
    'personnel_id',
    'custom_id',
    'camera_person_id',
    'record_id',
    'verify_status',
    'person_type',
    'similarity',
    'is_real_time',
    'name_from_camera',
    'facesluice_id',
    'id_card',
    'phone',
    'is_no_mask',
    'target_bbox',
    'captured_at',
    'face_image_path',
    'scene_image_path',
    'raw_payload',
    'severity',
    'acknowledged_by',
    'acknowledged_at',
    'dismissed_at',
])]
class RecognitionEvent extends Model
{
    /** @use HasFactory<RecognitionEventFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['face_image_url', 'scene_image_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity' => AlertSeverity::class,
            'is_real_time' => 'boolean',
            'similarity' => 'float',
            'target_bbox' => 'array',
            'raw_payload' => 'array',
            'captured_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'verify_status' => 'integer',
            'person_type' => 'integer',
            'is_no_mask' => 'integer',
        ];
    }

    /** Get the camera that captured this recognition event. */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    /** Get the personnel matched in this recognition event. */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /** Get the user who acknowledged this event. */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /** Get the URL for the face crop image. */
    protected function faceImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->face_image_path
            ? "/alerts/{$this->id}/face"
            : null
        );
    }

    /** Get the URL for the scene image. */
    protected function sceneImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->scene_image_path
            ? "/alerts/{$this->id}/scene"
            : null
        );
    }
}

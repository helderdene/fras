<?php

namespace App\Events;

use App\Models\RecognitionEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecognitionAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Create a new event instance. */
    public function __construct(
        public int $id,
        public int $camera_id,
        public string $camera_name,
        public ?int $personnel_id,
        public ?string $person_name,
        public ?string $custom_id,
        public string $severity,
        public float $similarity,
        public int $person_type,
        public ?string $face_image_url,
        public ?string $scene_image_url,
        public ?array $target_bbox,
        public string $captured_at,
        public string $created_at,
    ) {}

    /** Create a RecognitionAlert from a RecognitionEvent model. */
    public static function fromEvent(RecognitionEvent $event): self
    {
        $event->loadMissing(['camera', 'personnel']);

        return new self(
            id: $event->id,
            camera_id: $event->camera_id,
            camera_name: $event->camera->name,
            personnel_id: $event->personnel_id,
            person_name: $event->personnel?->name,
            custom_id: $event->custom_id,
            severity: $event->severity->value,
            similarity: $event->similarity,
            person_type: $event->person_type,
            face_image_url: $event->face_image_url,
            scene_image_url: $event->scene_image_url,
            target_bbox: $event->target_bbox,
            captured_at: $event->captured_at->toIso8601String(),
            created_at: $event->created_at->toIso8601String(),
        );
    }

    /** Get the event's broadcast name. */
    public function broadcastAs(): string
    {
        return 'RecognitionAlert';
    }

    /** Get the channels the event should broadcast on. */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('fras.alerts');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'camera_id' => $this->camera_id,
            'camera_name' => $this->camera_name,
            'personnel_id' => $this->personnel_id,
            'person_name' => $this->person_name,
            'custom_id' => $this->custom_id,
            'severity' => $this->severity,
            'similarity' => $this->similarity,
            'person_type' => $this->person_type,
            'face_image_url' => $this->face_image_url,
            'scene_image_url' => $this->scene_image_url,
            'target_bbox' => $this->target_bbox,
            'captured_at' => $this->captured_at,
            'created_at' => $this->created_at,
        ];
    }
}

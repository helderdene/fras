<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CameraStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Create a new event instance. */
    public function __construct(
        public int $camera_id,
        public string $camera_name,
        public bool $is_online,
        public ?string $last_seen_at,
    ) {}

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
            'camera_id' => $this->camera_id,
            'camera_name' => $this->camera_name,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at,
        ];
    }
}

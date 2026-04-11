<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnrollmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Create a new event instance. */
    public function __construct(
        public int $personnel_id,
        public int $camera_id,
        public string $status,
        public ?string $enrolled_at,
        public ?string $last_error,
    ) {}

    /** Get the event's broadcast name. */
    public function broadcastAs(): string
    {
        return 'EnrollmentStatusChanged';
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
            'personnel_id' => $this->personnel_id,
            'camera_id' => $this->camera_id,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at,
            'last_error' => $this->last_error,
        ];
    }
}

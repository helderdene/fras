<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Create a new event instance. */
    public function __construct(
        public string $message = 'Reverb broadcast test',
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
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

<?php

namespace App\Mqtt\Handlers;

use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;

class HeartbeatHandler implements MqttHandler
{
    /** Handle a HeartBeat message from a camera. */
    public function handle(string $topic, string $message): void
    {
        Log::info('Heartbeat received (stub)', ['topic' => $topic]);
    }
}

<?php

namespace App\Mqtt\Handlers;

use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;

class AckHandler implements MqttHandler
{
    /** Handle an enrollment ACK response from a camera. */
    public function handle(string $topic, string $message): void
    {
        Log::info('Enrollment ACK received (stub)', ['topic' => $topic]);
    }
}

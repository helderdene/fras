<?php

namespace App\Mqtt\Handlers;

use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;

class RecognitionHandler implements MqttHandler
{
    /** Handle a RecPush recognition event from a camera. */
    public function handle(string $topic, string $message): void
    {
        Log::info('RecPush received (stub)', ['topic' => $topic]);
    }
}

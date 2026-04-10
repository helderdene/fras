<?php

namespace App\Mqtt\Handlers;

use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;

class OnlineOfflineHandler implements MqttHandler
{
    /** Handle an Online/Offline status message from a camera. */
    public function handle(string $topic, string $message): void
    {
        Log::info('Online/Offline received (stub)', ['topic' => $topic]);
    }
}

<?php

namespace App\Mqtt;

use App\Mqtt\Contracts\MqttHandler;
use App\Mqtt\Handlers\AckHandler;
use App\Mqtt\Handlers\HeartbeatHandler;
use App\Mqtt\Handlers\OnlineOfflineHandler;
use App\Mqtt\Handlers\RecognitionHandler;
use Illuminate\Support\Facades\Log;

class TopicRouter
{
    /** @var array<string, class-string<MqttHandler>> */
    private array $routes = [
        '#mqtt/face/[^/]+/Rec$#' => RecognitionHandler::class,
        '#mqtt/face/[^/]+/Ack$#' => AckHandler::class,
        '#^mqtt/face/basic$#' => OnlineOfflineHandler::class,
        '#^mqtt/face/heartbeat$#' => HeartbeatHandler::class,
    ];

    /** Dispatch an MQTT message to the appropriate handler based on topic pattern. */
    public function dispatch(string $topic, string $message): void
    {
        foreach ($this->routes as $pattern => $handlerClass) {
            if (preg_match($pattern, $topic)) {
                app($handlerClass)->handle($topic, $message);

                return;
            }
        }

        Log::warning('Unmatched MQTT topic', ['topic' => $topic]);
    }
}

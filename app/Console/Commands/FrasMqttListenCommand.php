<?php

namespace App\Console\Commands;

use App\Mqtt\TopicRouter;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class FrasMqttListenCommand extends Command
{
    protected $signature = 'fras:mqtt-listen';

    protected $description = 'Subscribe to camera MQTT topics and process messages';

    /** Execute the console command. */
    public function handle(TopicRouter $router): int
    {
        $mqtt = MQTT::connection();

        $prefix = config('hds.mqtt.topic_prefix');

        // Subscribe to all topic patterns (QoS 0 per spec section 3.1)
        $topics = [
            $prefix.'/+/Rec',       // Recognition events
            $prefix.'/+/Ack',       // Enrollment ACKs
            $prefix.'/basic',       // Online/Offline
            $prefix.'/heartbeat',   // Heartbeat
        ];

        foreach ($topics as $topic) {
            $mqtt->subscribe($topic, function (string $topic, string $message) use ($router): void {
                $router->dispatch($topic, $message);
            }, 0);
        }

        // Graceful shutdown on SIGTERM/SIGINT
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $mqtt->interrupt());
        pcntl_signal(SIGINT, fn () => $mqtt->interrupt());

        $this->info('MQTT listener started. Subscribed to '.count($topics).' topic patterns.');

        $mqtt->loop(true);

        $mqtt->disconnect();
        $this->info('MQTT listener stopped gracefully.');

        return self::SUCCESS;
    }
}

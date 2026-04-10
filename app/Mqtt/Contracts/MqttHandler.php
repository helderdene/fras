<?php

namespace App\Mqtt\Contracts;

interface MqttHandler
{
    /** Handle an incoming MQTT message. */
    public function handle(string $topic, string $message): void;
}

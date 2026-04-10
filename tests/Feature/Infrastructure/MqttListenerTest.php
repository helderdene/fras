<?php

test('fras:mqtt-listen command is registered', function () {
    $this->artisan('list')
        ->expectsOutputToContain('fras:mqtt-listen');
});

test('mqtt-client config reads from MQTT env vars', function () {
    $config = config('mqtt-client.connections.default');
    expect($config['host'])->toBe(env('MQTT_HOST', '127.0.0.1'))
        ->and($config['port'])->toBe((int) env('MQTT_PORT', 1883))
        ->and($config['use_clean_session'])->toBeFalse();
});

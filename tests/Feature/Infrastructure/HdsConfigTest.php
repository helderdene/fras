<?php

test('hds config has mqtt section with all required keys', function () {
    $config = config('hds.mqtt');
    expect($config)->toBeArray()
        ->and($config)->toHaveKeys([
            'host', 'port', 'username', 'password', 'client_id',
            'topic_prefix', 'keepalive', 'reconnect_delay', 'max_reconnect_attempts',
        ]);
});

test('hds config has retention section', function () {
    $config = config('hds.retention');
    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['scene_images_days', 'face_crops_days']);
});

test('hds config has enrollment section', function () {
    $config = config('hds.enrollment');
    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['batch_size', 'ack_timeout_minutes']);
});

test('hds config has photo section', function () {
    $config = config('hds.photo');
    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['max_dimension', 'max_size_bytes', 'jpeg_quality']);
});

test('hds config has alerts section', function () {
    $config = config('hds.alerts');
    expect($config)->toBeArray()
        ->and($config)->toHaveKey('camera_offline_threshold');
});

test('hds config has mapbox section', function () {
    $config = config('hds.mapbox');
    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['token', 'dark_style', 'light_style']);
});

test('hds config mqtt port is integer', function () {
    expect(config('hds.mqtt.port'))->toBeInt();
});

test('hds config enrollment batch_size defaults to 1000', function () {
    expect(config('hds.enrollment.batch_size'))->toBe(1000);
});

test('hds config offline threshold defaults to 90 seconds', function () {
    expect(config('hds.alerts.camera_offline_threshold'))->toBe(90);
});

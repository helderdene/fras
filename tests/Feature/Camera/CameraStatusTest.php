<?php

use App\Models\Camera;
use App\Mqtt\Handlers\HeartbeatHandler;
use App\Mqtt\Handlers\OnlineOfflineHandler;

test('heartbeat handler updates last_seen_at for known camera', function () {
    $camera = Camera::factory()->create(['device_id' => '1026700']);

    $payload = json_encode([
        'operator' => 'HeartBeat',
        'info' => [
            'facesluiceId' => '1026700',
            'DeviceName' => 'CAM_1026700',
            'IpAddress' => '192.168.2.100',
        ],
    ]);

    (new HeartbeatHandler)->handle('mqtt/face/heartbeat', $payload);

    $camera->refresh();
    expect($camera->last_seen_at)->not->toBeNull();
});

test('heartbeat handler does not update last_seen_at for unknown camera', function () {
    $payload = json_encode([
        'operator' => 'HeartBeat',
        'info' => [
            'facesluiceId' => '9999999',
            'DeviceName' => 'CAM_UNKNOWN',
            'IpAddress' => '192.168.2.200',
        ],
    ]);

    // Should not throw -- just logs a warning
    (new HeartbeatHandler)->handle('mqtt/face/heartbeat', $payload);

    $this->assertDatabaseMissing('cameras', ['device_id' => '9999999']);
});

test('heartbeat handler ignores messages with wrong operator', function () {
    $camera = Camera::factory()->create(['device_id' => '1026700']);

    $payload = json_encode([
        'operator' => 'Online',
        'info' => [
            'facesluiceId' => '1026700',
        ],
    ]);

    (new HeartbeatHandler)->handle('mqtt/face/heartbeat', $payload);

    $camera->refresh();
    expect($camera->last_seen_at)->toBeNull();
});

test('heartbeat handler ignores messages with missing facesluiceId', function () {
    $camera = Camera::factory()->create(['device_id' => '1026700']);

    $payload = json_encode([
        'operator' => 'HeartBeat',
        'info' => [],
    ]);

    (new HeartbeatHandler)->handle('mqtt/face/heartbeat', $payload);

    $camera->refresh();
    expect($camera->last_seen_at)->toBeNull();
});

test('online offline handler marks camera online on Online operator', function () {
    $camera = Camera::factory()->create([
        'device_id' => '1026700',
        'is_online' => false,
    ]);

    $payload = json_encode([
        'operator' => 'Online',
        'info' => [
            'facesluiceId' => '1026700',
            'DeviceName' => 'CAM_1026700',
        ],
    ]);

    (new OnlineOfflineHandler)->handle('mqtt/face/basic', $payload);

    $camera->refresh();
    expect($camera->is_online)->toBeTrue()
        ->and($camera->last_seen_at)->not->toBeNull();
});

test('online offline handler marks camera offline on Offline operator', function () {
    $camera = Camera::factory()->online()->create(['device_id' => '1026700']);
    $originalLastSeen = $camera->last_seen_at;

    $payload = json_encode([
        'operator' => 'Offline',
        'info' => [
            'facesluiceId' => '1026700',
        ],
    ]);

    (new OnlineOfflineHandler)->handle('mqtt/face/basic', $payload);

    $camera->refresh();
    expect($camera->is_online)->toBeFalse()
        ->and($camera->last_seen_at->toIso8601String())->toBe($originalLastSeen->toIso8601String());
});

test('online offline handler ignores messages for unknown cameras', function () {
    $payload = json_encode([
        'operator' => 'Online',
        'info' => [
            'facesluiceId' => '9999999',
        ],
    ]);

    // Should not throw -- just logs a warning
    (new OnlineOfflineHandler)->handle('mqtt/face/basic', $payload);

    $this->assertDatabaseMissing('cameras', ['device_id' => '9999999']);
});

test('online offline handler ignores messages with unexpected operator', function () {
    $camera = Camera::factory()->create([
        'device_id' => '1026700',
        'is_online' => false,
    ]);

    $payload = json_encode([
        'operator' => 'HeartBeat',
        'info' => [
            'facesluiceId' => '1026700',
        ],
    ]);

    (new OnlineOfflineHandler)->handle('mqtt/face/basic', $payload);

    $camera->refresh();
    expect($camera->is_online)->toBeFalse();
});

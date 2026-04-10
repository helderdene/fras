<?php

use App\Events\CameraStatusChanged;
use App\Models\Camera;
use App\Mqtt\Handlers\HeartbeatHandler;
use App\Mqtt\Handlers\OnlineOfflineHandler;
use Illuminate\Support\Facades\Event;

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

// --- Offline Detection Command Tests ---

test('marks stale online cameras as offline', function () {
    $camera = Camera::factory()->create([
        'is_online' => true,
        'last_seen_at' => now()->subSeconds(100),
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    $camera->refresh();
    expect($camera->is_online)->toBeFalse();
});

test('does not mark fresh online cameras as offline', function () {
    $camera = Camera::factory()->create([
        'is_online' => true,
        'last_seen_at' => now()->subSeconds(30),
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    $camera->refresh();
    expect($camera->is_online)->toBeTrue();
});

test('dispatches CameraStatusChanged for newly offline cameras', function () {
    Event::fake([CameraStatusChanged::class]);

    $camera = Camera::factory()->create([
        'is_online' => true,
        'last_seen_at' => now()->subSeconds(100),
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    Event::assertDispatched(CameraStatusChanged::class, function ($event) use ($camera) {
        return $event->camera_id === $camera->id
            && $event->is_online === false;
    });
});

test('does not dispatch events for already offline cameras', function () {
    Event::fake([CameraStatusChanged::class]);

    Camera::factory()->create([
        'is_online' => false,
        'last_seen_at' => now()->subSeconds(200),
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    Event::assertNotDispatched(CameraStatusChanged::class);
});

test('uses configurable offline threshold', function () {
    // With a 30-second threshold, a camera last seen 50 seconds ago should go offline
    config(['hds.alerts.camera_offline_threshold' => 30]);

    $staleCamera = Camera::factory()->create([
        'is_online' => true,
        'last_seen_at' => now()->subSeconds(50),
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    $staleCamera->refresh();
    expect($staleCamera->is_online)->toBeFalse();

    // With a 120-second threshold, a camera last seen 50 seconds ago should remain online
    config(['hds.alerts.camera_offline_threshold' => 120]);

    $freshCamera = Camera::factory()->create([
        'is_online' => true,
        'last_seen_at' => now()->subSeconds(50),
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    $freshCamera->refresh();
    expect($freshCamera->is_online)->toBeTrue();
});

test('does not affect cameras with null last_seen_at that are already offline', function () {
    $camera = Camera::factory()->create([
        'is_online' => false,
        'last_seen_at' => null,
    ]);

    $this->artisan('fras:check-offline-cameras')->assertSuccessful();

    $camera->refresh();
    expect($camera->is_online)->toBeFalse()
        ->and($camera->last_seen_at)->toBeNull();
});

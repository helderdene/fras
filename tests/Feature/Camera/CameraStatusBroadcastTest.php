<?php

use App\Events\CameraStatusChanged;
use App\Models\Camera;
use App\Mqtt\Handlers\HeartbeatHandler;
use App\Mqtt\Handlers\OnlineOfflineHandler;
use Illuminate\Support\Facades\Event;

test('online offline handler dispatches CameraStatusChanged when going online', function () {
    Event::fake([CameraStatusChanged::class]);

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

    Event::assertDispatched(CameraStatusChanged::class, function ($event) use ($camera) {
        return $event->camera_id === $camera->id
            && $event->is_online === true;
    });
});

test('online offline handler dispatches CameraStatusChanged when going offline', function () {
    Event::fake([CameraStatusChanged::class]);

    $camera = Camera::factory()->online()->create(['device_id' => '1026700']);

    $payload = json_encode([
        'operator' => 'Offline',
        'info' => [
            'facesluiceId' => '1026700',
        ],
    ]);

    (new OnlineOfflineHandler)->handle('mqtt/face/basic', $payload);

    Event::assertDispatched(CameraStatusChanged::class, function ($event) use ($camera) {
        return $event->camera_id === $camera->id
            && $event->is_online === false;
    });
});

test('online offline handler does not dispatch event when status stays same', function () {
    Event::fake([CameraStatusChanged::class]);

    Camera::factory()->online()->create(['device_id' => '1026700']);

    $payload = json_encode([
        'operator' => 'Online',
        'info' => [
            'facesluiceId' => '1026700',
            'DeviceName' => 'CAM_1026700',
        ],
    ]);

    (new OnlineOfflineHandler)->handle('mqtt/face/basic', $payload);

    Event::assertNotDispatched(CameraStatusChanged::class);
});

test('heartbeat handler does not dispatch any broadcast event', function () {
    Event::fake([CameraStatusChanged::class]);

    Camera::factory()->create(['device_id' => '1026700']);

    $payload = json_encode([
        'operator' => 'HeartBeat',
        'info' => [
            'facesluiceId' => '1026700',
            'DeviceName' => 'CAM_1026700',
            'IpAddress' => '192.168.2.100',
        ],
    ]);

    (new HeartbeatHandler)->handle('mqtt/face/heartbeat', $payload);

    Event::assertNotDispatched(CameraStatusChanged::class);
});

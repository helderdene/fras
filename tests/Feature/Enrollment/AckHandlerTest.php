<?php

use App\Events\EnrollmentStatusChanged;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Mqtt\Handlers\AckHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

test('ack success updates enrollment to enrolled with timestamp', function () {
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->withPhoto()->create();
    $enrollment = CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $messageId = 'EditPersonsNew2026-04-10T12:00:00_abc123';

    Cache::put("enrollment-ack:{$camera->id}:{$messageId}", [
        'camera_id' => $camera->id,
        'personnel_ids' => [$personnel->id],
        'photo_hashes' => [$personnel->custom_id => 'testhash123'],
        'dispatched_at' => now()->toIso8601String(),
    ], 300);

    $handler = app(AckHandler::class);
    $handler->handle("mqtt/face/{$camera->device_id}/Ack", json_encode([
        'messageId' => $messageId,
        'AddSucInfo' => [
            ['customId' => $personnel->custom_id],
        ],
        'AddErrInfo' => [],
    ]));

    $enrollment->refresh();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_ENROLLED)
        ->and($enrollment->enrolled_at)->not->toBeNull()
        ->and($enrollment->photo_hash)->toBe('testhash123')
        ->and($enrollment->last_error)->toBeNull();
});

test('ack failure updates enrollment to failed with error message', function () {
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->withPhoto()->create();
    $enrollment = CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $messageId = 'EditPersonsNew2026-04-10T12:00:00_def456';

    Cache::put("enrollment-ack:{$camera->id}:{$messageId}", [
        'camera_id' => $camera->id,
        'personnel_ids' => [$personnel->id],
        'photo_hashes' => [],
        'dispatched_at' => now()->toIso8601String(),
    ], 300);

    $handler = app(AckHandler::class);
    $handler->handle("mqtt/face/{$camera->device_id}/Ack", json_encode([
        'messageId' => $messageId,
        'AddSucInfo' => [],
        'AddErrInfo' => [
            ['customId' => $personnel->custom_id, 'errcode' => 468],
        ],
    ]));

    $enrollment->refresh();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_FAILED)
        ->and($enrollment->last_error)->toBe('No usable face detected in photo');
});

test('ack dispatches EnrollmentStatusChanged for each update', function () {
    Event::fake([EnrollmentStatusChanged::class]);

    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->withPhoto()->create();
    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $messageId = 'EditPersonsNew2026-04-10T12:00:00_ghi789';

    Cache::put("enrollment-ack:{$camera->id}:{$messageId}", [
        'camera_id' => $camera->id,
        'personnel_ids' => [$personnel->id],
        'photo_hashes' => [$personnel->custom_id => 'hash1'],
        'dispatched_at' => now()->toIso8601String(),
    ], 300);

    $handler = app(AckHandler::class);
    $handler->handle("mqtt/face/{$camera->device_id}/Ack", json_encode([
        'messageId' => $messageId,
        'AddSucInfo' => [
            ['customId' => $personnel->custom_id],
        ],
        'AddErrInfo' => [],
    ]));

    Event::assertDispatched(EnrollmentStatusChanged::class, function ($event) use ($personnel, $camera) {
        return $event->personnel_id === $personnel->id
            && $event->camera_id === $camera->id
            && $event->status === CameraEnrollment::STATUS_ENROLLED;
    });
});

test('ack with unknown messageId logs warning', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return str_contains($message, 'unknown/expired messageId');
        });
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $camera = Camera::factory()->online()->create();

    $handler = app(AckHandler::class);
    $handler->handle("mqtt/face/{$camera->device_id}/Ack", json_encode([
        'messageId' => 'NonExistent123',
        'AddSucInfo' => [],
        'AddErrInfo' => [],
    ]));
});

test('ack with missing messageId returns without error', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message) {
            return str_contains($message, 'ACK missing messageId');
        });
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $handler = app(AckHandler::class);
    $handler->handle('mqtt/face/CAM001/Ack', json_encode([
        'AddSucInfo' => [],
    ]));
});

test('ack extracts camera from topic', function () {
    $camera = Camera::factory()->online()->create(['device_id' => 'CAM001']);
    $personnel = Personnel::factory()->withPhoto()->create();
    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $messageId = 'EditPersonsNew2026-04-10T12:00:00_xyz999';

    Cache::put("enrollment-ack:{$camera->id}:{$messageId}", [
        'camera_id' => $camera->id,
        'personnel_ids' => [$personnel->id],
        'photo_hashes' => [$personnel->custom_id => 'hash'],
        'dispatched_at' => now()->toIso8601String(),
    ], 300);

    $handler = app(AckHandler::class);
    $handler->handle('mqtt/face/CAM001/Ack', json_encode([
        'messageId' => $messageId,
        'AddSucInfo' => [
            ['customId' => $personnel->custom_id],
        ],
        'AddErrInfo' => [],
    ]));

    $enrollment = CameraEnrollment::where('camera_id', $camera->id)
        ->where('personnel_id', $personnel->id)
        ->first();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_ENROLLED);
});

test('EnrollmentStatusChanged broadcasts on fras.alerts private channel', function () {
    $event = new EnrollmentStatusChanged(
        personnel_id: 1,
        camera_id: 2,
        status: 'enrolled',
        enrolled_at: '2026-04-10T12:00:00+00:00',
        last_error: null,
    );

    expect($event->broadcastOn()->name)->toBe('private-fras.alerts')
        ->and($event->broadcastWith())->toBe([
            'personnel_id' => 1,
            'camera_id' => 2,
            'status' => 'enrolled',
            'enrolled_at' => '2026-04-10T12:00:00+00:00',
            'last_error' => null,
        ]);
});

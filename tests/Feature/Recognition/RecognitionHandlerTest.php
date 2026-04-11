<?php

use App\Enums\AlertSeverity;
use App\Events\RecognitionAlert;
use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use App\Mqtt\Handlers\RecognitionHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Helper to build a valid RecPush payload for testing.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function makeRecPushPayload(array $overrides = []): array
{
    $info = array_merge([
        'customId' => 'EMP-001',
        'personId' => '3',
        'RecordID' => '12345',
        'VerifyStatus' => '1',
        'PersonType' => '0',
        'similarity1' => '83.000000',
        'Sendintime' => 1,
        'personName' => 'John Doe',
        'facesluiceId' => '1026700',
        'idCard' => '12345',
        'telnum' => ' ',
        'time' => '2026-04-10 10:00:07',
        'isNoMask' => '1',
        'PushType' => 0,
        'targetPosInScene' => [346, 0, 1572, 1080],
        'pic' => 'data:image/jpeg;base64,'.base64_encode(str_repeat('x', 100)),
    ], $overrides);

    return [
        'operator' => 'RecPush',
        'info' => $info,
    ];
}

test('handler processes valid RecPush and creates recognition event', function () {
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create(['custom_id' => 'EMP-001']);
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload();
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event)->not->toBeNull()
        ->and($event->camera_id)->toBe($camera->id)
        ->and($event->personnel_id)->toBe($personnel->id)
        ->and($event->custom_id)->toBe('EMP-001')
        ->and($event->record_id)->toBe(12345)
        ->and($event->verify_status)->toBe(1)
        ->and($event->person_type)->toBe(0)
        ->and($event->similarity)->toBe(83.0)
        ->and($event->is_real_time)->toBeTrue()
        ->and($event->name_from_camera)->toBe('John Doe')
        ->and($event->captured_at->format('Y-m-d H:i:s'))->toBe('2026-04-10 10:00:07');
});

test('handler ignores non-RecPush messages', function () {
    $camera = Camera::factory()->online()->create();

    $handler = app(RecognitionHandler::class);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode([
        'operator' => 'HeartBeat',
        'info' => [],
    ]));

    expect(RecognitionEvent::count())->toBe(0);
});

test('handler ignores unknown cameras', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'unknown camera'));
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload();
    $handler->handle('mqtt/face/UNKNOWN_DEVICE/Rec', json_encode($payload));

    expect(RecognitionEvent::count())->toBe(0);
});

test('handler falls back to persionName when personName missing', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'personName' => null,
        'persionName' => 'Firmware Typo Name',
    ]);
    // Remove the null personName key so only persionName remains
    unset($payload['info']['personName']);

    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->name_from_camera)->toBe('Firmware Typo Name');
});

test('handler casts string numeric fields to int and float', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'VerifyStatus' => '1',
        'PersonType' => '0',
        'similarity1' => '95.500000',
        'RecordID' => '99999',
        'isNoMask' => '2',
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->verify_status)->toBe(1)
        ->and($event->person_type)->toBe(0)
        ->and($event->similarity)->toBe(95.5)
        ->and($event->record_id)->toBe(99999)
        ->and($event->is_no_mask)->toBe(2);
});

test('handler handles empty customId', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload(['customId' => '']);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->custom_id)->toBeNull()
        ->and($event->personnel_id)->toBeNull();
});

test('handler decodes base64 face crop and saves to storage', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $faceData = str_repeat('F', 500);
    $base64 = base64_encode($faceData);

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'pic' => 'data:image/jpeg;base64,'.$base64,
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->face_image_path)->not->toBeNull();
    Storage::disk('local')->assertExists($event->face_image_path);

    $storedContent = Storage::disk('local')->get($event->face_image_path);
    expect($storedContent)->toBe($faceData);
});

test('handler skips scene image when not present', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload();
    // No 'scene' key in default payload
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->face_image_path)->not->toBeNull()
        ->and($event->scene_image_path)->toBeNull();
});

test('handler rejects oversized face crop', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    // Create a face crop > 1MB (1048576 bytes)
    $oversizedData = str_repeat('X', 1048577);
    $base64 = base64_encode($oversizedData);

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'pic' => 'data:image/jpeg;base64,'.$base64,
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    // Event should still be created, just without the face image
    expect($event)->not->toBeNull()
        ->and($event->face_image_path)->toBeNull();
});

test('handler stores full raw payload for forensics', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload();
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->raw_payload)->toBe($payload);
});

test('handler classifies severity via AlertSeverity enum', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    // PersonType=1 should be Critical
    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload(['PersonType' => '1']);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->severity)->toBe(AlertSeverity::Critical);
});

test('handler broadcasts RecognitionAlert for real-time broadcastable events', function () {
    Event::fake([RecognitionAlert::class]);

    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'Sendintime' => 1,
        'PushType' => 0,
        'VerifyStatus' => '1',
        'PersonType' => '0',
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    Event::assertDispatched(RecognitionAlert::class, function (RecognitionAlert $alert) use ($camera) {
        return $alert->camera_id === $camera->id
            && $alert->severity === 'info';
    });
});

test('handler does not broadcast manual replay events', function () {
    Event::fake([RecognitionAlert::class]);

    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'PushType' => 2,
        'Sendintime' => 1,
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    Event::assertNotDispatched(RecognitionAlert::class);

    // But event should still be stored
    $event = RecognitionEvent::where('camera_id', $camera->id)->first();
    expect($event)->not->toBeNull()
        ->and($event->is_real_time)->toBeFalse();
});

test('handler does not broadcast ignored severity events', function () {
    Event::fake([RecognitionAlert::class]);

    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    // VerifyStatus=3 (not registered) with PersonType=0 => Ignored severity
    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'VerifyStatus' => '3',
        'PersonType' => '0',
        'Sendintime' => 1,
        'PushType' => 0,
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    Event::assertNotDispatched(RecognitionAlert::class);

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();
    expect($event->severity)->toBe(AlertSeverity::Ignored);
});

test('handler looks up personnel by custom_id', function () {
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create(['custom_id' => 'EMP-LOOKUP']);
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload(['customId' => 'EMP-LOOKUP']);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->personnel_id)->toBe($personnel->id);
});

test('handler saves images with date-partitioned paths', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $faceData = str_repeat('A', 200);
    $sceneData = str_repeat('B', 300);

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'pic' => 'data:image/jpeg;base64,'.base64_encode($faceData),
        'scene' => 'data:image/jpeg;base64,'.base64_encode($sceneData),
        'time' => '2026-04-10 15:30:00',
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->face_image_path)->toContain('recognition/2026-04-10/faces/')
        ->and($event->face_image_path)->toEndWith("{$event->id}.jpg")
        ->and($event->scene_image_path)->toContain('recognition/2026-04-10/scenes/')
        ->and($event->scene_image_path)->toEndWith("{$event->id}.jpg");

    Storage::disk('local')->assertExists($event->face_image_path);
    Storage::disk('local')->assertExists($event->scene_image_path);
});

test('handler saves scene image when present', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $sceneData = str_repeat('S', 400);

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'scene' => 'data:image/jpeg;base64,'.base64_encode($sceneData),
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->scene_image_path)->not->toBeNull();
    Storage::disk('local')->assertExists($event->scene_image_path);

    $storedContent = Storage::disk('local')->get($event->scene_image_path);
    expect($storedContent)->toBe($sceneData);
});

test('handler trims telnum and stores as phone', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);

    // Test with whitespace-only telnum (camera default)
    $payload = makeRecPushPayload(['telnum' => ' ']);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();
    expect($event->phone)->toBeNull();
});

test('handler stores target bbox as JSON array', function () {
    $camera = Camera::factory()->online()->create();
    Storage::fake('local');

    $handler = app(RecognitionHandler::class);
    $payload = makeRecPushPayload([
        'targetPosInScene' => [100, 200, 300, 400],
    ]);
    $handler->handle("mqtt/face/{$camera->device_id}/Rec", json_encode($payload));

    $event = RecognitionEvent::where('camera_id', $camera->id)->first();

    expect($event->target_bbox)->toBe([100, 200, 300, 400]);
});

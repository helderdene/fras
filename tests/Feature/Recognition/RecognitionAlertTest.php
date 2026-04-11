<?php

use App\Events\RecognitionAlert;
use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use Illuminate\Broadcasting\PrivateChannel;

test('recognition alert broadcasts on fras.alerts private channel', function () {
    $alert = new RecognitionAlert(
        id: 1,
        camera_id: 1,
        camera_name: 'Front Gate',
        personnel_id: 1,
        person_name: 'John Doe',
        custom_id: 'EMP-001',
        severity: 'critical',
        similarity: 95.5,
        person_type: 1,
        face_image_url: '/alerts/1/face',
        scene_image_url: '/alerts/1/scene',
        target_bbox: [100, 200, 300, 400],
        captured_at: '2026-04-11T00:00:00+00:00',
        created_at: '2026-04-11T00:00:00+00:00',
    );

    $channel = $alert->broadcastOn();

    expect($channel)->toBeInstanceOf(PrivateChannel::class)
        ->and($channel->name)->toBe('private-fras.alerts');
});

test('recognition alert broadcastAs returns RecognitionAlert', function () {
    $alert = new RecognitionAlert(
        id: 1,
        camera_id: 1,
        camera_name: 'Front Gate',
        personnel_id: null,
        person_name: null,
        custom_id: null,
        severity: 'info',
        similarity: 85.0,
        person_type: 0,
        face_image_url: null,
        scene_image_url: null,
        target_bbox: null,
        captured_at: '2026-04-11T00:00:00+00:00',
        created_at: '2026-04-11T00:00:00+00:00',
    );

    expect($alert->broadcastAs())->toBe('RecognitionAlert');
});

test('recognition alert broadcastWith includes all required fields', function () {
    $alert = new RecognitionAlert(
        id: 42,
        camera_id: 5,
        camera_name: 'Lobby Camera',
        personnel_id: 10,
        person_name: 'Jane Smith',
        custom_id: 'EMP-042',
        severity: 'warning',
        similarity: 88.3,
        person_type: 0,
        face_image_url: '/alerts/42/face',
        scene_image_url: '/alerts/42/scene',
        target_bbox: [50, 100, 200, 300],
        captured_at: '2026-04-11T12:00:00+00:00',
        created_at: '2026-04-11T12:00:01+00:00',
    );

    $data = $alert->broadcastWith();

    expect($data)->toHaveKeys([
        'id',
        'camera_id',
        'camera_name',
        'personnel_id',
        'person_name',
        'custom_id',
        'severity',
        'similarity',
        'person_type',
        'face_image_url',
        'scene_image_url',
        'target_bbox',
        'captured_at',
        'created_at',
    ])
        ->and($data['id'])->toBe(42)
        ->and($data['camera_id'])->toBe(5)
        ->and($data['camera_name'])->toBe('Lobby Camera')
        ->and($data['severity'])->toBe('warning')
        ->and($data['similarity'])->toBe(88.3)
        ->and($data['face_image_url'])->toBe('/alerts/42/face');
});

test('fromEvent maps recognition event model to broadcast payload', function () {
    $camera = Camera::factory()->create(['name' => 'East Wing']);
    $personnel = Personnel::factory()->create(['name' => 'Test Person', 'custom_id' => 'TST-001']);

    $event = RecognitionEvent::factory()
        ->critical()
        ->withFaceImage()
        ->withSceneImage()
        ->create([
            'camera_id' => $camera->id,
            'personnel_id' => $personnel->id,
            'custom_id' => 'TST-001',
            'similarity' => 92.5,
            'target_bbox' => [10, 20, 30, 40],
        ]);

    $alert = RecognitionAlert::fromEvent($event);

    expect($alert->id)->toBe($event->id)
        ->and($alert->camera_id)->toBe($camera->id)
        ->and($alert->camera_name)->toBe('East Wing')
        ->and($alert->personnel_id)->toBe($personnel->id)
        ->and($alert->person_name)->toBe('Test Person')
        ->and($alert->custom_id)->toBe('TST-001')
        ->and($alert->severity)->toBe('critical')
        ->and($alert->similarity)->toBe(92.5)
        ->and($alert->person_type)->toBe(1)
        ->and($alert->face_image_url)->toBe("/alerts/{$event->id}/face")
        ->and($alert->scene_image_url)->toBe("/alerts/{$event->id}/scene")
        ->and($alert->target_bbox)->toBe([10, 20, 30, 40]);
});

test('fromEvent handles null personnel', function () {
    $camera = Camera::factory()->create(['name' => 'Main Gate']);

    $event = RecognitionEvent::factory()
        ->ignored()
        ->create([
            'camera_id' => $camera->id,
            'personnel_id' => null,
            'custom_id' => null,
        ]);

    $alert = RecognitionAlert::fromEvent($event);

    expect($alert->personnel_id)->toBeNull()
        ->and($alert->person_name)->toBeNull()
        ->and($alert->custom_id)->toBeNull();
});

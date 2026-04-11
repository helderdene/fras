<?php

use App\Enums\AlertSeverity;
use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use App\Models\User;

// --- fromEvent() classification tests ---

test('block-list person type returns critical regardless of verify status', function (int $verifyStatus) {
    expect(AlertSeverity::fromEvent(personType: 1, verifyStatus: $verifyStatus))
        ->toBe(AlertSeverity::Critical);
})->with([0, 1, 2, 3]);

test('refused verify status returns warning', function () {
    expect(AlertSeverity::fromEvent(personType: 0, verifyStatus: 2))
        ->toBe(AlertSeverity::Warning);
});

test('allow-list match returns info', function () {
    expect(AlertSeverity::fromEvent(personType: 0, verifyStatus: 1))
        ->toBe(AlertSeverity::Info);
});

test('stranger or not registered returns ignored', function (int $verifyStatus) {
    expect(AlertSeverity::fromEvent(personType: 0, verifyStatus: $verifyStatus))
        ->toBe(AlertSeverity::Ignored);
})->with([0, 3]);

// --- shouldBroadcast() tests ---

test('critical, warning, and info should broadcast', function (AlertSeverity $severity) {
    expect($severity->shouldBroadcast())->toBeTrue();
})->with([AlertSeverity::Critical, AlertSeverity::Warning, AlertSeverity::Info]);

test('ignored should not broadcast', function () {
    expect(AlertSeverity::Ignored->shouldBroadcast())->toBeFalse();
});

// --- shouldAlert() tests ---

test('only critical should alert', function () {
    expect(AlertSeverity::Critical->shouldAlert())->toBeTrue();
});

test('non-critical severities should not alert', function (AlertSeverity $severity) {
    expect($severity->shouldAlert())->toBeFalse();
})->with([AlertSeverity::Warning, AlertSeverity::Info, AlertSeverity::Ignored]);

// --- label() tests ---

test('label returns ucfirst of value', function (AlertSeverity $severity, string $expected) {
    expect($severity->label())->toBe($expected);
})->with([
    'critical' => [AlertSeverity::Critical, 'Critical'],
    'warning' => [AlertSeverity::Warning, 'Warning'],
    'info' => [AlertSeverity::Info, 'Info'],
    'ignored' => [AlertSeverity::Ignored, 'Ignored'],
]);

// --- RecognitionEvent model tests ---

test('recognition event belongs to camera', function () {
    $event = RecognitionEvent::factory()->create();

    expect($event->camera)->toBeInstanceOf(Camera::class);
});

test('recognition event belongs to personnel', function () {
    $event = RecognitionEvent::factory()->create();

    expect($event->personnel)->toBeInstanceOf(Personnel::class);
});

test('recognition event personnel is nullable', function () {
    $event = RecognitionEvent::factory()->create(['personnel_id' => null, 'custom_id' => null]);

    expect($event->personnel)->toBeNull();
});

test('recognition event casts severity to AlertSeverity enum', function () {
    $event = RecognitionEvent::factory()->critical()->create();

    expect($event->severity)->toBe(AlertSeverity::Critical);
});

test('recognition event has acknowledged by relationship', function () {
    $event = RecognitionEvent::factory()->acknowledged()->create();

    expect($event->acknowledgedBy)->toBeInstanceOf(User::class);
});

// --- Factory state tests ---

test('factory critical state sets correct values', function () {
    $event = RecognitionEvent::factory()->critical()->create();

    expect($event->person_type)->toBe(1)
        ->and($event->verify_status)->toBe(0)
        ->and($event->severity)->toBe(AlertSeverity::Critical);
});

test('factory warning state sets correct values', function () {
    $event = RecognitionEvent::factory()->warning()->create();

    expect($event->person_type)->toBe(0)
        ->and($event->verify_status)->toBe(2)
        ->and($event->severity)->toBe(AlertSeverity::Warning);
});

test('factory info state sets correct values', function () {
    $event = RecognitionEvent::factory()->info()->create();

    expect($event->person_type)->toBe(0)
        ->and($event->verify_status)->toBe(1)
        ->and($event->severity)->toBe(AlertSeverity::Info);
});

test('factory ignored state sets correct values', function () {
    $event = RecognitionEvent::factory()->ignored()->create();

    expect($event->person_type)->toBe(0)
        ->and($event->verify_status)->toBe(3)
        ->and($event->severity)->toBe(AlertSeverity::Ignored);
});

// --- Acknowledgment migration tests ---

test('recognition event has acknowledgment columns', function () {
    $event = RecognitionEvent::factory()->acknowledged()->create();

    expect($event->acknowledged_at)->not->toBeNull()
        ->and($event->acknowledged_by)->not->toBeNull();
});

test('recognition event has dismissed column', function () {
    $event = RecognitionEvent::factory()->dismissed()->create();

    expect($event->dismissed_at)->not->toBeNull();
});

// --- Image URL accessor tests ---

test('face image url returns path when face image exists', function () {
    $event = RecognitionEvent::factory()->withFaceImage()->create();

    expect($event->face_image_url)->toBe("/alerts/{$event->id}/face");
});

test('face image url returns null when no face image', function () {
    $event = RecognitionEvent::factory()->create(['face_image_path' => null]);

    expect($event->face_image_url)->toBeNull();
});

test('scene image url returns path when scene image exists', function () {
    $event = RecognitionEvent::factory()->withSceneImage()->create();

    expect($event->scene_image_url)->toBe("/alerts/{$event->id}/scene");
});

test('scene image url returns null when no scene image', function () {
    $event = RecognitionEvent::factory()->create(['scene_image_path' => null]);

    expect($event->scene_image_url)->toBeNull();
});

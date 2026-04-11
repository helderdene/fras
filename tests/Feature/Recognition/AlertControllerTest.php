<?php

use App\Models\RecognitionEvent;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

test('alert feed page loads with recent events', function () {
    $user = User::factory()->create();

    $events = RecognitionEvent::factory()->count(3)->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('alerts/Index')
            ->has('events', 3)
        );
});

test('alert feed excludes ignored severity events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create();
    RecognitionEvent::factory()->ignored()->create();

    $this->actingAs($user)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
        );
});

test('alert feed excludes non-real-time events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create();
    RecognitionEvent::factory()->info()->replay()->create();

    $this->actingAs($user)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
        );
});

test('alert feed orders by captured_at descending', function () {
    $user = User::factory()->create();

    $older = RecognitionEvent::factory()->info()->create([
        'captured_at' => now()->subMinutes(5),
    ]);
    $newer = RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events', 2)
            ->where('events.0.id', $newer->id)
            ->where('events.1.id', $older->id)
        );
});

test('alert feed limits to 50 events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->count(55)->info()->create();

    $this->actingAs($user)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events', 50)
        );
});

test('acknowledge records user and timestamp', function () {
    $user = User::factory()->create();
    $event = RecognitionEvent::factory()->info()->create();

    $this->actingAs($user)
        ->post(route('alerts.acknowledge', $event))
        ->assertRedirect();

    $event->refresh();

    expect($event->acknowledged_by)->toBe($user->id)
        ->and($event->acknowledged_at)->not->toBeNull();
});

test('dismiss records timestamp', function () {
    $user = User::factory()->create();
    $event = RecognitionEvent::factory()->info()->create();

    $this->actingAs($user)
        ->post(route('alerts.dismiss', $event))
        ->assertRedirect();

    $event->refresh();

    expect($event->dismissed_at)->not->toBeNull();
});

test('face image returns file response', function () {
    Storage::fake('local');
    Storage::disk('local')->put('recognition/faces/test.jpg', 'fake-image-content');

    $user = User::factory()->create();
    $event = RecognitionEvent::factory()->info()->create([
        'face_image_path' => 'recognition/faces/test.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('alerts.face-image', $event))
        ->assertSuccessful();
});

test('face image returns 404 when no path', function () {
    $user = User::factory()->create();
    $event = RecognitionEvent::factory()->info()->create([
        'face_image_path' => null,
    ]);

    $this->actingAs($user)
        ->get(route('alerts.face-image', $event))
        ->assertNotFound();
});

test('scene image returns file response', function () {
    Storage::fake('local');
    Storage::disk('local')->put('recognition/scenes/test.jpg', 'fake-image-content');

    $user = User::factory()->create();
    $event = RecognitionEvent::factory()->info()->create([
        'scene_image_path' => 'recognition/scenes/test.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('alerts.scene-image', $event))
        ->assertSuccessful();
});

test('scene image returns 404 when no path', function () {
    $user = User::factory()->create();
    $event = RecognitionEvent::factory()->info()->create([
        'scene_image_path' => null,
    ]);

    $this->actingAs($user)
        ->get(route('alerts.scene-image', $event))
        ->assertNotFound();
});

test('alert routes require authentication', function () {
    $this->get(route('alerts.index'))
        ->assertRedirect(route('login'));
});

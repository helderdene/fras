<?php

use App\Models\RecognitionEvent;
use Illuminate\Support\Facades\Storage;

test('command signature is fras:cleanup-retention-images', function () {
    $this->artisan('fras:cleanup-retention-images')
        ->assertSuccessful();
});

test('scene images older than configured retention are deleted from disk', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->withSceneImage()->create([
        'captured_at' => now()->subDays(31),
    ]);

    Storage::disk('local')->put($event->scene_image_path, 'scene-content');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    Storage::disk('local')->assertMissing($event->scene_image_path);
});

test('face crops older than configured retention are deleted from disk', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->withFaceImage()->create([
        'captured_at' => now()->subDays(91),
    ]);

    Storage::disk('local')->put($event->face_image_path, 'face-content');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    Storage::disk('local')->assertMissing($event->face_image_path);
});

test('scene_image_path column is nullified after deleting scene image', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->withSceneImage()->create([
        'captured_at' => now()->subDays(31),
    ]);

    Storage::disk('local')->put($event->scene_image_path, 'scene-content');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    $event->refresh();
    expect($event->scene_image_path)->toBeNull();
});

test('face_image_path column is nullified after deleting face image', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->withFaceImage()->create([
        'captured_at' => now()->subDays(91),
    ]);

    Storage::disk('local')->put($event->face_image_path, 'face-content');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    $event->refresh();
    expect($event->face_image_path)->toBeNull();
});

test('recognition event rows are preserved after image cleanup', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->withSceneImage()->withFaceImage()->create([
        'captured_at' => now()->subDays(91),
    ]);

    Storage::disk('local')->put($event->scene_image_path, 'scene-content');
    Storage::disk('local')->put($event->face_image_path, 'face-content');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    expect(RecognitionEvent::find($event->id))->not->toBeNull();
});

test('events within retention window are not affected', function () {
    Storage::fake('local');

    $recentEvent = RecognitionEvent::factory()->withSceneImage()->withFaceImage()->create([
        'captured_at' => now()->subDays(5),
    ]);

    Storage::disk('local')->put($recentEvent->scene_image_path, 'scene-content');
    Storage::disk('local')->put($recentEvent->face_image_path, 'face-content');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    Storage::disk('local')->assertExists($recentEvent->scene_image_path);
    Storage::disk('local')->assertExists($recentEvent->face_image_path);

    $recentEvent->refresh();
    expect($recentEvent->scene_image_path)->not->toBeNull()
        ->and($recentEvent->face_image_path)->not->toBeNull();
});

test('command logs summary of deleted counts', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->withSceneImage()->create([
        'captured_at' => now()->subDays(31),
    ]);

    Storage::disk('local')->put($event->scene_image_path, 'scene-content');

    $this->artisan('fras:cleanup-retention-images')
        ->assertSuccessful()
        ->expectsOutputToContain('Retention cleanup: deleted');
});

test('command returns SUCCESS exit code', function () {
    $this->artisan('fras:cleanup-retention-images')
        ->assertExitCode(0);
});

test('events with null image paths are skipped', function () {
    Storage::fake('local');

    $event = RecognitionEvent::factory()->create([
        'captured_at' => now()->subDays(91),
        'face_image_path' => null,
        'scene_image_path' => null,
    ]);

    $this->artisan('fras:cleanup-retention-images')
        ->assertSuccessful()
        ->expectsOutputToContain('Retention cleanup: deleted 0 scene images, 0 face crops');
});

test('custom retention values from config are respected', function () {
    Storage::fake('local');

    config(['hds.retention.scene_images_days' => 5]);

    $oldEvent = RecognitionEvent::factory()->withSceneImage()->create([
        'captured_at' => now()->subDays(6),
    ]);
    $recentEvent = RecognitionEvent::factory()->withSceneImage()->create([
        'captured_at' => now()->subDays(4),
    ]);

    Storage::disk('local')->put($oldEvent->scene_image_path, 'old-scene');
    Storage::disk('local')->put($recentEvent->scene_image_path, 'recent-scene');

    $this->artisan('fras:cleanup-retention-images')->assertSuccessful();

    Storage::disk('local')->assertMissing($oldEvent->scene_image_path);
    Storage::disk('local')->assertExists($recentEvent->scene_image_path);

    $oldEvent->refresh();
    $recentEvent->refresh();
    expect($oldEvent->scene_image_path)->toBeNull()
        ->and($recentEvent->scene_image_path)->not->toBeNull();
});

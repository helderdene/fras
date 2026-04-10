<?php

use App\Events\CameraStatusChanged;
use App\Models\Camera;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

beforeEach(function () {
    $this->withoutVite();
});

test('camera model has correct fillable attributes', function () {
    $camera = Camera::create([
        'device_id' => '9999999',
        'name' => 'Test Camera',
        'location_label' => 'Test Location',
        'latitude' => 8.9475785,
        'longitude' => 125.5406434,
    ]);

    expect($camera)->toBeInstanceOf(Camera::class)
        ->and($camera->device_id)->toBe('9999999')
        ->and($camera->name)->toBe('Test Camera')
        ->and($camera->location_label)->toBe('Test Location');
});

test('camera model casts coordinates as decimals and is_online as boolean', function () {
    $camera = Camera::factory()->create([
        'latitude' => 8.9475785,
        'longitude' => 125.5406434,
        'is_online' => true,
        'last_seen_at' => now(),
    ]);

    $camera->refresh();

    expect($camera->latitude)->toBeString()
        ->and($camera->longitude)->toBeString()
        ->and($camera->is_online)->toBeBool()
        ->and($camera->last_seen_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('camera factory creates valid records', function () {
    $camera = Camera::factory()->create();

    expect($camera->exists)->toBeTrue()
        ->and($camera->device_id)->not->toBeEmpty()
        ->and($camera->name)->not->toBeEmpty()
        ->and($camera->location_label)->not->toBeEmpty()
        ->and($camera->latitude)->not->toBeNull()
        ->and($camera->longitude)->not->toBeNull();
});

test('camera factory online state sets is_online and last_seen_at', function () {
    $camera = Camera::factory()->online()->create();

    expect($camera->is_online)->toBeTrue()
        ->and($camera->last_seen_at)->not->toBeNull();
});

test('camera status changed event implements ShouldBroadcast on fras.alerts', function () {
    $event = new CameraStatusChanged(
        camera_id: 1,
        camera_name: 'Test Camera',
        is_online: true,
        last_seen_at: now()->toIso8601String(),
    );

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channel = $event->broadcastOn();
    expect($channel)->toBeInstanceOf(PrivateChannel::class)
        ->and($channel->name)->toBe('private-fras.alerts');
});

test('camera status changed event broadcastWith returns expected data', function () {
    $timestamp = now()->toIso8601String();
    $event = new CameraStatusChanged(
        camera_id: 42,
        camera_name: 'Lobby',
        is_online: false,
        last_seen_at: $timestamp,
    );

    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['camera_id', 'camera_name', 'is_online', 'last_seen_at'])
        ->and($data['camera_id'])->toBe(42)
        ->and($data['camera_name'])->toBe('Lobby')
        ->and($data['is_online'])->toBeFalse()
        ->and($data['last_seen_at'])->toBe($timestamp);
});

test('store camera request validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cameras.store'), [])
        ->assertSessionHasErrors(['device_id', 'name', 'location_label', 'latitude', 'longitude']);
});

test('store camera request enforces unique device_id', function () {
    $user = User::factory()->create();
    Camera::factory()->create(['device_id' => 'DUPLICATE']);

    $this->actingAs($user)
        ->post(route('cameras.store'), [
            'device_id' => 'DUPLICATE',
            'name' => 'New Camera',
            'location_label' => 'Somewhere',
            'latitude' => 8.95,
            'longitude' => 125.54,
        ])
        ->assertSessionHasErrors(['device_id']);
});

test('store camera request validates coordinate ranges', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cameras.store'), [
            'device_id' => '1234567',
            'name' => 'Test',
            'location_label' => 'Test',
            'latitude' => 91,
            'longitude' => 181,
        ])
        ->assertSessionHasErrors(['latitude', 'longitude']);
});

test('update camera request allows same device_id for own camera', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create(['device_id' => 'OWN-ID']);

    $this->actingAs($user)
        ->put(route('cameras.update', $camera), [
            'device_id' => 'OWN-ID',
            'name' => 'Updated Name',
            'location_label' => 'Updated Location',
            'latitude' => 8.95,
            'longitude' => 125.54,
        ])
        ->assertSessionHasNoErrors();
});

test('can list cameras', function () {
    $user = User::factory()->create();
    Camera::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('cameras.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cameras/Index')
            ->has('cameras', 3)
        );
});

test('can view create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('cameras.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cameras/Create')
            ->has('mapboxToken')
        );
});

test('can store a camera', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cameras.store'), [
            'device_id' => '5550001',
            'name' => 'New Camera',
            'location_label' => 'Test Location',
            'latitude' => 8.9475,
            'longitude' => 125.5406,
        ])
        ->assertRedirect(route('cameras.index'));

    expect(Camera::where('device_id', '5550001')->exists())->toBeTrue();
});

test('can view a camera', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create();

    $this->actingAs($user)
        ->get(route('cameras.show', $camera))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cameras/Show')
            ->has('camera')
            ->has('mapboxToken')
        );
});

test('can view edit form', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create();

    $this->actingAs($user)
        ->get(route('cameras.edit', $camera))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cameras/Edit')
            ->has('camera')
            ->has('mapboxToken')
        );
});

test('can update a camera', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create();

    $this->actingAs($user)
        ->put(route('cameras.update', $camera), [
            'device_id' => $camera->device_id,
            'name' => 'Renamed Camera',
            'location_label' => 'New Location',
            'latitude' => 8.95,
            'longitude' => 125.54,
        ])
        ->assertRedirect(route('cameras.show', $camera));

    expect($camera->fresh()->name)->toBe('Renamed Camera');
});

test('can delete a camera', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create();

    $this->actingAs($user)
        ->delete(route('cameras.destroy', $camera))
        ->assertRedirect(route('cameras.index'));

    expect(Camera::find($camera->id))->toBeNull();
});

test('requires authentication for camera routes', function () {
    $this->get(route('cameras.index'))
        ->assertRedirect(route('login'));
});

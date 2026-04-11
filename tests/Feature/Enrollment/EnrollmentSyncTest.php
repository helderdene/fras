<?php

use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PhpMqtt\Client\Contracts\MqttClient;
use PhpMqtt\Client\Facades\MQTT;

beforeEach(function () {
    $this->withoutVite();
});

test('saving personnel creates enrollment rows for all cameras', function () {
    Storage::fake('public');
    Bus::fake([EnrollPersonnelBatch::class]);
    $user = User::factory()->create();
    Camera::factory()->online()->create();
    Camera::factory()->offline()->create();

    $this->actingAs($user)
        ->post(route('personnel.store'), [
            'custom_id' => 'SYNC-001',
            'name' => 'Sync Test',
            'person_type' => 0,
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])
        ->assertRedirect(route('personnel.index'));

    $personnel = Personnel::where('custom_id', 'SYNC-001')->first();
    expect(CameraEnrollment::where('personnel_id', $personnel->id)->count())->toBe(2);
});

test('saving personnel dispatches job only for online cameras', function () {
    Storage::fake('public');
    Bus::fake([EnrollPersonnelBatch::class]);
    $user = User::factory()->create();
    Camera::factory()->online()->create();
    Camera::factory()->offline()->create();

    $this->actingAs($user)
        ->post(route('personnel.store'), [
            'custom_id' => 'SYNC-002',
            'name' => 'Sync Test 2',
            'person_type' => 0,
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ]);

    Bus::assertDispatched(EnrollPersonnelBatch::class, 1);
});

test('creating camera creates pending enrollment rows for all existing personnel', function () {
    Bus::fake([EnrollPersonnelBatch::class]);
    $user = User::factory()->create();
    Personnel::factory()->count(5)->create();

    $this->actingAs($user)
        ->post(route('cameras.store'), [
            'device_id' => '7770001',
            'name' => 'New Sync Camera',
            'location_label' => 'Test Location',
            'latitude' => 8.9475,
            'longitude' => 125.5406,
        ])
        ->assertRedirect(route('cameras.index'));

    $camera = Camera::where('device_id', '7770001')->first();
    expect(CameraEnrollment::where('camera_id', $camera->id)->count())->toBe(5);
    expect(CameraEnrollment::where('camera_id', $camera->id)->where('status', CameraEnrollment::STATUS_PENDING)->count())->toBe(5);
    // New camera is offline by default — job dispatched only when camera comes online (D-03, WR-06)
    Bus::assertNotDispatched(EnrollPersonnelBatch::class);
});

test('updating personnel dispatches enrollment to online cameras', function () {
    Storage::fake('public');
    Bus::fake([EnrollPersonnelBatch::class]);
    $user = User::factory()->create();
    Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    $this->actingAs($user)
        ->put(route('personnel.update', $personnel), [
            'custom_id' => $personnel->custom_id,
            'name' => 'Updated Sync Person',
            'person_type' => 0,
        ]);

    Bus::assertDispatched(EnrollPersonnelBatch::class, 1);
});

test('deleting personnel sends delete to enrolled cameras', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
    ]);

    // Mock MQTT facade to prevent actual publish and verify the call
    $mockConnection = Mockery::mock(MqttClient::class);
    $mockConnection->shouldReceive('publish')
        ->once()
        ->withArgs(function (string $topic, string $message) use ($camera) {
            $prefix = config('hds.mqtt.topic_prefix');

            return str_contains($topic, "{$prefix}/{$camera->device_id}")
                && str_contains($message, 'DeletePersons');
        });
    MQTT::shouldReceive('connection')->with('publisher')->andReturn($mockConnection);

    $this->actingAs($user)
        ->delete(route('personnel.destroy', $personnel))
        ->assertRedirect(route('personnel.index'));

    $this->assertModelMissing($personnel);
});

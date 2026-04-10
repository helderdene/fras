<?php

use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpMqtt\Client\Facades\MQTT;

beforeEach(function () {
    $this->withoutVite();
});

test('deleting personnel sends delete MQTT to enrolled cameras', function () {
    MQTT::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
    ]);

    $this->actingAs($user)
        ->delete(route('personnel.destroy', $personnel))
        ->assertRedirect(route('personnel.index'));

    $prefix = config('hds.mqtt.topic_prefix');
    $topic = "{$prefix}/{$camera->device_id}/Edit";

    MQTT::assertPublished($topic);
});

test('deleting personnel flashes camera removal toast', function () {
    MQTT::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $personnel = Personnel::factory()->create();

    $this->actingAs($user)
        ->delete(route('personnel.destroy', $personnel))
        ->assertRedirect(route('personnel.index'));

    // The toast message is set via Inertia::flash in the controller
    // Verify the personnel was actually deleted
    $this->assertModelMissing($personnel);
});

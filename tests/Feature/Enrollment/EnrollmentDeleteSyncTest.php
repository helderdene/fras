<?php

use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpMqtt\Client\Contracts\MqttClient;
use PhpMqtt\Client\Facades\MQTT;

beforeEach(function () {
    $this->withoutVite();
});

test('deleting personnel sends delete MQTT to enrolled cameras', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
    ]);

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

test('deleting personnel removes model and redirects', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $personnel = Personnel::factory()->create();

    // No enrollment rows, so no MQTT calls expected
    MQTT::shouldReceive('publish')->never();

    $this->actingAs($user)
        ->delete(route('personnel.destroy', $personnel))
        ->assertRedirect(route('personnel.index'));

    $this->assertModelMissing($personnel);
});

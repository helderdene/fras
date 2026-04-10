<?php

use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->withoutVite();
});

test('retry resets enrollment status to pending and dispatches job', function () {
    Bus::fake([EnrollPersonnelBatch::class]);

    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_FAILED,
        'last_error' => 'Photo required for first enrollment',
    ]);

    $this->actingAs($user)
        ->post(route('enrollment.retry', [$personnel, $camera]))
        ->assertRedirect();

    $enrollment = CameraEnrollment::where('camera_id', $camera->id)
        ->where('personnel_id', $personnel->id)
        ->first();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_PENDING)
        ->and($enrollment->last_error)->toBeNull();

    Bus::assertDispatched(EnrollPersonnelBatch::class, function ($job) use ($camera, $personnel) {
        return $job->camera->id === $camera->id
            && $job->personnelIds === [$personnel->id];
    });
});

test('retry for offline camera creates pending row but does not dispatch job', function () {
    Bus::fake([EnrollPersonnelBatch::class]);

    $user = User::factory()->create();
    $camera = Camera::factory()->offline()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_FAILED,
        'last_error' => 'Some error',
    ]);

    $this->actingAs($user)
        ->post(route('enrollment.retry', [$personnel, $camera]))
        ->assertRedirect();

    $enrollment = CameraEnrollment::where('camera_id', $camera->id)
        ->where('personnel_id', $personnel->id)
        ->first();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_PENDING);

    Bus::assertNotDispatched(EnrollPersonnelBatch::class);
});

test('retry requires authentication', function () {
    $camera = Camera::factory()->create();
    $personnel = Personnel::factory()->create();

    $this->post(route('enrollment.retry', [$personnel, $camera]))
        ->assertRedirect(route('login'));
});

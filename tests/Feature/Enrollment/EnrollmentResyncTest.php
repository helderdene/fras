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

test('resync all resets all enrollments and dispatches to online cameras', function () {
    Bus::fake([EnrollPersonnelBatch::class]);

    $user = User::factory()->create();
    $onlineCamera = Camera::factory()->online()->create();
    $offlineCamera = Camera::factory()->offline()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $onlineCamera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
    ]);

    CameraEnrollment::create([
        'camera_id' => $offlineCamera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
    ]);

    $this->actingAs($user)
        ->post(route('enrollment.resync-all', $personnel))
        ->assertRedirect();

    $enrollments = CameraEnrollment::where('personnel_id', $personnel->id)->get();

    expect($enrollments)->each(fn ($enrollment) => $enrollment->status->toBe(CameraEnrollment::STATUS_PENDING)
        ->and($enrollment->last_error)->toBeNull()
    );

    // Only online camera should have a job dispatched
    Bus::assertDispatched(EnrollPersonnelBatch::class, function ($job) use ($onlineCamera) {
        return $job->camera->id === $onlineCamera->id;
    });

    Bus::assertNotDispatched(EnrollPersonnelBatch::class, function ($job) use ($offlineCamera) {
        return $job->camera->id === $offlineCamera->id;
    });
});

test('resync all creates enrollment rows for cameras without prior enrollment', function () {
    Bus::fake([EnrollPersonnelBatch::class]);

    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    // No enrollment row exists for this camera
    expect(CameraEnrollment::where('camera_id', $camera->id)->count())->toBe(0);

    $this->actingAs($user)
        ->post(route('enrollment.resync-all', $personnel))
        ->assertRedirect();

    $enrollment = CameraEnrollment::where('camera_id', $camera->id)
        ->where('personnel_id', $personnel->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe(CameraEnrollment::STATUS_PENDING);
});

test('resync all requires authentication', function () {
    $personnel = Personnel::factory()->create();

    $this->post(route('enrollment.resync-all', $personnel))
        ->assertRedirect(route('login'));
});

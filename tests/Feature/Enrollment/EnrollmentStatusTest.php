<?php

use App\Events\EnrollmentStatusChanged;
use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Mqtt\Handlers\OnlineOfflineHandler;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

test('timeout command marks old pending enrollments as failed', function () {
    config(['hds.enrollment.ack_timeout_minutes' => 5]);

    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();
    $enrollment = CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    // Simulate enrollment created 10 minutes ago
    $enrollment->update(['updated_at' => now()->subMinutes(10)]);

    $this->artisan('enrollment:check-timeouts')
        ->expectsOutputToContain('1 enrollment(s) as timed out')
        ->assertSuccessful();

    $enrollment->refresh();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_FAILED)
        ->and($enrollment->last_error)->toContain('timed out');
});

test('timeout command ignores already enrolled records', function () {
    config(['hds.enrollment.ack_timeout_minutes' => 5]);

    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();
    $enrollment = CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
        'enrolled_at' => now()->subMinutes(10),
    ]);

    $enrollment->update(['updated_at' => now()->subMinutes(10)]);

    $this->artisan('enrollment:check-timeouts')
        ->expectsOutputToContain('No timed-out enrollments found')
        ->assertSuccessful();

    $enrollment->refresh();

    expect($enrollment->status)->toBe(CameraEnrollment::STATUS_ENROLLED);
});

test('timeout command dispatches EnrollmentStatusChanged', function () {
    Event::fake([EnrollmentStatusChanged::class]);
    config(['hds.enrollment.ack_timeout_minutes' => 5]);

    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();
    $enrollment = CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $enrollment->update(['updated_at' => now()->subMinutes(10)]);

    $this->artisan('enrollment:check-timeouts')->assertSuccessful();

    Event::assertDispatched(EnrollmentStatusChanged::class, function ($event) use ($personnel, $camera) {
        return $event->personnel_id === $personnel->id
            && $event->camera_id === $camera->id
            && $event->status === CameraEnrollment::STATUS_FAILED
            && str_contains($event->last_error, 'timed out');
    });
});

test('online handler dispatches pending enrollments when camera comes online', function () {
    Bus::fake([EnrollPersonnelBatch::class]);
    Event::fake([EnrollmentStatusChanged::class, \App\Events\CameraStatusChanged::class]);

    $camera = Camera::factory()->offline()->create();
    $personnel = Personnel::factory()->create();
    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $handler = app(OnlineOfflineHandler::class);
    $handler->handle('mqtt/face/basic', json_encode([
        'operator' => 'Online',
        'info' => ['facesluiceId' => $camera->device_id],
    ]));

    Bus::assertDispatched(EnrollPersonnelBatch::class, function ($job) use ($camera) {
        return $job->camera->id === $camera->id;
    });
});

test('online handler does not dispatch when camera goes offline', function () {
    Bus::fake([EnrollPersonnelBatch::class]);
    Event::fake([\App\Events\CameraStatusChanged::class]);

    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();
    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $handler = app(OnlineOfflineHandler::class);
    $handler->handle('mqtt/face/basic', json_encode([
        'operator' => 'Offline',
        'info' => ['facesluiceId' => $camera->device_id],
    ]));

    Bus::assertNotDispatched(EnrollPersonnelBatch::class);
});

test('online handler does not dispatch when camera was already online', function () {
    Bus::fake([EnrollPersonnelBatch::class]);

    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();
    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $handler = app(OnlineOfflineHandler::class);
    $handler->handle('mqtt/face/basic', json_encode([
        'operator' => 'Online',
        'info' => ['facesluiceId' => $camera->device_id],
    ]));

    Bus::assertNotDispatched(EnrollPersonnelBatch::class);
});

<?php

use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Services\CameraEnrollmentService;
use Illuminate\Queue\Middleware\WithoutOverlapping;

test('job has WithoutOverlapping middleware keyed by camera id', function () {
    $camera = Camera::factory()->online()->create();
    $job = new EnrollPersonnelBatch($camera, [1, 2, 3]);

    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);
});

test('job calls upsertBatch on service', function () {
    $camera = Camera::factory()->online()->create();
    $personnelIds = [1, 2, 3];

    $mock = $this->mock(CameraEnrollmentService::class);
    $mock->shouldReceive('upsertBatch')
        ->once()
        ->with(
            \Mockery::on(fn ($arg) => $arg->id === $camera->id),
            $personnelIds
        );

    $job = new EnrollPersonnelBatch($camera, $personnelIds);
    $job->handle($mock);
});

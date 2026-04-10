<?php

use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Services\CameraEnrollmentService;
use Illuminate\Support\Facades\Bus;

test('buildEditPersonsNewPayload produces correct structure', function () {
    $service = app(CameraEnrollmentService::class);
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->withPhoto()->create();

    $payload = $service->buildEditPersonsNewPayload(
        $camera,
        collect([$personnel]),
        'TestMsg001'
    );

    expect($payload)
        ->toHaveKeys(['messageId', 'DataBegin', 'operator', 'PersonNum', 'info', 'DataEnd'])
        ->and($payload['messageId'])->toBe('TestMsg001')
        ->and($payload['DataBegin'])->toBe('BeginFlag')
        ->and($payload['operator'])->toBe('EditPersonsNew')
        ->and($payload['PersonNum'])->toBe(1)
        ->and($payload['DataEnd'])->toBe('EndFlag')
        ->and($payload['info'][0])->toHaveKeys(['customId', 'name', 'personType', 'isCheckSimilarity', 'picURI']);
});

test('buildEditPersonsNewPayload omits picURI when photo_path null', function () {
    $service = app(CameraEnrollmentService::class);
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create(['photo_path' => null]);

    $payload = $service->buildEditPersonsNewPayload(
        $camera,
        collect([$personnel]),
        'TestMsg002'
    );

    expect($payload['info'][0])->not->toHaveKey('picURI');
});

test('enrollPersonnel dispatches jobs for online cameras only', function () {
    Bus::fake([EnrollPersonnelBatch::class]);
    Camera::factory()->online()->count(2)->create();
    Camera::factory()->offline()->create();
    $personnel = Personnel::factory()->create();

    $service = app(CameraEnrollmentService::class);
    $service->enrollPersonnel($personnel);

    Bus::assertDispatched(EnrollPersonnelBatch::class, 2);
    expect(CameraEnrollment::where('personnel_id', $personnel->id)->count())->toBe(3);
});

test('enrollAllToCamera chunks large batches', function () {
    Bus::fake([EnrollPersonnelBatch::class]);
    config(['hds.enrollment.batch_size' => 1000]);
    $camera = Camera::factory()->online()->create();
    Personnel::factory()->count(2500)->create();

    $service = app(CameraEnrollmentService::class);
    $service->enrollAllToCamera($camera);

    Bus::assertDispatched(EnrollPersonnelBatch::class, 3);
});

test('translateErrorCode maps known codes', function () {
    $service = app(CameraEnrollmentService::class);

    expect($service->translateErrorCode(463))->toBe('Photo required for first enrollment')
        ->and($service->translateErrorCode(468))->toBe('No usable face detected in photo')
        ->and($service->translateErrorCode(999))->toBe('Enrollment failed. Try again or check camera connectivity.');
});

test('buildDeletePersonsPayload produces correct structure', function () {
    $service = app(CameraEnrollmentService::class);

    $payload = $service->buildDeletePersonsPayload(
        ['id1', 'id2'],
        'DeleteMsg001'
    );

    expect($payload)
        ->toHaveKeys(['messageId', 'operator', 'info'])
        ->and($payload['operator'])->toBe('DeletePersons')
        ->and($payload['info'])->toHaveCount(2)
        ->and($payload['info'][0])->toBe(['customId' => 'id1'])
        ->and($payload['info'][1])->toBe(['customId' => 'id2']);
});

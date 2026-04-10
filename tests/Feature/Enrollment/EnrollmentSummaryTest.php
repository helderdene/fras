<?php

use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;

test('personnel index returns camera enrollment summary', function () {
    $camera1 = Camera::factory()->online()->create(['name' => 'Camera Alpha']);
    $camera2 = Camera::factory()->offline()->create(['name' => 'Camera Beta']);
    $p1 = Personnel::factory()->create();
    $p2 = Personnel::factory()->create();
    $p3 = Personnel::factory()->create();

    CameraEnrollment::create(['camera_id' => $camera1->id, 'personnel_id' => $p1->id, 'status' => CameraEnrollment::STATUS_ENROLLED, 'enrolled_at' => now()]);
    CameraEnrollment::create(['camera_id' => $camera1->id, 'personnel_id' => $p2->id, 'status' => CameraEnrollment::STATUS_ENROLLED, 'enrolled_at' => now()]);
    CameraEnrollment::create(['camera_id' => $camera1->id, 'personnel_id' => $p3->id, 'status' => CameraEnrollment::STATUS_FAILED]);

    $response = $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('personnel.index'));

    $response->assertSuccessful();

    $cameraSummary = $response->original->getData()['page']['props']['cameraSummary'];

    $alpha = collect($cameraSummary)->firstWhere('name', 'Camera Alpha');
    expect($alpha['enrolled_count'])->toBe(2)
        ->and($alpha['failed_count'])->toBe(1)
        ->and($alpha['pending_count'])->toBe(0)
        ->and($alpha['total_count'])->toBe(3);

    $beta = collect($cameraSummary)->firstWhere('name', 'Camera Beta');
    expect($beta['enrolled_count'])->toBe(0)
        ->and($beta['total_count'])->toBe(3);
});

test('personnel index returns personnel with sync_status', function () {
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
        'enrolled_at' => now(),
    ]);

    $response = $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('personnel.index'));

    $response->assertSuccessful();

    $personnelData = $response->original->getData()['page']['props']['personnel'];
    $first = collect($personnelData)->firstWhere('id', $personnel->id);

    expect($first['sync_status'])->toBe('synced');
});

test('personnel sync_status shows failed when any camera failed', function () {
    $camera1 = Camera::factory()->online()->create();
    $camera2 = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    CameraEnrollment::create([
        'camera_id' => $camera1->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
        'enrolled_at' => now(),
    ]);
    CameraEnrollment::create([
        'camera_id' => $camera2->id,
        'personnel_id' => $personnel->id,
        'status' => CameraEnrollment::STATUS_FAILED,
    ]);

    $response = $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('personnel.index'));

    $response->assertSuccessful();

    $personnelData = $response->original->getData()['page']['props']['personnel'];
    $person = collect($personnelData)->firstWhere('id', $personnel->id);

    expect($person['sync_status'])->toBe('failed');
});

test('camera show returns enrolled personnel', function () {
    $camera = Camera::factory()->online()->create();
    $p1 = Personnel::factory()->create(['name' => 'Alice']);
    $p2 = Personnel::factory()->create(['name' => 'Bob']);

    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $p1->id,
        'status' => CameraEnrollment::STATUS_ENROLLED,
        'enrolled_at' => now(),
    ]);
    CameraEnrollment::create([
        'camera_id' => $camera->id,
        'personnel_id' => $p2->id,
        'status' => CameraEnrollment::STATUS_PENDING,
    ]);

    $response = $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('cameras.show', $camera));

    $response->assertSuccessful();

    $enrolledPersonnel = $response->original->getData()['page']['props']['enrolledPersonnel'];

    expect($enrolledPersonnel)->toHaveCount(2);

    $alice = collect($enrolledPersonnel)->firstWhere('name', 'Alice');
    expect($alice['enrollment_status'])->toBe('enrolled')
        ->and($alice['enrolled_at'])->not->toBeNull();

    $bob = collect($enrolledPersonnel)->firstWhere('name', 'Bob');
    expect($bob['enrollment_status'])->toBe('pending');
});

<?php

use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

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

<?php

use App\Models\Camera;
use App\Models\Personnel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

test('personnel model has correct fillable attributes', function () {
    $personnel = Personnel::create([
        'custom_id' => 'EMP-0001',
        'name' => 'Test Person',
        'person_type' => 0,
        'gender' => 1,
        'birthday' => '1990-01-15',
        'id_card' => '1234567890',
        'phone' => '09171234567',
        'address' => '123 Main St',
        'photo_path' => null,
        'photo_hash' => null,
    ]);

    expect($personnel)->toBeInstanceOf(Personnel::class)
        ->and($personnel->custom_id)->toBe('EMP-0001')
        ->and($personnel->name)->toBe('Test Person')
        ->and($personnel->person_type)->toBe(0)
        ->and($personnel->gender)->toBe(1);
});

test('personnel model casts person_type as integer, gender as integer, birthday as date', function () {
    $personnel = Personnel::factory()->create([
        'person_type' => 1,
        'gender' => 0,
        'birthday' => '1995-06-15',
    ]);

    $personnel->refresh();

    expect($personnel->person_type)->toBeInt()
        ->and($personnel->gender)->toBeInt()
        ->and($personnel->birthday)->toBeInstanceOf(CarbonImmutable::class);
});

test('personnel factory creates valid records with required fields', function () {
    $personnel = Personnel::factory()->create();

    expect($personnel->exists)->toBeTrue()
        ->and($personnel->custom_id)->not->toBeEmpty()
        ->and($personnel->name)->not->toBeEmpty()
        ->and($personnel->person_type)->toBe(0);
});

test('personnel factory blockList state sets person_type to 1', function () {
    $personnel = Personnel::factory()->blockList()->create();

    expect($personnel->person_type)->toBe(1);
});

test('store personnel request validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('personnel.store'), [])
        ->assertSessionHasErrors(['custom_id', 'name', 'person_type', 'photo']);
});

test('store personnel request enforces unique custom_id', function () {
    $user = User::factory()->create();
    Personnel::factory()->create(['custom_id' => 'DUPLICATE']);

    $this->actingAs($user)
        ->post(route('personnel.store'), [
            'custom_id' => 'DUPLICATE',
            'name' => 'New Person',
            'person_type' => 0,
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])
        ->assertSessionHasErrors(['custom_id']);
});

test('store personnel request validates photo is image and mimes jpeg,png', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('personnel.store'), [
            'custom_id' => 'EMP-9999',
            'name' => 'Test',
            'person_type' => 0,
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['photo']);
});

test('update personnel request allows same custom_id for own record', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create(['custom_id' => 'OWN-ID']);

    $this->actingAs($user)
        ->put(route('personnel.update', $personnel), [
            'custom_id' => 'OWN-ID',
            'name' => 'Updated Name',
            'person_type' => 0,
        ])
        ->assertSessionHasNoErrors();
});

test('update personnel request makes photo optional', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create();

    $this->actingAs($user)
        ->put(route('personnel.update', $personnel), [
            'custom_id' => $personnel->custom_id,
            'name' => 'Updated Name',
            'person_type' => 0,
        ])
        ->assertSessionHasNoErrors();
});

test('can list personnel', function () {
    $user = User::factory()->create();
    Personnel::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('personnel.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('personnel/Index')
            ->has('personnel', 3)
        );
});

test('can view create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('personnel.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('personnel/Create')
        );
});

test('can store a personnel record', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('personnel.store'), [
            'custom_id' => 'EMP-5550',
            'name' => 'New Person',
            'person_type' => 0,
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])
        ->assertRedirect(route('personnel.index'));

    expect(Personnel::where('custom_id', 'EMP-5550')->exists())->toBeTrue();
});

test('can view a personnel record', function () {
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create();
    Camera::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('personnel.show', $personnel))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('personnel/Show')
            ->has('personnel')
            ->has('cameras', 2)
        );
});

test('can view edit form', function () {
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create();

    $this->actingAs($user)
        ->get(route('personnel.edit', $personnel))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('personnel/Edit')
            ->has('personnel')
        );
});

test('can update a personnel record', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create();

    $this->actingAs($user)
        ->put(route('personnel.update', $personnel), [
            'custom_id' => $personnel->custom_id,
            'name' => 'Renamed Person',
            'person_type' => 1,
        ])
        ->assertRedirect(route('personnel.show', $personnel));

    expect($personnel->fresh()->name)->toBe('Renamed Person');
});

test('can delete a personnel record and photo file is cleaned up', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    Storage::disk('public')->put('personnel/test-photo.jpg', 'fake-content');
    $personnel = Personnel::factory()->withPhoto()->create();

    $this->actingAs($user)
        ->delete(route('personnel.destroy', $personnel))
        ->assertRedirect(route('personnel.index'));

    expect(Personnel::find($personnel->id))->toBeNull()
        ->and(Storage::disk('public')->exists('personnel/test-photo.jpg'))->toBeFalse();
});

test('requires authentication for personnel routes', function () {
    $this->get(route('personnel.index'))
        ->assertRedirect(route('login'));
});

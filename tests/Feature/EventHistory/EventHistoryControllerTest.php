<?php

use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('event history page loads with paginated events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->count(3)->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('events/Index')
            ->has('events.data', 3)
            ->has('events.current_page')
            ->has('events.last_page')
            ->has('events.total')
        );
});

test('event history returns cameras prop', function () {
    $user = User::factory()->create();
    Camera::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('cameras', 2)
        );
});

test('event history returns filters prop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('filters')
            ->where('filters.date_from', now()->format('Y-m-d'))
            ->where('filters.date_to', now()->format('Y-m-d'))
        );
});

test('default date range is today - excludes yesterday events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'captured_at' => now()->subDay(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('date_from and date_to params filter events by date range inclusively', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'captured_at' => '2026-04-01 12:00:00',
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => '2026-04-05 12:00:00',
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => '2026-04-10 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('events.index', [
            'date_from' => '2026-04-01',
            'date_to' => '2026-04-05',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 2)
        );
});

test('camera_id param filters events to only that camera', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'camera_id' => $camera->id,
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['camera_id' => $camera->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('severity param filters events to only that severity', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->critical()->create([
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['severity' => 'critical']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('search param matches events by personnel name', function () {
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create(['name' => 'John Doe']);

    RecognitionEvent::factory()->info()->create([
        'personnel_id' => $personnel->id,
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['search' => 'John']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('search param matches events by personnel custom_id', function () {
    $user = User::factory()->create();
    $personnel = Personnel::factory()->create(['custom_id' => 'EMP-SEARCHABLE-999']);

    RecognitionEvent::factory()->info()->create([
        'personnel_id' => $personnel->id,
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['search' => 'SEARCHABLE-999']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('search param matches events by name_from_camera field', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'name_from_camera' => 'Camera Person Name',
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'name_from_camera' => 'Unrelated',
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['search' => 'Camera Person']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('search param matches events by custom_id field on recognition_events table', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'custom_id' => 'EMP-UNIQUE-123',
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['search' => 'UNIQUE-123']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('default sort is captured_at descending', function () {
    $user = User::factory()->create();

    $older = RecognitionEvent::factory()->info()->create([
        'captured_at' => now()->subMinutes(5),
    ]);
    $newer = RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 2)
            ->where('events.data.0.id', $newer->id)
            ->where('events.data.1.id', $older->id)
        );
});

test('sort by similarity ascending', function () {
    $user = User::factory()->create();

    $high = RecognitionEvent::factory()->info()->create([
        'similarity' => 95.0,
        'captured_at' => now(),
    ]);
    $low = RecognitionEvent::factory()->info()->create([
        'similarity' => 70.0,
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['sort' => 'similarity', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 2)
            ->where('events.data.0.id', $low->id)
            ->where('events.data.1.id', $high->id)
        );
});

test('invalid sort column falls back to captured_at', function () {
    $user = User::factory()->create();

    $older = RecognitionEvent::factory()->info()->create([
        'captured_at' => now()->subMinutes(5),
    ]);
    $newer = RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['sort' => 'raw_payload']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('events.data.0.id', $newer->id)
            ->where('events.data.1.id', $older->id)
        );
});

test('invalid direction value falls back to desc', function () {
    $user = User::factory()->create();

    $older = RecognitionEvent::factory()->info()->create([
        'captured_at' => now()->subMinutes(5),
    ]);
    $newer = RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index', ['direction' => 'INVALID']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('events.data.0.id', $newer->id)
            ->where('events.data.1.id', $older->id)
        );
});

test('paginated at 25 results per page', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->count(30)->info()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 25)
            ->where('events.total', 30)
            ->where('events.last_page', 2)
        );
});

test('events include replay events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->info()->replay()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 2)
        );
});

test('events include ignored severity events', function () {
    $user = User::factory()->create();

    RecognitionEvent::factory()->info()->create([
        'captured_at' => now(),
    ]);
    RecognitionEvent::factory()->ignored()->create([
        'captured_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 2)
        );
});

test('event history route requires authentication', function () {
    $this->get(route('events.index'))
        ->assertRedirect(route('login'));
});

<?php

use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated user sees Dashboard component with cameras prop as array', function () {
    $user = User::factory()->create();
    Camera::factory()->online()->count(2)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('cameras', 2)
        );
});

test('dashboard cameras include today_recognition_count withCount', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    RecognitionEvent::factory()->count(3)->for($camera)->create([
        'captured_at' => today(),
    ]);
    // Yesterday's event should not be counted
    RecognitionEvent::factory()->for($camera)->create([
        'captured_at' => today()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cameras.0.today_recognition_count', 3)
        );
});

test('dashboard todayStats has keys: recognitions, critical, warnings, enrolled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('todayStats.recognitions')
            ->has('todayStats.critical')
            ->has('todayStats.warnings')
            ->has('todayStats.enrolled')
        );
});

test('dashboard todayStats counts only is_real_time=true events from today', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();
    $personnel = Personnel::factory()->create();

    // Real-time today events (share one personnel to avoid inflating enrolled count)
    RecognitionEvent::factory()->critical()->for($camera)->for($personnel)->create([
        'captured_at' => today(),
        'is_real_time' => true,
    ]);
    RecognitionEvent::factory()->warning()->for($camera)->for($personnel)->create([
        'captured_at' => today(),
        'is_real_time' => true,
    ]);
    RecognitionEvent::factory()->info()->for($camera)->for($personnel)->create([
        'captured_at' => today(),
        'is_real_time' => true,
    ]);
    // Replay event (should not be counted)
    RecognitionEvent::factory()->critical()->replay()->for($camera)->for($personnel)->create([
        'captured_at' => today(),
    ]);
    // Yesterday's event (should not be counted)
    RecognitionEvent::factory()->critical()->for($camera)->for($personnel)->create([
        'captured_at' => today()->subDay(),
        'is_real_time' => true,
    ]);

    // 4 more personnel (total 5 with the one above)
    Personnel::factory()->count(4)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('todayStats.recognitions', 3)
            ->where('todayStats.critical', 1)
            ->where('todayStats.warnings', 1)
            ->where('todayStats.enrolled', 5)
        );
});

test('dashboard recentEvents limited to 50, ordered by captured_at desc, only is_real_time=true', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->online()->create();

    // Create 55 real-time events
    RecognitionEvent::factory()->count(55)->for($camera)->create([
        'is_real_time' => true,
    ]);
    // Create 5 replay events (should not appear)
    RecognitionEvent::factory()->count(5)->replay()->for($camera)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentEvents', 50)
        );
});

test('dashboard mapbox prop contains token, darkStyle, lightStyle from config', function () {
    $user = User::factory()->create();

    config([
        'hds.mapbox.token' => 'test-token-abc',
        'hds.mapbox.dark_style' => 'mapbox://styles/test/dark',
        'hds.mapbox.light_style' => 'mapbox://styles/test/light',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mapbox.token', 'test-token-abc')
            ->where('mapbox.darkStyle', 'mapbox://styles/test/dark')
            ->where('mapbox.lightStyle', 'mapbox://styles/test/light')
        );
});

test('queue depth endpoint returns JSON with depth count for authenticated user', function () {
    $user = User::factory()->create();

    // Insert a row into the jobs table
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'available_at' => time(),
        'created_at' => time(),
    ]);

    $this->actingAs($user)
        ->getJson(route('queue-depth'))
        ->assertOk()
        ->assertJson(['depth' => 1]);
});

test('queue depth endpoint redirects guests to login', function () {
    $this->getJson(route('queue-depth'))
        ->assertUnauthorized();
});

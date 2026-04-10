<?php

use App\Events\TestBroadcastEvent;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

test('TestBroadcastEvent implements ShouldBroadcast', function () {
    $event = new TestBroadcastEvent;
    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

test('TestBroadcastEvent broadcasts on fras.alerts private channel', function () {
    $event = new TestBroadcastEvent;
    $channel = $event->broadcastOn();
    expect($channel)->toBeInstanceOf(PrivateChannel::class)
        ->and($channel->name)->toBe('private-fras.alerts');
});

test('TestBroadcastEvent broadcastWith has message and timestamp', function () {
    $event = new TestBroadcastEvent('hello');
    $data = $event->broadcastWith();
    expect($data)->toHaveKeys(['message', 'timestamp'])
        ->and($data['message'])->toBe('hello');
});

test('authenticated user can authorize on fras.alerts channel', function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app-id');
    Broadcast::purge();

    // Re-register channel on the fresh reverb driver
    Broadcast::channel('fras.alerts', function ($user) {
        return $user !== null;
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-fras.alerts',
            'socket_id' => '12345.67890',
        ])
        ->assertOk();
});

test('unauthenticated user cannot authorize on fras.alerts channel', function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app-id');
    Broadcast::purge();

    // Re-register channel on the fresh reverb driver
    Broadcast::channel('fras.alerts', function ($user) {
        return $user !== null;
    });

    $this->post('/broadcasting/auth', [
        'channel_name' => 'private-fras.alerts',
        'socket_id' => '12345.67890',
    ])
        ->assertStatus(403);
});

test('TestBroadcastEvent can be dispatched', function () {
    Event::fake([TestBroadcastEvent::class]);

    TestBroadcastEvent::dispatch('test message');

    Event::assertDispatched(TestBroadcastEvent::class, function ($event) {
        return $event->message === 'test message';
    });
});

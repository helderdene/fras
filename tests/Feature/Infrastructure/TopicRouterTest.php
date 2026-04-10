<?php

use App\Mqtt\Contracts\MqttHandler;
use App\Mqtt\Handlers\AckHandler;
use App\Mqtt\Handlers\HeartbeatHandler;
use App\Mqtt\Handlers\OnlineOfflineHandler;
use App\Mqtt\Handlers\RecognitionHandler;
use App\Mqtt\TopicRouter;
use Illuminate\Support\Facades\Log;

test('topic router dispatches Rec topic to RecognitionHandler', function () {
    $handler = Mockery::mock(RecognitionHandler::class, MqttHandler::class);
    $handler->shouldReceive('handle')->once()->with('mqtt/face/ABC123/Rec', '{}');
    $this->app->instance(RecognitionHandler::class, $handler);

    $router = new TopicRouter;
    $router->dispatch('mqtt/face/ABC123/Rec', '{}');
});

test('topic router dispatches Ack topic to AckHandler', function () {
    $handler = Mockery::mock(AckHandler::class, MqttHandler::class);
    $handler->shouldReceive('handle')->once()->with('mqtt/face/DEV001/Ack', '{}');
    $this->app->instance(AckHandler::class, $handler);

    $router = new TopicRouter;
    $router->dispatch('mqtt/face/DEV001/Ack', '{}');
});

test('topic router dispatches basic topic to OnlineOfflineHandler', function () {
    $handler = Mockery::mock(OnlineOfflineHandler::class, MqttHandler::class);
    $handler->shouldReceive('handle')->once()->with('mqtt/face/basic', '{}');
    $this->app->instance(OnlineOfflineHandler::class, $handler);

    $router = new TopicRouter;
    $router->dispatch('mqtt/face/basic', '{}');
});

test('topic router dispatches heartbeat topic to HeartbeatHandler', function () {
    $handler = Mockery::mock(HeartbeatHandler::class, MqttHandler::class);
    $handler->shouldReceive('handle')->once()->with('mqtt/face/heartbeat', '{}');
    $this->app->instance(HeartbeatHandler::class, $handler);

    $router = new TopicRouter;
    $router->dispatch('mqtt/face/heartbeat', '{}');
});

test('topic router logs warning for unmatched topic', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Unmatched MQTT topic', ['topic' => 'mqtt/face/unknown/xyz/bad']);

    $router = new TopicRouter;
    $router->dispatch('mqtt/face/unknown/xyz/bad', '{}');
});

test('all handlers implement MqttHandler interface', function () {
    expect(new RecognitionHandler)->toBeInstanceOf(MqttHandler::class)
        ->and(new AckHandler)->toBeInstanceOf(MqttHandler::class)
        ->and(new OnlineOfflineHandler)->toBeInstanceOf(MqttHandler::class)
        ->and(new HeartbeatHandler)->toBeInstanceOf(MqttHandler::class);
});

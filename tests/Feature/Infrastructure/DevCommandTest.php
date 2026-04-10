<?php

test('composer dev script includes all required processes', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    $devScript = implode(' ', $composer['scripts']['dev']);

    expect($devScript)->toContain('queue:listen')
        ->and($devScript)->toContain('pail')
        ->and($devScript)->toContain('npm run dev')
        ->and($devScript)->toContain('reverb:start')
        ->and($devScript)->toContain('fras:mqtt-listen')
        ->and($devScript)->not->toContain('artisan serve');
});

test('composer dev script uses concurrently with named processes', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    $devScript = implode(' ', $composer['scripts']['dev']);

    expect($devScript)->toContain('concurrently')
        ->and($devScript)->toContain('--names=')
        ->and($devScript)->toContain('--kill-others');
});

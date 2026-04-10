<?php

test('supervisor mqtt config exists and has correct command', function () {
    $config = file_get_contents(base_path('deploy/supervisor/hds-mqtt.conf'));
    expect($config)->toContain('[program:hds-mqtt]')
        ->and($config)->toContain('command=php /var/www/hds/artisan fras:mqtt-listen')
        ->and($config)->toContain('autostart=true')
        ->and($config)->toContain('autorestart=true');
});

test('supervisor reverb config exists and has correct command', function () {
    $config = file_get_contents(base_path('deploy/supervisor/hds-reverb.conf'));
    expect($config)->toContain('[program:hds-reverb]')
        ->and($config)->toContain('command=php /var/www/hds/artisan reverb:start')
        ->and($config)->toContain('autostart=true')
        ->and($config)->toContain('autorestart=true');
});

test('supervisor queue config exists and has correct command', function () {
    $config = file_get_contents(base_path('deploy/supervisor/hds-queue.conf'));
    expect($config)->toContain('[program:hds-queue]')
        ->and($config)->toContain('command=php /var/www/hds/artisan queue:work')
        ->and($config)->toContain('autostart=true')
        ->and($config)->toContain('autorestart=true');
});

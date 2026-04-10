<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MQTT Connection
    |--------------------------------------------------------------------------
    |
    | Configuration for the MQTT broker connection. The FRAS MQTT listener
    | uses these settings to connect to the Mosquitto broker and subscribe
    | to camera topics.
    |
    */

    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('MQTT_PORT', 1883),
        'username' => env('MQTT_USERNAME') ?: null,
        'password' => env('MQTT_PASSWORD') ?: null,
        'client_id' => env('MQTT_CLIENT_ID', 'hds-fras-'.env('APP_ENV', 'local')),
        'topic_prefix' => env('MQTT_TOPIC_PREFIX', 'mqtt/face'),
        'keepalive' => (int) env('MQTT_KEEPALIVE', 30),
        'reconnect_delay' => (int) env('MQTT_RECONNECT_DELAY', 5000),
        'max_reconnect_attempts' => (int) env('MQTT_MAX_RECONNECT_ATTEMPTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep face crop and scene images before the scheduled
    | retention cleanup job removes them. The recognition_events rows
    | are preserved indefinitely; only the image files are removed.
    |
    */

    'retention' => [
        'scene_images_days' => (int) env('FRAS_SCENE_RETENTION_DAYS', 30),
        'face_crops_days' => (int) env('FRAS_FACE_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enrollment Sync
    |--------------------------------------------------------------------------
    |
    | Settings for pushing personnel records to cameras via MQTT.
    | batch_size limits the number of personnel per EditPersonsNew message.
    | ack_timeout_minutes controls how long to wait for an ACK before
    | marking the enrollment as timed out.
    |
    */

    'enrollment' => [
        'batch_size' => (int) env('FRAS_ENROLLMENT_BATCH_SIZE', 1000),
        'ack_timeout_minutes' => (int) env('FRAS_ACK_TIMEOUT_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Constraints
    |--------------------------------------------------------------------------
    |
    | Constraints for enrollment photos sent to cameras. Photos are
    | resized and compressed to meet these limits before being served
    | via HTTP for camera download.
    |
    */

    'photo' => [
        'max_dimension' => (int) env('FRAS_PHOTO_MAX_DIMENSION', 1080),
        'max_size_bytes' => (int) env('FRAS_PHOTO_MAX_SIZE', 1048576), // 1MB
        'jpeg_quality' => (int) env('FRAS_PHOTO_QUALITY', 85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for operational alerts. camera_offline_threshold is the
    | number of seconds without a heartbeat before a camera is marked
    | offline.
    |
    */

    'alerts' => [
        'camera_offline_threshold' => (int) env('FRAS_OFFLINE_THRESHOLD', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapbox
    |--------------------------------------------------------------------------
    |
    | Mapbox GL JS access token and custom studio style URLs for the
    | dashboard map. The dark_style and light_style should be Mapbox
    | Studio style URLs from the HelderDene account.
    |
    */

    'mapbox' => [
        'token' => env('MAPBOX_ACCESS_TOKEN', ''),
        'dark_style' => env('MAPBOX_DARK_STYLE', ''),
        'light_style' => env('MAPBOX_LIGHT_STYLE', ''),
    ],

];

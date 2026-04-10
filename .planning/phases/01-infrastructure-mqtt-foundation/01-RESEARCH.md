# Phase 1: Infrastructure & MQTT Foundation - Research

**Researched:** 2026-04-10
**Domain:** Laravel infrastructure services (MySQL, MQTT, WebSocket broadcasting, process management)
**Confidence:** HIGH

## Summary

Phase 1 establishes all foundational services for FRAS: switching the database from SQLite to MySQL, installing and configuring the MQTT client library, setting up Laravel Reverb for real-time WebSocket broadcasting, creating Supervisor production configs, and extending the dev orchestration command. No UI pages or business logic are included -- just the plumbing that every subsequent phase depends on.

All three new PHP packages (`php-mqtt/laravel-client` v1.8.0, `laravel/reverb` v1.10.0, `intervention/image-laravel` v1.5.9 + `intervention/image` v3.11.7) explicitly support Laravel 13 (`^13.0` in their `illuminate/*` requirements). The development machine has MySQL 9.6.0 running via Homebrew, Mosquitto 2.0.22 installed, PHP 8.4.19 with GD+Imagick+pdo_mysql extensions, and Herd 1.26.1 serving the app. Herd also has a built-in Reverb service (currently stopped) but we will use the `reverb:start` artisan command directly for more control.

**Primary recommendation:** Install packages via `composer require` and `npm install`, use `php artisan install:broadcasting --reverb` for the Reverb scaffolding, create the 4 FRAS migration files, build the `fras:mqtt-listen` artisan command skeleton, and wire up the concurrently dev script. Keep CI running on SQLite by maintaining a test-specific database configuration.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** MySQL everywhere -- dev environment uses MySQL via Herd's built-in MySQL service. No SQLite/MySQL divergence.
- **D-02:** Single database -- all tables (existing users/sessions/cache/jobs + new FRAS tables) in one MySQL database.
- **D-03:** All 4 core FRAS tables created upfront in Phase 1: cameras, personnel, camera_enrollments, recognition_events. Schema based on spec. Later phases may add columns.
- **D-04:** Update `.env.example` and `.env` to default to MySQL connection with FRAS database name.
- **D-05:** Single `fras:mqtt-listen` artisan command subscribes to all camera topics using wildcard patterns. Messages routed to handler classes based on topic pattern (topic router pattern).
- **D-06:** Auto-reconnect in process -- leverage php-mqtt/laravel-client's built-in reconnect with configurable backoff. Re-subscribe to all topics on reconnect. Supervisor only restarts on process crash.
- **D-07:** MQTT configuration (broker host, port, credentials, client ID, topic prefixes) lives in `config/hds.php` under an `mqtt` key -- not in a separate config file.
- **D-08:** Mosquitto broker is assumed to be available on the network. Phase 1 provides a setup guide/script in docs but does not automate broker provisioning.
- **D-09:** Single private WebSocket channel (`fras.alerts`) for all recognition events. Private channel requires Fortify auth -- only logged-in users receive events.
- **D-10:** Phase 1 includes a full round-trip broadcast test: fire an event via Reverb and confirm a Laravel Echo client receives it. Validates success criteria #3.
- **D-11:** Laravel Echo configured with Pusher adapter (Reverb is Pusher-compatible). Echo setup in `resources/js/app.ts` or a dedicated bootstrap file.
- **D-12:** Extend existing `composer run dev` concurrently command to include Reverb and MQTT listener processes. All processes (queue, pail, vite, reverb, mqtt-listener) in one terminal with color-coded output.
- **D-13:** Remove `php artisan serve` from dev command -- Herd serves the app at `https://fras.test`. Redundant process eliminated.
- **D-14:** Create Supervisor `.conf` files in `deploy/supervisor/` for production: MQTT listener, Reverb server, and queue worker. Checked into the repo, ready for deployment.
- **D-15:** `config/hds.php` is the unified FRAS configuration file. Covers: MQTT connection, retention windows, enrollment limits, alert thresholds, photo constraints, Mapbox tokens. All settings env-overridable.

### Claude's Discretion
- Table column types and indexes for the 4 FRAS migrations -- follow the spec and optimize for query patterns
- php-mqtt/laravel-client version selection -- use latest stable compatible with Laravel 13
- Intervention Image v3 installation -- include in Phase 1 composer require since it's a stack addition
- Reverb installation and config -- follow Laravel's official setup
- Handler class naming and namespace conventions -- follow existing app patterns

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| INFRA-01 | Application uses MySQL database for all FRAS data (cameras, personnel, events, enrollments) | MySQL 9.6.0 available via Homebrew, pdo_mysql extension loaded, `config/database.php` has MySQL config ready. Need to switch `DB_CONNECTION=mysql` and create 4 FRAS migrations. |
| INFRA-02 | MQTT broker (Mosquitto) is accessible from Laravel and camera subnet | Mosquitto 2.0.22 installed via Homebrew. `php-mqtt/laravel-client` v1.8.0 supports Laravel 13. Publishes config to `config/mqtt-client.php` -- but decision D-07 moves MQTT config into `config/hds.php`. |
| INFRA-03 | Laravel Reverb WebSocket server runs and broadcasts events to connected browsers | `laravel/reverb` v1.10.0 supports Laravel 13. Install via `php artisan install:broadcasting --reverb`. Herd has built-in Reverb service but we use artisan command for dev orchestration. |
| INFRA-04 | Long-running processes managed by Supervisor with autostart/autorestart | Supervisor config templates in spec section 8.2. Create `deploy/supervisor/` directory with `.conf` files for mqtt-listener, reverb, and queue-worker. |
| INFRA-05 | Development environment orchestrates all processes via concurrently | Existing `composer.json` dev script uses `npx concurrently`. Need to replace `php artisan serve` with Reverb and MQTT listener processes, keep queue and vite. |
</phase_requirements>

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| php-mqtt/laravel-client | v1.8.0 | MQTT client for subscribing to camera topics and publishing enrollment commands | Only maintained MQTT client for Laravel with facade, config, and service provider. Requires `^13.0` illuminate packages. [VERIFIED: composer show -a] |
| php-mqtt/client | v2.1+ | Underlying MQTT library (auto-required by laravel-client) | Provides MqttClient class with auto-reconnect, loop management, signal handling [VERIFIED: composer show -a] |
| laravel/reverb | v1.10.0 | WebSocket server for broadcasting real-time recognition alerts | Laravel's first-party WebSocket server. Pusher-compatible protocol. Requires `^13.0`. [VERIFIED: composer show -a] |
| intervention/image | v3.11.7 | Photo preprocessing (resize, compress, format conversion) | Industry standard PHP image processing. Required for enrollment photo prep. [VERIFIED: composer show -a] |
| intervention/image-laravel | v1.5.9 | Laravel integration for Intervention Image (service provider, facade) | Official Laravel bridge. Requires `^13.0` illuminate. [VERIFIED: composer show -a] |

### Supporting (Frontend)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| @laravel/echo-vue | v2.3.4 | Vue composables for Laravel Echo WebSocket subscriptions | Use for all Reverb channel subscriptions in Vue components [VERIFIED: npm view] |
| laravel-echo | v2.3.4 | Core Echo client library (peer dep of echo-vue) | Required by @laravel/echo-vue [VERIFIED: npm view] |
| pusher-js | v8.5.0 | Pusher protocol client (Reverb is Pusher-compatible) | Required by Laravel Echo for the Reverb broadcaster [VERIFIED: npm view] |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| php-mqtt/laravel-client | Direct Mosquitto PHP extension | Extension requires system-level install, harder to configure per-project, no Laravel integration |
| laravel/reverb | Pusher | External service with monthly costs, network dependency, not self-hosted |
| intervention/image | Direct GD/Imagick | Manual resizing/compression code, no fluent API, easy to get edge cases wrong |
| @laravel/echo-vue | Manual WebSocket | No automatic auth, channel management, reconnection handling |

**Installation:**
```bash
# PHP packages
composer require php-mqtt/laravel-client laravel/reverb intervention/image-laravel

# Frontend packages  
npm install laravel-echo @laravel/echo-vue pusher-js

# Reverb scaffolding (creates config/broadcasting.php, config/reverb.php, routes/channels.php)
php artisan install:broadcasting --reverb --no-interaction

# Publish MQTT config (we will merge into config/hds.php per D-07)
php artisan vendor:publish --provider="PhpMqtt\Client\MqttClientServiceProvider" --tag="config"
```

**Version verification:**
- php-mqtt/laravel-client: v1.8.0 (latest stable, requires illuminate `^13.0`) [VERIFIED: composer show -a, 2026-04-10]
- laravel/reverb: v1.10.0 (latest stable, requires illuminate `^13.0`) [VERIFIED: composer show -a, 2026-04-10]
- intervention/image: v3.11.7 (latest 3.x stable) [VERIFIED: composer show -a, 2026-04-10]
- intervention/image-laravel: v1.5.9 (latest 1.x stable, requires illuminate `^13.0`) [VERIFIED: composer show -a, 2026-04-10]
- @laravel/echo-vue: v2.3.4 [VERIFIED: npm view, 2026-04-10]
- laravel-echo: v2.3.4 [VERIFIED: npm view, 2026-04-10]
- pusher-js: v8.5.0 [VERIFIED: npm view, 2026-04-10]

## Architecture Patterns

### Recommended Project Structure (new files for Phase 1)
```
app/
├── Console/
│   └── Commands/
│       └── FrasMqttListenCommand.php     # Long-running MQTT listener
├── Events/
│   └── TestBroadcastEvent.php            # Test event for Reverb validation
├── Mqtt/
│   ├── TopicRouter.php                   # Routes MQTT messages to handlers by topic pattern
│   └── Handlers/                         # Handler stubs (skeleton only in Phase 1)
│       ├── RecognitionHandler.php        # mqtt/face/+/Rec  (stub)
│       ├── AckHandler.php               # mqtt/face/+/Ack  (stub)
│       ├── OnlineOfflineHandler.php      # mqtt/face/basic  (stub)
│       └── HeartbeatHandler.php          # mqtt/face/heartbeat (stub)
config/
├── hds.php                               # Unified FRAS configuration
├── broadcasting.php                      # Created by install:broadcasting
└── reverb.php                            # Created by reverb:install
database/
└── migrations/
    ├── YYYY_MM_DD_000001_create_cameras_table.php
    ├── YYYY_MM_DD_000002_create_personnel_table.php
    ├── YYYY_MM_DD_000003_create_recognition_events_table.php
    └── YYYY_MM_DD_000004_create_camera_enrollments_table.php
deploy/
└── supervisor/
    ├── hds-mqtt.conf
    ├── hds-reverb.conf
    └── hds-queue.conf
routes/
└── channels.php                          # Created by install:broadcasting
```

### Pattern 1: Topic Router Pattern (D-05)
**What:** A single MQTT subscriber dispatches messages to handler classes based on topic matching.
**When to use:** When one MQTT connection must handle multiple topic patterns with different processing logic.
**Example:**
```php
// Source: FRAS Spec section 5.3 + project decision D-05
// app/Mqtt/TopicRouter.php

namespace App\Mqtt;

class TopicRouter
{
    /** @var array<string, string> Topic regex => Handler class */
    private array $routes = [
        '#mqtt/face/[^/]+/Rec$#'       => Handlers\RecognitionHandler::class,
        '#mqtt/face/[^/]+/Ack$#'       => Handlers\AckHandler::class,
        '#^mqtt/face/basic$#'           => Handlers\OnlineOfflineHandler::class,
        '#^mqtt/face/heartbeat$#'       => Handlers\HeartbeatHandler::class,
    ];

    public function dispatch(string $topic, string $message): void
    {
        foreach ($this->routes as $pattern => $handlerClass) {
            if (preg_match($pattern, $topic)) {
                app($handlerClass)->handle($topic, $message);
                return;
            }
        }

        Log::warning('Unmatched MQTT topic', ['topic' => $topic]);
    }
}
```

### Pattern 2: Long-Running Artisan Command with Signal Handling
**What:** An artisan command that runs the MQTT event loop indefinitely with graceful shutdown.
**When to use:** For the `fras:mqtt-listen` command that must survive as a daemon.
**Example:**
```php
// Source: php-mqtt/client docs + FRAS Spec section 5.2
// app/Console/Commands/FrasMqttListenCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class FrasMqttListenCommand extends Command
{
    protected $signature = 'fras:mqtt-listen';
    protected $description = 'Subscribe to camera MQTT topics and process messages';

    public function handle(): int
    {
        $mqtt = MQTT::connection();

        // Enable auto-reconnect (D-06)
        $mqtt->setReconnectAutomatically(true);
        $mqtt->setMaxReconnectAttempts(10);
        $mqtt->setDelayBetweenReconnectAttempts(5000); // 5 seconds

        $router = app(\App\Mqtt\TopicRouter::class);

        // Subscribe to all topic patterns (spec section 5.3)
        $topics = [
            'mqtt/face/+/Rec',      // Recognition events
            'mqtt/face/+/Ack',      // Enrollment ACKs
            'mqtt/face/basic',      // Online/Offline
            'mqtt/face/heartbeat',  // Heartbeat
        ];

        foreach ($topics as $topic) {
            $mqtt->subscribe($topic, function (string $topic, string $message) use ($router) {
                $router->dispatch($topic, $message);
            }, 0); // QoS 0 per spec section 3.1
        }

        // Graceful shutdown on SIGTERM/SIGINT
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $mqtt->interrupt());
        pcntl_signal(SIGINT, fn () => $mqtt->interrupt());

        $this->info('MQTT listener started. Subscribed to ' . count($topics) . ' topic patterns.');

        $mqtt->loop(true);

        $mqtt->disconnect();
        $this->info('MQTT listener stopped gracefully.');

        return self::SUCCESS;
    }
}
```

### Pattern 3: Unified Config File (D-07, D-15)
**What:** Single `config/hds.php` housing all FRAS settings with env() defaults.
**When to use:** All FRAS-specific configuration including MQTT, retention, enrollment, photos, Mapbox.
**Example:**
```php
// config/hds.php
return [
    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('MQTT_PORT', 1883),
        'username' => env('MQTT_USERNAME', ''),
        'password' => env('MQTT_PASSWORD', ''),
        'client_id' => env('MQTT_CLIENT_ID', 'hds-fras-' . env('APP_ENV', 'local')),
        'topic_prefix' => env('MQTT_TOPIC_PREFIX', 'mqtt/face'),
        'keepalive' => (int) env('MQTT_KEEPALIVE', 30),
        'reconnect_delay' => (int) env('MQTT_RECONNECT_DELAY', 5000),
        'max_reconnect_attempts' => (int) env('MQTT_MAX_RECONNECT_ATTEMPTS', 10),
    ],

    'retention' => [
        'scene_images_days' => (int) env('FRAS_SCENE_RETENTION_DAYS', 30),
        'face_crops_days' => (int) env('FRAS_FACE_RETENTION_DAYS', 90),
    ],

    'enrollment' => [
        'batch_size' => (int) env('FRAS_ENROLLMENT_BATCH_SIZE', 1000),
        'ack_timeout_minutes' => (int) env('FRAS_ACK_TIMEOUT_MINUTES', 5),
    ],

    'photo' => [
        'max_dimension' => (int) env('FRAS_PHOTO_MAX_DIMENSION', 1080),
        'max_size_bytes' => (int) env('FRAS_PHOTO_MAX_SIZE', 1048576), // 1MB
        'jpeg_quality' => (int) env('FRAS_PHOTO_QUALITY', 85),
    ],

    'alerts' => [
        'camera_offline_threshold' => (int) env('FRAS_OFFLINE_THRESHOLD', 90), // seconds
    ],

    'mapbox' => [
        'token' => env('MAPBOX_ACCESS_TOKEN', ''),
        'dark_style' => env('MAPBOX_DARK_STYLE', ''),
        'light_style' => env('MAPBOX_LIGHT_STYLE', ''),
    ],
];
```

### Pattern 4: Laravel Echo Setup with @laravel/echo-vue
**What:** Configure Echo in the Vue app using the framework-specific package.
**When to use:** Phase 1 sets up the bootstrap; later phases use `useEcho` composable for channel subscriptions.
**Example:**
```typescript
// resources/js/echo.ts (or inline in app.ts)
// Source: Laravel 13.x Broadcasting docs
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Pattern 5: Private Channel Authorization (D-09)
**What:** Authorization callback in `routes/channels.php` ensuring only authenticated users receive alerts.
**When to use:** The `fras.alerts` private channel.
**Example:**
```php
// routes/channels.php
// Source: Laravel 13.x Broadcasting docs
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('fras.alerts', function ($user) {
    return $user !== null; // Any authenticated user can listen
});
```

### Anti-Patterns to Avoid
- **Separate config files per service:** Do NOT create `config/mqtt.php` separately. Decision D-07 mandates all FRAS config lives in `config/hds.php`. The published `config/mqtt-client.php` should be deleted after extracting its structure, and the MQTT connection configured programmatically using `config/hds.php` values.
- **Using `php artisan serve` in dev:** Decision D-13 explicitly removes this. Herd serves the app.
- **SQLite in development:** Decision D-01 mandates MySQL everywhere. Do not leave SQLite as default.
- **Running Herd's built-in Reverb service:** Use `php artisan reverb:start` in the concurrently command for consistency with production Supervisor config and to see output in the dev terminal.
- **Clean session with auto-reconnect:** php-mqtt/client docs warn that auto-reconnect cannot be used with the clean session flag. Use persistent sessions for the MQTT connection.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| MQTT pub/sub | Raw socket/stream code | php-mqtt/laravel-client | Protocol compliance, QoS handling, reconnection, keep-alive pings |
| WebSocket server | Custom WebSocket with Ratchet | Laravel Reverb | Channel authorization, Pusher protocol compatibility, Laravel ecosystem integration |
| Image resize/compress | Direct GD/Imagick calls | Intervention Image v3 | Edge cases (EXIF rotation, ICC profiles, format detection), fluent API, tested at scale |
| Process orchestration | Custom bash scripts | Supervisor (prod) + concurrently (dev) | Process monitoring, auto-restart, log management, signal forwarding |
| Echo client setup | Manual WebSocket in JS | @laravel/echo-vue | Automatic auth, channel management, presence tracking, reconnection |

**Key insight:** Every "foundational service" in this phase has a battle-tested package. The complexity is in wiring them together correctly, not in implementing any one piece.

## Common Pitfalls

### Pitfall 1: MQTT Config File Collision
**What goes wrong:** `php-mqtt/laravel-client` publishes its own `config/mqtt-client.php`. If this file exists alongside `config/hds.php`, there's confusion about which config is used.
**Why it happens:** The MQTT facade's service provider reads `config/mqtt-client.php` by default.
**How to avoid:** After publishing the MQTT config to understand its structure, delete `config/mqtt-client.php` and programmatically configure the MQTT connection in the artisan command using values from `config/hds.php`. Alternatively, keep `config/mqtt-client.php` but have it read from `config('hds.mqtt.*')` values. The cleanest approach: keep the `mqtt-client.php` config file but set its values to read from `hds.mqtt.*` env vars so the facade still works.
**Warning signs:** MQTT connects with wrong credentials or to wrong host.

### Pitfall 2: CI Test Suite Breaks After MySQL Switch
**What goes wrong:** CI workflow uses `.env.example` which will now default to MySQL. CI runners don't have MySQL.
**Why it happens:** Decision D-04 changes `.env.example` to default to MySQL.
**How to avoid:** CI test workflow should explicitly set `DB_CONNECTION=sqlite` in the test step, or use a `.env.testing` file that overrides back to SQLite. GitHub Actions can also spin up a MySQL service container. The simplest approach: keep SQLite for CI tests by adding `DB_CONNECTION=sqlite` to the test step's env vars.
**Warning signs:** CI pipeline failures after merging Phase 1.

### Pitfall 3: Reverb Port Conflict
**What goes wrong:** Reverb defaults to port 8080. Herd's built-in Reverb service or another process may already bind that port.
**Why it happens:** Herd has a Reverb service on port 8080 (currently stopped but could be started accidentally).
**How to avoid:** Use a distinct port (e.g., 8085) or ensure Herd's Reverb service stays stopped. Set `REVERB_SERVER_PORT=8080` explicitly in `.env` and document that Herd's built-in Reverb should NOT be used.
**Warning signs:** "Address already in use" error when starting Reverb via artisan.

### Pitfall 4: MQTT Auto-Reconnect with Clean Session
**What goes wrong:** Auto-reconnect silently fails or behaves unpredictably.
**Why it happens:** php-mqtt/client docs explicitly state "The setting cannot be used together with the clean session flag."
**How to avoid:** When configuring the MQTT connection, set clean session to false (persistent session). Use a stable client ID so the broker recognizes reconnections.
**Warning signs:** MQTT listener stops receiving messages after a network hiccup but doesn't crash.

### Pitfall 5: Broadcast Driver Left as Log
**What goes wrong:** Events fire but no WebSocket messages arrive at the browser.
**Why it happens:** `.env` still has `BROADCAST_CONNECTION=log` after installing Reverb.
**How to avoid:** Update `.env` and `.env.example` to set `BROADCAST_CONNECTION=reverb` after installing Reverb.
**Warning signs:** Events appear in `storage/logs/laravel.log` instead of WebSocket.

### Pitfall 6: Missing channels.php Registration
**What goes wrong:** `install:broadcasting` creates `routes/channels.php` but it may not be loaded if `bootstrap/app.php` doesn't include the channels route file.
**Why it happens:** The `withRouting()` call in `bootstrap/app.php` doesn't include a `channels` parameter.
**How to avoid:** Verify that `install:broadcasting` adds the channels route to `bootstrap/app.php`. If not, manually add `channels: __DIR__.'/../routes/channels.php'` to the `withRouting()` call. Also ensure `BroadcastServiceProvider` is registered or broadcasting is bootstrapped.
**Warning signs:** Channel authorization always fails, 403 on private channel subscription.

### Pitfall 7: PersonNum Mismatch in Enrollment Payload
**What goes wrong:** Camera rejects enrollment with errcode 417.
**Why it happens:** The spec requires `PersonNum` to exactly equal the length of the `info` array.
**How to avoid:** In the `CameraEnrollmentService` (Phase 4), always compute `PersonNum` from `count($info)`, never pass it as a separate parameter.
**Warning signs:** All enrollment batches fail with 417.

## Code Examples

### Migration: cameras table (from spec section 4.1)
```php
// Source: FRAS Spec v1.1 section 4.1 + section 4.2
Schema::create('cameras', function (Blueprint $table) {
    $table->id();
    $table->string('device_id')->unique();  // MQTT device ID
    $table->string('name');
    $table->string('location_label');
    $table->decimal('latitude', 10, 7);     // WGS84
    $table->decimal('longitude', 10, 7);    // WGS84
    $table->dateTime('last_seen_at')->nullable();
    $table->boolean('is_online')->default(false);
    $table->timestamps();
});
```

### Migration: personnel table
```php
// Source: FRAS Spec v1.1 section 4.1
Schema::create('personnel', function (Blueprint $table) {
    $table->id();
    $table->string('custom_id', 48)->unique();
    $table->string('name', 32);
    $table->tinyInteger('person_type')->default(0);     // 0=allow, 1=block
    $table->tinyInteger('gender')->nullable();           // 0=male, 1=female
    $table->date('birthday')->nullable();
    $table->string('id_card', 32)->nullable();
    $table->string('phone', 32)->nullable();
    $table->string('address', 72)->nullable();
    $table->string('photo_path')->nullable();
    $table->string('photo_hash', 32)->nullable();        // MD5
    $table->timestamps();
});
```

### Migration: recognition_events table
```php
// Source: FRAS Spec v1.1 section 4.1 + 4.2
Schema::create('recognition_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('camera_id')->constrained('cameras');
    $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
    $table->string('custom_id')->nullable()->index();
    $table->string('camera_person_id')->nullable();
    $table->bigInteger('record_id');
    $table->tinyInteger('verify_status');        // 0-3
    $table->tinyInteger('person_type');           // 0 or 1
    $table->float('similarity');                  // 0-100
    $table->boolean('is_real_time');
    $table->string('name_from_camera')->nullable();
    $table->string('facesluice_id')->nullable();
    $table->string('id_card')->nullable();
    $table->string('phone')->nullable();
    $table->tinyInteger('is_no_mask');            // 0-2
    $table->json('target_bbox')->nullable();      // [x1,y1,x2,y2]
    $table->dateTime('captured_at');
    $table->string('face_image_path')->nullable();
    $table->string('scene_image_path')->nullable();
    $table->json('raw_payload');
    $table->timestamps();

    // Composite indexes for query patterns (spec section 4.2)
    $table->index(['camera_id', 'captured_at']);
    $table->index(['person_type', 'verify_status']);
});
```

### Migration: camera_enrollments table
```php
// Source: FRAS Spec v1.1 section 4.1
Schema::create('camera_enrollments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('camera_id')->constrained('cameras')->cascadeOnDelete();
    $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
    $table->dateTime('enrolled_at')->nullable();
    $table->string('photo_hash', 32)->nullable();
    $table->string('last_error')->nullable();
    $table->timestamps();

    $table->unique(['camera_id', 'personnel_id']);
});
```

### Supervisor Config (from spec section 8.2)
```ini
; deploy/supervisor/hds-mqtt.conf
; Source: FRAS Spec v1.1 section 8.2 (adapted for fras:mqtt-listen command name)
[program:hds-mqtt]
command=php /var/www/hds/artisan fras:mqtt-listen
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/hds-mqtt.log
stderr_logfile=/var/log/hds-mqtt-error.log
stopwaitsecs=10
```

### Updated composer.json dev script (D-12, D-13)
```json
"dev": [
    "Composer\\Config::disableProcessTimeout",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74,#34d399\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" \"php artisan reverb:start --debug\" \"php artisan fras:mqtt-listen\" --names=queue,logs,vite,reverb,mqtt --kill-others"
]
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Manual WebSocket (Ratchet) | Laravel Reverb | Laravel 11 (2024) | First-party, zero-config WebSocket server with Pusher protocol compatibility |
| laravel-echo (npm) | @laravel/echo-vue | Laravel Echo 2.x (2025) | Framework-specific composables with `configureEcho()` and `useEcho()` hooks |
| Intervention Image v2 | Intervention Image v3 | 2024 | Complete rewrite with driver-agnostic API, PHP 8.1+ |
| Separate Echo bootstrap.js | configureEcho() in app.ts | Inertia v3 / Echo v2 | No separate bootstrap file needed; configure Echo directly in app entry |
| Broadcasting needs BroadcastServiceProvider | install:broadcasting artisan command | Laravel 11+ | Auto-scaffolds everything (config, routes, provider registration) |

**Deprecated/outdated:**
- `Inertia::lazy()` / `LazyProp`: Removed in Inertia v3. Use `Inertia::optional()` instead. [CITED: CLAUDE.md]
- `router.cancel()`: Replaced by `router.cancelAll()` in Inertia v3. [CITED: CLAUDE.md]
- Axios in Inertia v3: Removed. Use built-in XHR client or install separately. [CITED: CLAUDE.md]
- `config/mqtt-client.php` published by php-mqtt: Should be adapted to read from `config/hds.php` per decision D-07. [VERIFIED: project decision]

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `php artisan install:broadcasting --reverb --no-interaction` will auto-add the channels route to bootstrap/app.php | Architecture Patterns | Need to manually add channels route, which is straightforward |
| A2 | php-mqtt/laravel-client's MQTT facade can be configured to use custom config keys (from hds.php instead of mqtt-client.php) | Architecture Patterns | May need to keep mqtt-client.php file that delegates to hds.php values |
| A3 | Herd's MySQL service is separate from the Homebrew MySQL instance detected | Environment Availability | Need to verify which MySQL server to connect to and its credentials |
| A4 | pcntl extension is available in PHP 8.4.19 for signal handling in MQTT listener | Code Examples | If missing, graceful shutdown on SIGTERM won't work; Supervisor will need to force-kill |
| A5 | @laravel/echo-vue's configureEcho() works with Inertia v3 SSR (no window reference in SSR context) | Standard Stack | May need to conditionally initialize Echo only on client side |

## Open Questions (RESOLVED)

1. **MySQL credentials for local development** (RESOLVED)
   - What we know: MySQL 9.6.0 is running via Homebrew. Root access requires a password.
   - What's unclear: What are the MySQL credentials? Does a `fras` database already exist?
   - Recommendation: Ask the user for MySQL root password, or create a dedicated `fras` user. The migration step will need a working database connection.
   - **Resolution:** Plan 01-01 Task 1 Step 4 sets `.env` with `DB_USERNAME=root` and `DB_PASSWORD=` (empty). Step 10 creates the `fras` database if it does not exist via `mysql -u root -e "CREATE DATABASE IF NOT EXISTS fras"`. If the user's MySQL requires a password, the executor will encounter a connection error and prompt the user for credentials.

2. **php-mqtt/laravel-client config integration with hds.php** (RESOLVED)
   - What we know: The package publishes `config/mqtt-client.php` and its facade reads from that file. Decision D-07 says MQTT config goes in `config/hds.php`.
   - What's unclear: Whether the MQTT facade can be told to read from a different config key, or whether we need to keep `mqtt-client.php` as a thin proxy.
   - Recommendation: Keep `config/mqtt-client.php` but have its values reference `config('hds.mqtt.*')`. This lets the facade work normally while centralizing config. Alternatively, bypass the facade and construct MqttClient manually in the artisan command.
   - **Resolution:** Plan 01-02 Task 1 Step 5 keeps `config/mqtt-client.php` as a thin proxy. It publishes the vendor config, then modifies its `connections.default` section to read from the same `env()` calls as `config/hds.php` (e.g., `env('MQTT_HOST', '127.0.0.1')`). Both config files share the same env vars, avoiding `config()` cross-references which break during config loading. The MQTT facade works normally.

3. **CI workflow after MySQL switch** (RESOLVED)
   - What we know: CI uses `.env.example` which will default to MySQL after D-04. CI runners don't have MySQL.
   - What's unclear: Whether to add a MySQL service to CI or keep tests on SQLite.
   - Recommendation: Keep CI on SQLite by setting `DB_CONNECTION=sqlite` in the test workflow env. All FRAS tables use standard MySQL-compatible column types that work on SQLite too. Only add MySQL to CI if MySQL-specific features (JSON queries, spatial indexes) are used.
   - **Resolution:** Plan 01-01 Task 1 Step 5 adds `DB_CONNECTION: sqlite` and `DB_DATABASE: ':memory:'` to the CI test step's `env` block in `.github/workflows/tests.yml`. CI continues using SQLite. No MySQL service container needed because all FRAS migration column types are SQLite-compatible.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| MySQL | INFRA-01 (database) | YES | 9.6.0 (Homebrew) | -- |
| Mosquitto | INFRA-02 (MQTT broker) | YES | 2.0.22 (Homebrew) | -- |
| PHP pdo_mysql | INFRA-01 (MySQL driver) | YES | 8.4.19 | -- |
| PHP GD | Photo preprocessing | YES | 8.4.19 | -- |
| PHP Imagick | Photo preprocessing (alt) | YES | 8.4.19 | GD is primary fallback |
| PHP mbstring | Intervention Image req | YES | 8.4.19 | -- |
| PHP exif | EXIF rotation handling | YES | 8.4.19 | -- |
| Laravel Herd | Dev server, SSL | YES | 1.26.1 | -- |
| Node.js | Frontend build | YES | v22.14.0 | -- |
| Supervisor | Production process mgmt | NO | -- | Not needed for dev; checked into repo for deployment |

**Missing dependencies with no fallback:**
- None. All required dev dependencies are available.

**Missing dependencies with fallback:**
- Supervisor is not installed on the dev machine (macOS), but this is expected. Supervisor configs are created as files in `deploy/supervisor/` for production Linux deployment. Dev uses `concurrently` instead.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 with pestphp/pest-plugin-laravel v4.1 |
| Config file | `tests/Pest.php` (exists, binds TestCase, RefreshDatabase commented out) |
| Quick run command | `php artisan test --compact --filter=TestName` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| INFRA-01 | MySQL connection works and all 4 FRAS tables exist after migration | feature | `php artisan test --compact --filter=FrasMigrationTest` | Wave 0 |
| INFRA-02 | MQTT client can connect, publish, and subscribe (mocked broker) | feature | `php artisan test --compact --filter=MqttConnectionTest` | Wave 0 |
| INFRA-03 | Reverb broadcast event fires and can be received | feature | `php artisan test --compact --filter=ReverbBroadcastTest` | Wave 0 |
| INFRA-04 | Supervisor config files are valid and reference correct commands | unit | `php artisan test --compact --filter=SupervisorConfigTest` | Wave 0 |
| INFRA-05 | Dev command script includes all required processes | unit | `php artisan test --compact --filter=DevCommandTest` | Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --compact --filter=Fras`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Infrastructure/FrasMigrationTest.php` -- covers INFRA-01: verify tables exist after migrate
- [ ] `tests/Feature/Infrastructure/MqttConnectionTest.php` -- covers INFRA-02: test MQTT connection config and command existence
- [ ] `tests/Feature/Infrastructure/ReverbBroadcastTest.php` -- covers INFRA-03: test event broadcasting through Reverb
- [ ] `tests/Unit/Infrastructure/SupervisorConfigTest.php` -- covers INFRA-04: validate config file contents
- [ ] `tests/Unit/Infrastructure/DevCommandTest.php` -- covers INFRA-05: verify composer dev script has all processes
- [ ] Enable `RefreshDatabase` in `tests/Pest.php` for Feature tests (currently commented out)
- [ ] Update `tests/Pest.php` to ensure MySQL-compatible tests work with SQLite in CI

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | Yes (existing) | Laravel Fortify -- already configured, no changes in Phase 1 |
| V3 Session Management | Yes (existing) | Laravel session driver (database) -- no changes |
| V4 Access Control | Yes | Private WebSocket channel `fras.alerts` requires Fortify auth (D-09) |
| V5 Input Validation | No (Phase 1) | No user input processing in this phase; validation comes in Phase 2+ |
| V6 Cryptography | No | No crypto operations in infrastructure phase |

### Known Threat Patterns for MQTT + WebSocket Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Unauthorized MQTT subscription | Information Disclosure | Mosquitto ACL config restricts topics by username (handled by broker, not app) |
| WebSocket channel eavesdropping | Information Disclosure | Private channel authorization via `routes/channels.php` (D-09) |
| MQTT message injection | Spoofing/Tampering | Broker-level auth (username/password per camera), QoS 0 accepted trade-off for v1 |
| Denial of service via MQTT flood | Denial of Service | Mosquitto rate limiting, Supervisor auto-restart on crash |

**Note:** MQTT TLS (mqtts://) is explicitly out of scope for v1 per project constraints. Plain MQTT on internal trusted network only.

## Project Constraints (from CLAUDE.md)

The following directives from CLAUDE.md apply to Phase 1 implementation:

- **Use `php artisan make:` commands** to create migrations, commands, and other files. Pass `--no-interaction`.
- **Run `vendor/bin/pint --dirty --format agent`** before finalizing any PHP changes.
- **Every change must be programmatically tested.** Write or update tests, then run them.
- **Do not change dependencies without approval** -- this phase explicitly adds approved packages (php-mqtt, Reverb, Intervention Image).
- **Follow existing code conventions** -- check sibling files for structure and naming. Controllers in `PascalCase` + `Controller`, commands use artisan make conventions.
- **Use PHP 8 constructor property promotion** and explicit return types.
- **Use PHPDoc blocks** over inline comments.
- **Stick to existing directory structure** -- `app/Console/Commands/` for artisan commands, `app/Events/` for broadcast events, new `app/Mqtt/` for MQTT handlers.
- **Do not create documentation files** unless explicitly requested.
- **Use `search-docs` tool** before making code changes for version-specific documentation.
- **Inertia v3 conventions**: `<script setup lang="ts">`, `defineProps<{}>()`, new event names (`httpException`, `networkError`).
- **Wayfinder**: Use Wayfinder route functions instead of hardcoded URLs (not directly relevant to Phase 1 but important for consistency).
- **Anonymous migration classes**: Use `return new class extends Migration` pattern.
- **Test with Pest**: Use `php artisan make:test --pest` for creating tests.
- **Remove `php artisan serve`** from dev command (D-13, aligns with Herd guideline in CLAUDE.md).

## Sources

### Primary (HIGH confidence)
- `composer show -a php-mqtt/laravel-client v1.8.0` -- confirmed Laravel 13 support (`^13.0`), version, requirements
- `composer show -a laravel/reverb v1.10.0` -- confirmed Laravel 13 support (`^13.0`), version, full dependency tree
- `composer show -a intervention/image-laravel v1.5.9` -- confirmed Laravel 13 support (`^13.0`), requires intervention/image `^3.11`
- `composer show -a intervention/image 3.11.7` -- confirmed version, PHP 8.1+ requirement
- `npm view @laravel/echo-vue` -- confirmed v2.3.4, maintained by Laravel team
- `npm view laravel-echo` -- confirmed v2.3.4
- `npm view pusher-js` -- confirmed v8.5.0
- `php -m` -- confirmed extensions: pdo_mysql, gd, imagick, mbstring, exif
- `mysql --version` -- confirmed MySQL 9.6.0 (Homebrew)
- `mosquitto -h` -- confirmed Mosquitto 2.0.22
- `docs/HDS-FRAS-Spec-v1.1.md` -- complete database schema, MQTT topics, supervisor configs, enrollment protocol

### Secondary (MEDIUM confidence)
- [Laravel 13.x Reverb docs](https://laravel.com/docs/13.x/reverb) -- installation, configuration, SSL, Supervisor setup
- [Laravel 13.x Broadcasting docs](https://laravel.com/docs/13.x/broadcasting) -- Echo setup, channel authorization, @laravel/echo-vue usage
- [php-mqtt/laravel-client GitHub README](https://github.com/php-mqtt/laravel-client) -- facade usage, config structure, publish/subscribe patterns
- [php-mqtt/client GitHub README](https://github.com/php-mqtt/client) -- auto-reconnect API, loop control, signal handling, connection settings

### Tertiary (LOW confidence)
- None -- all claims verified against package registries or official documentation.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all package versions verified against Composer/npm registries, Laravel 13 compatibility confirmed
- Architecture: HIGH -- patterns derived from FRAS spec, official docs, and locked decisions
- Pitfalls: HIGH -- derived from official documentation warnings and project-specific configuration conflicts

**Research date:** 2026-04-10
**Valid until:** 2026-05-10 (stable packages, unlikely to change)

---
phase: 01-infrastructure-mqtt-foundation
reviewed: 2026-04-10T00:00:00Z
depth: standard
files_reviewed: 37
files_reviewed_list:
  - .env.example
  - .github/workflows/tests.yml
  - .gitignore
  - app/Console/Commands/FrasMqttListenCommand.php
  - app/Events/TestBroadcastEvent.php
  - app/Mqtt/Contracts/MqttHandler.php
  - app/Mqtt/Handlers/AckHandler.php
  - app/Mqtt/Handlers/HeartbeatHandler.php
  - app/Mqtt/Handlers/OnlineOfflineHandler.php
  - app/Mqtt/Handlers/RecognitionHandler.php
  - app/Mqtt/TopicRouter.php
  - bootstrap/app.php
  - composer.json
  - config/broadcasting.php
  - config/database.php
  - config/hds.php
  - config/mqtt-client.php
  - config/reverb.php
  - database/migrations/2026_04_10_000001_create_cameras_table.php
  - database/migrations/2026_04_10_000002_create_personnel_table.php
  - database/migrations/2026_04_10_000003_create_recognition_events_table.php
  - database/migrations/2026_04_10_000004_create_camera_enrollments_table.php
  - deploy/supervisor/hds-mqtt.conf
  - deploy/supervisor/hds-queue.conf
  - deploy/supervisor/hds-reverb.conf
  - docs/mosquitto-setup.md
  - package.json
  - resources/js/app.ts
  - resources/js/types/global.d.ts
  - routes/channels.php
  - tests/Feature/Infrastructure/DevCommandTest.php
  - tests/Feature/Infrastructure/FrasMigrationTest.php
  - tests/Feature/Infrastructure/HdsConfigTest.php
  - tests/Feature/Infrastructure/MqttListenerTest.php
  - tests/Feature/Infrastructure/ReverbBroadcastTest.php
  - tests/Feature/Infrastructure/SupervisorConfigTest.php
  - tests/Feature/Infrastructure/TopicRouterTest.php
  - tests/Pest.php
findings:
  critical: 2
  warning: 5
  info: 4
  total: 11
status: issues_found
---

# Phase 01: Code Review Report

**Reviewed:** 2026-04-10T00:00:00Z
**Depth:** standard
**Files Reviewed:** 37
**Status:** issues_found

## Summary

This phase implements the MQTT infrastructure foundation: the listener command, topic router, stub handlers, database schema, Reverb broadcasting, Supervisor configs, and the full test suite. The overall design is solid — the handler contract, topic router pattern, and schema all look well-considered.

Two critical bugs will prevent the system from running correctly. The most serious is that `FrasMqttListenCommand` calls reconnect-configuration methods directly on the `MqttClient` instance, which does not expose those methods; this will crash at startup. The second is a regex anchor omission in `TopicRouter` that can cause wrong-handler dispatch on malformed topics.

Five warnings cover the hardcoded topic prefix in the router (config drift risk), the misleading comment about reconnect delay units, the overly permissive broadcast channel authorization, the wildcard Reverb `allowed_origins`, and pinned non-existent GitHub Actions versions that will break CI.

---

## Critical Issues

### CR-01: `FrasMqttListenCommand` Calls Non-Existent Methods on `MqttClient`

**File:** `app/Console/Commands/FrasMqttListenCommand.php:21-23`

**Issue:** `MQTT::connection()` returns a `PhpMqtt\Client\Contracts\MqttClient` instance. The three methods called on it — `setReconnectAutomatically()`, `setMaxReconnectAttempts()`, and `setDelayBetweenReconnectAttempts()` — exist only on `PhpMqtt\Client\ConnectionSettings`, not on `MqttClient`. Calling them on the client will throw a fatal `BadMethodCallException` and the command will crash immediately after establishing the connection, before subscribing to any topics.

The correct way to configure reconnect behaviour in `php-mqtt/laravel-client` is to set the `connection_settings.auto_reconnect` block in `config/mqtt-client.php`. The library reads that block and applies it to `ConnectionSettings` before returning the connected client.

**Fix:** Remove the three manual setter calls from the command and instead drive reconnect configuration entirely through `config/mqtt-client.php`:

```php
// config/mqtt-client.php  — update auto_reconnect block
'auto_reconnect' => [
    'enabled'                          => env('MQTT_AUTO_RECONNECT_ENABLED', true),
    'max_reconnect_attempts'           => env('MQTT_AUTO_RECONNECT_MAX_RECONNECT_ATTEMPTS', 10),
    // delay is in milliseconds
    'delay_between_reconnect_attempts' => env('MQTT_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS', 5000),
],
```

```php
// FrasMqttListenCommand.php — remove these three lines
$mqtt->setReconnectAutomatically(true);                                           // DELETE
$mqtt->setMaxReconnectAttempts(config('hds.mqtt.max_reconnect_attempts'));        // DELETE
$mqtt->setDelayBetweenReconnectAttempts(config('hds.mqtt.reconnect_delay'));      // DELETE

$prefix = config('hds.mqtt.topic_prefix');
```

---

### CR-02: `TopicRouter` Regex Patterns Missing `^` Start Anchor

**File:** `app/Mqtt/TopicRouter.php:16-17`

**Issue:** The Rec and Ack route patterns are missing the `^` start-of-string anchor:

```php
'#mqtt/face/[^/]+/Rec$#' => RecognitionHandler::class,
'#mqtt/face/[^/]+/Ack$#' => AckHandler::class,
```

Without `^`, the pattern matches any string that _ends with_ the correct suffix anywhere in the topic — e.g. `evil-prefix/mqtt/face/CAM1/Rec` would be dispatched to `RecognitionHandler`. In a production MQTT deployment with `allow_anonymous false`, unexpected topics from a misconfigured broker or a replay attack relying on a recognisable suffix could be dispatched to the wrong handler.

Additionally, all four patterns hardcode the literal string `mqtt/face` rather than reading from `config('hds.mqtt.topic_prefix')`. If the prefix changes via `MQTT_TOPIC_PREFIX`, the subscribe calls in `FrasMqttListenCommand` will use the new prefix but the router patterns will still match the old one, silently dropping every message.

**Fix:**

```php
private array $routes = [
    // Patterns use the literal prefix; see dispatch() which prepends config
    '#^mqtt/face/[^/]+/Rec$#' => RecognitionHandler::class,
    '#^mqtt/face/[^/]+/Ack$#' => AckHandler::class,
    '#^mqtt/face/basic$#'      => OnlineOfflineHandler::class,
    '#^mqtt/face/heartbeat$#'  => HeartbeatHandler::class,
];
```

For the config-driven prefix, build the patterns dynamically in the constructor:

```php
public function __construct()
{
    $prefix = preg_quote(config('hds.mqtt.topic_prefix', 'mqtt/face'), '#');
    $this->routes = [
        "#^{$prefix}/[^/]+/Rec$#"  => RecognitionHandler::class,
        "#^{$prefix}/[^/]+/Ack$#"  => AckHandler::class,
        "#^{$prefix}/basic$#"      => OnlineOfflineHandler::class,
        "#^{$prefix}/heartbeat$#"  => HeartbeatHandler::class,
    ];
}
```

---

## Warnings

### WR-01: `config/mqtt-client.php` Comment Claims Delay Is in Seconds — It Is Milliseconds

**File:** `config/mqtt-client.php:110`

**Issue:** The comment reads:

```
// Additional settings for the optional auto-reconnect. The delay between reconnect attempts is in seconds.
```

The `php-mqtt/client` library's `setDelayBetweenReconnectAttempts()` PHPDoc explicitly says **milliseconds**, and `config/hds.php` defaults to `5000` (clearly milliseconds). Anyone reading `config/mqtt-client.php` who follows the comment will set, for example, `MQTT_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS=5` expecting 5 seconds but getting 5 milliseconds — effectively disabling the backoff.

**Fix:** Correct the comment:

```php
// The delay between reconnect attempts is in milliseconds.
'auto_reconnect' => [
```

---

### WR-02: `fras.alerts` Channel Authorization Grants Access to All Authenticated Users

**File:** `routes/channels.php:9-11`

**Issue:**

```php
Broadcast::channel('fras.alerts', function ($user) {
    return $user !== null;
});
```

Any authenticated user — including low-privilege staff accounts — can subscribe to the face recognition alert channel and receive all recognition events including person names, biometric match scores, and captured-at timestamps. The system design implies that only operators and admins should receive these alerts.

**Fix:** Restrict to users with an appropriate role or ability. At minimum, document the intentional permissiveness with a comment if open access is desired. If roles are added in a later phase, add a placeholder now:

```php
Broadcast::channel('fras.alerts', function ($user) {
    // TODO(Phase-N): restrict to operator/admin roles when RBAC is implemented
    return $user !== null;
});
```

---

### WR-03: Reverb `allowed_origins` Set to Wildcard `['*']`

**File:** `config/reverb.php:85`

**Issue:**

```php
'allowed_origins' => ['*'],
```

Allowing all origins means a cross-origin web page could initiate a WebSocket connection to Reverb using a stolen session cookie and subscribe to private channels (subject to channel authorization). While channel auth provides a second layer, the wildcard removes the CORS pre-filter that would otherwise block requests from unknown origins.

**Fix:** Restrict to the application's own origin in production:

```php
'allowed_origins' => [env('APP_URL', 'http://localhost')],
```

---

### WR-04: GitHub Actions Pins to Non-Existent Action Versions

**File:** `.github/workflows/tests.yml:26,36`

**Issue:**

```yaml
uses: actions/checkout@v6
uses: actions/setup-node@v6
```

As of the knowledge cutoff (August 2025), both `actions/checkout` and `actions/setup-node` are on major version **v4**. `@v6` does not exist and will cause every CI run to fail with a `Unable to resolve action` error.

**Fix:**

```yaml
uses: actions/checkout@v4
uses: actions/setup-node@v4
```

---

### WR-05: `TopicRouter` Uses `app()` Helper — Prefer Dependency Injection

**File:** `app/Mqtt/TopicRouter.php:26`

**Issue:**

```php
app($handlerClass)->handle($topic, $message);
```

The `app()` global helper couples the router to the service container in a way that makes unit testing harder and violates the project's architecture guideline (single-purpose Action classes; DI over `app()`). The `TopicRouterTest` works around this by using `$this->app->instance()`, but that fragile mock setup is only necessary because of the static `app()` call.

**Fix:** Accept the container via constructor injection and use it to resolve handlers:

```php
use Illuminate\Contracts\Container\Container;

class TopicRouter
{
    public function __construct(private readonly Container $container) {}

    public function dispatch(string $topic, string $message): void
    {
        foreach ($this->routes as $pattern => $handlerClass) {
            if (preg_match($pattern, $topic)) {
                $this->container->make($handlerClass)->handle($topic, $message);
                return;
            }
        }
        Log::warning('Unmatched MQTT topic', ['topic' => $topic]);
    }
}
```

---

## Info

### IN-01: `tests/Pest.php` Contains Unused Stub Function and Custom Expectation

**File:** `tests/Pest.php:32-50`

**Issue:** Two items from the Pest starter scaffold were left in place:

- `expect()->extend('toBeOne', ...)` — a custom expectation that is not used in any test file.
- `function something() { // .. }` — an empty placeholder function that is not referenced anywhere.

**Fix:** Remove both. The `toBeOne` extension and the `something()` stub are dead code that add noise to every test run's bootstrap.

---

### IN-02: `composer.json` Dependency Version Differs from CLAUDE.md Documentation

**File:** `composer.json:14`

**Issue:** `CLAUDE.md` documents `intervention/image` as v3, but `composer.json` requires `intervention/image-laravel: ^4.0`. The package name itself (`image-laravel` instead of `image`) also changed between major versions. This is not a runtime bug (the composer.json is the source of truth), but the documentation mismatch will confuse anyone following `CLAUDE.md`.

**Fix:** Update `CLAUDE.md` to reflect `intervention/image-laravel ^4.0` or confirm which version is intended.

---

### IN-03: `MAPBOX_ACCESS_TOKEN` and Style Variables Not Exposed to Vite

**File:** `.env.example:85-87`, `config/hds.php:104-108`

**Issue:** `MAPBOX_ACCESS_TOKEN`, `MAPBOX_DARK_STYLE`, and `MAPBOX_LIGHT_STYLE` are defined in `.env.example` and read by `config/hds.php`, but they are never re-exported with a `VITE_` prefix. When the map dashboard is built, the frontend cannot access these values via `import.meta.env`.

**Fix:** Either add `VITE_MAPBOX_*` counterparts to `.env.example`:

```env
VITE_MAPBOX_ACCESS_TOKEN="${MAPBOX_ACCESS_TOKEN}"
VITE_MAPBOX_DARK_STYLE="${MAPBOX_DARK_STYLE}"
VITE_MAPBOX_LIGHT_STYLE="${MAPBOX_LIGHT_STYLE}"
```

Or serve them as Inertia shared props from `HandleInertiaRequests::share()`, which is the safer approach as it avoids embedding tokens in the client bundle.

---

### IN-04: `recognition_events` Uses `float` for `similarity` — Precision Loss Risk

**File:** `database/migrations/2026_04_10_000003_create_recognition_events_table.php:22`

**Issue:**

```php
$table->float('similarity'); // 0-100
```

`float` in Laravel maps to `FLOAT` (single-precision, ~7 decimal digits) in MySQL and `REAL` (8-byte IEEE 754) in SQLite. For a similarity percentage the precision is more than adequate, but `FLOAT` has known rounding artefacts in MySQL that can make exact comparisons (`WHERE similarity = 95.5`) unreliable. Using `decimal(5, 2)` documents the expected range and avoids floating-point artefacts when querying alert thresholds.

**Fix:**

```php
$table->decimal('similarity', 5, 2); // 000.00 – 100.00
```

---

_Reviewed: 2026-04-10T00:00:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

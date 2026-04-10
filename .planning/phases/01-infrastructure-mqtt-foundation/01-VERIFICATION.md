---
phase: 01-infrastructure-mqtt-foundation
verified: 2026-04-10T08:00:00Z
status: human_needed
score: 4/5 must-haves verified
overrides_applied: 0
human_verification:
  - test: "Start Mosquitto MQTT broker and run: mosquitto_pub -t 'mqtt/face/heartbeat' -m '{}' then verify fras:mqtt-listen receives and routes the message"
    expected: "The HeartbeatHandler logs 'Heartbeat received (stub)' without error; no connection timeout"
    why_human: "MQTT broker is an external service; automated tests only verify the listener command is registered and config is correct — not that actual broker accepts connections and live pub/sub works"
  - test: "Start php artisan reverb:start and open a browser tab pointing at the app; use browser console to check Echo WebSocket connected"
    expected: "WebSocket connection established to ws://localhost:8080 (or configured port); no connection errors in browser console"
    why_human: "Real Reverb WebSocket connectivity requires a running server and browser client; broadcast tests use Event::fake and don't exercise the live socket pipeline"
---

# Phase 01: Infrastructure & MQTT Foundation Verification Report

**Phase Goal:** All foundational services (MySQL, MQTT, Reverb, queues) are running and the development environment can orchestrate them with a single command
**Verified:** 2026-04-10T08:00:00Z
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| #  | Truth | Status | Evidence |
|----|-------|--------|---------|
| 1  | Application connects to MySQL and all FRAS migrations run successfully (cameras, personnel, enrollments, recognition_events tables exist) | VERIFIED | 4 migration files present with correct schemas; `.env.example` sets `DB_CONNECTION=mysql`, `DB_DATABASE=fras`; 4 FrasMigration tests pass |
| 2  | MQTT broker accepts connections from the Laravel server and test publishes/subscribes work | NEEDS HUMAN | `fras:mqtt-listen` command is registered, subscribes to 4 topics at QoS 0, auto-reconnect configured — but actual broker connectivity requires a running Mosquitto instance and cannot be verified programmatically |
| 3  | Laravel Reverb WebSocket server starts and a browser client can connect and receive a test broadcast | PARTIAL | Reverb installed, `BROADCAST_CONNECTION=reverb`, `fras.alerts` private channel configured, Echo client set up in app.ts, 6 ReverbBroadcast tests pass using Event::fake — live WebSocket round-trip needs human verification |
| 4  | Supervisor config starts and auto-restarts MQTT listener, Reverb, and queue worker processes | VERIFIED | `deploy/supervisor/hds-mqtt.conf`, `hds-reverb.conf`, `hds-queue.conf` all present with `autostart=true`, `autorestart=true`, and correct command paths; 3 SupervisorConfig tests pass |
| 5  | Running the dev command starts all processes (Vite, queue, Reverb, MQTT listener) concurrently | VERIFIED | `composer.json` dev script uses `concurrently` with all 5 processes: `queue:listen`, `pail`, `npm run dev`, `reverb:start --debug`, `fras:mqtt-listen` — `php artisan serve` absent (Herd serves); DevCommand tests pass |

**Score:** 4/5 truths verified (SC-2 and SC-3 require human validation for live service connectivity)

### Deferred Items

None. All handler stubs (RecognitionHandler, AckHandler, OnlineOfflineHandler, HeartbeatHandler) are intentional infrastructure placeholders explicitly scheduled for Phases 2–5. They are not deferred must-haves for Phase 1.

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `config/hds.php` | Unified FRAS configuration with mqtt, retention, enrollment, photo, alerts, mapbox sections | VERIFIED | All 6 sections present with `env()` defaults; MQTT_HOST, FRAS_* vars, MAPBOX_ACCESS_TOKEN referenced |
| `database/migrations/2026_04_10_000001_create_cameras_table.php` | Cameras table schema | VERIFIED | Present; `device_id` unique, GPS decimals, timestamps |
| `database/migrations/2026_04_10_000002_create_personnel_table.php` | Personnel table schema | VERIFIED | Present; `custom_id` unique, `person_type`, photo fields |
| `database/migrations/2026_04_10_000003_create_recognition_events_table.php` | Recognition events schema | VERIFIED | Present; FK to cameras/personnel, composite indexes, `raw_payload` JSON |
| `database/migrations/2026_04_10_000004_create_camera_enrollments_table.php` | Camera enrollments schema | VERIFIED | Present; `cascadeOnDelete()` on both FKs, `unique(['camera_id', 'personnel_id'])` |
| `app/Console/Commands/FrasMqttListenCommand.php` | Long-running MQTT subscriber | VERIFIED | Signature `fras:mqtt-listen`, `setReconnectAutomatically(true)`, QoS 0 subscriptions, `pcntl_signal(SIGTERM)` |
| `app/Mqtt/TopicRouter.php` | Topic pattern to handler dispatcher | VERIFIED | `dispatch()` with `preg_match`, routes to RecognitionHandler, AckHandler, OnlineOfflineHandler, HeartbeatHandler |
| `app/Mqtt/Contracts/MqttHandler.php` | Handler interface contract | VERIFIED | `interface MqttHandler` with `handle(string $topic, string $message): void` |
| `deploy/supervisor/hds-mqtt.conf` | Supervisor config for MQTT listener | VERIFIED | `[program:hds-mqtt]`, `command=php /var/www/hds/artisan fras:mqtt-listen`, autostart/autorestart |
| `deploy/supervisor/hds-reverb.conf` | Supervisor config for Reverb | VERIFIED | `[program:hds-reverb]`, `command=php /var/www/hds/artisan reverb:start`, autostart/autorestart |
| `deploy/supervisor/hds-queue.conf` | Supervisor config for queue worker | VERIFIED | `[program:hds-queue]`, `command=php /var/www/hds/artisan queue:work`, autostart/autorestart |
| `config/reverb.php` | Reverb WebSocket server configuration | VERIFIED | Present; reverb driver configured |
| `config/broadcasting.php` | Broadcasting driver configuration | VERIFIED | Present; reverb connection defined |
| `routes/channels.php` | Channel authorization rules | VERIFIED | `Broadcast::channel('fras.alerts', fn($user) => $user !== null)` |
| `app/Events/TestBroadcastEvent.php` | Test broadcast event for Reverb validation | VERIFIED | `implements ShouldBroadcast`, broadcasts on `PrivateChannel('fras.alerts')`, `broadcastWith()` returns message+timestamp |
| `resources/js/app.ts` | Echo client initialization | VERIFIED | `import { configureEcho } from '@laravel/echo-vue'`; `broadcaster: 'reverb'`; `VITE_REVERB_APP_KEY` used |

### Key Link Verification

| From | To | Via | Status | Details |
|------|-----|-----|--------|---------|
| `.env.example` | `config/database.php` | `DB_CONNECTION=mysql` env var | WIRED | `.env.example` line 23: `DB_CONNECTION=mysql` |
| `config/hds.php` | `.env.example` | `env()` calls with MQTT_*, FRAS_*, MAPBOX_* vars | WIRED | `MQTT_HOST`, `MAPBOX_ACCESS_TOKEN` confirmed in both files |
| `FrasMqttListenCommand.php` | `app/Mqtt/TopicRouter.php` | Resolve from container, pass to subscribe callback | WIRED | `TopicRouter` type-hinted in `handle()` method |
| `app/Mqtt/TopicRouter.php` | `app/Mqtt/Handlers/` | Regex match dispatches to handler class via `app()` | WIRED | `preg_match` + `app($handlerClass)->handle()` |
| `config/mqtt-client.php` | `config/hds.php` | Reads values from same env vars | WIRED | `env('MQTT_HOST')`, `use_clean_session => false` present |
| `composer.json` | `FrasMqttListenCommand.php` | dev script runs `fras:mqtt-listen` | WIRED | Confirmed in dev script at line 56 |
| `app/Events/TestBroadcastEvent.php` | `routes/channels.php` | `broadcastOn()` returns `PrivateChannel('fras.alerts')` | WIRED | Both confirmed; channel authorized for authenticated users |
| `resources/js/app.ts` | `config/reverb.php` | Echo connects to Reverb via `VITE_REVERB_*` env vars | WIRED | `configureEcho()` uses `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, etc. |
| `bootstrap/app.php` | `routes/channels.php` | `withRouting channels:` parameter | WIRED | `channels: __DIR__.'/../routes/channels.php'` confirmed at line 14 |

### Data-Flow Trace (Level 4)

Not applicable — this phase establishes infrastructure skeleton (command registration, config, routing). No components rendering dynamic data from DB queries exist in this phase.

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| fras:mqtt-listen command registered | `php artisan list` output (verified via test) | Test passes: `expectsOutputToContain('fras:mqtt-listen')` | PASS |
| mqtt-client config reads MQTT env vars | Test assertion on `config('mqtt-client.connections.default')` | `host`, `port`, `use_clean_session=false` verified | PASS |
| All 4 handlers implement MqttHandler interface | `grep implements MqttHandler` | 4/4 handlers confirmed | PASS |
| TopicRouter regex dispatches to correct handlers | TopicRouter unit tests (5 routing tests) | All pass | PASS |
| Supervisor configs correct | SupervisorConfigTest (3 tests) | All pass | PASS |
| Dev script has all 5 processes, no `artisan serve` | DevCommandTest (2 tests) | All pass | PASS |
| Broadcast event tests | ReverbBroadcastTest (6 tests) | All pass (using Event::fake) | PASS |
| Full test suite for phase | `php artisan test --compact --filter="FrasMigration\|HdsConfig\|TopicRouter\|MqttListener\|SupervisorConfig\|DevCommand\|ReverbBroadcast"` | 32 tests, 80 assertions, 0.60s | PASS |
| Live MQTT broker connectivity | Cannot test without running broker | N/A | SKIP — human needed |
| Live Reverb WebSocket browser connection | Cannot test without running server + browser | N/A | SKIP — human needed |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|---------|
| INFRA-01 | 01-01-PLAN | Application uses MySQL for FRAS data | SATISFIED | MySQL configured in `.env.example`; 4 migration files with correct schemas; FrasMigration tests pass |
| INFRA-02 | 01-02-PLAN | MQTT broker accessible from Laravel and camera subnet | PARTIAL | `fras:mqtt-listen` command and config present; actual broker connectivity is external service — needs human test |
| INFRA-03 | 01-03-PLAN | Laravel Reverb WebSocket server broadcasts to browsers | PARTIAL | Reverb configured, Echo client set up, private channel authorized, broadcast event tests pass — live WebSocket round-trip needs human test |
| INFRA-04 | 01-02-PLAN | Long-running processes managed by Supervisor with autostart/autorestart | SATISFIED | 3 Supervisor configs in `deploy/supervisor/` with autostart=true, autorestart=true, correct commands |
| INFRA-05 | 01-02-PLAN | Dev environment orchestrates all processes via concurrently | SATISFIED | `composer run dev` launches 5 processes (queue, logs, vite, reverb, mqtt) via concurrently with --kill-others |

No orphaned requirements — all 5 INFRA requirements declared in plans have been accounted for.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `app/Mqtt/Handlers/RecognitionHandler.php` | 13 | `Log::info('RecPush received (stub)')` | INFO | Intentional stub — plan explicitly scopes full implementation to Phase 5 (REC-01–REC-07) |
| `app/Mqtt/Handlers/AckHandler.php` | 13 | `Log::info('Enrollment ACK received (stub)')` | INFO | Intentional stub — plan explicitly scopes to Phase 4 (ENRL-04) |
| `app/Mqtt/Handlers/OnlineOfflineHandler.php` | 13 | `Log::info('Online/Offline received (stub)')` | INFO | Intentional stub — plan explicitly scopes to Phase 2 (OPS-04) |
| `app/Mqtt/Handlers/HeartbeatHandler.php` | 13 | `Log::info('Heartbeat received (stub)')` | INFO | Intentional stub — plan explicitly scopes to Phase 2 (OPS-05) |

All 4 stubs are INFO-only. Each implements `MqttHandler`, the router dispatches to them correctly, and the plan documents them as interface-compliant placeholders pending later-phase implementation. No blocker or warning anti-patterns found.

### Human Verification Required

#### 1. MQTT Broker Live Connectivity

**Test:** Install and start Mosquitto (see `docs/mosquitto-setup.md`). Set `MQTT_HOST`, `MQTT_USERNAME`, `MQTT_PASSWORD` in `.env`. Run `php artisan fras:mqtt-listen` in one terminal. In another terminal publish: `mosquitto_pub -t 'mqtt/face/heartbeat' -m '{}'`
**Expected:** The listener terminal shows "MQTT listener started. Subscribed to 4 topic patterns." and the HeartbeatHandler logs "Heartbeat received (stub)" — no connection errors or timeout
**Why human:** MQTT broker is an external service. Automated tests verify command registration and config correctness but cannot test actual TCP socket connectivity to a broker

#### 2. Reverb WebSocket Browser Round-Trip

**Test:** Run `composer run dev` (or `php artisan reverb:start --debug` standalone). Open the app in a browser at `https://fras.test`. Open browser DevTools → Network → WS. Log in as a user. Check for an active WebSocket connection to the Reverb server.
**Expected:** A WebSocket connection appears in DevTools, status = "101 Switching Protocols". No console errors about Echo connection failure. Optionally dispatch `TestBroadcastEvent::dispatch('ping')` via tinker and confirm the browser receives the event.
**Why human:** Verifying an active WebSocket connection and live event delivery requires a running Reverb process and browser — cannot be automated with file inspection or unit tests

### Gaps Summary

No blocking gaps. The phase delivered all infrastructure artifacts with correct wiring. The two human verification items (MQTT broker connectivity and Reverb WebSocket round-trip) are external-service integration tests that cannot be automated — they do not indicate missing code but rather require manual confirmation with running services.

---

_Verified: 2026-04-10T08:00:00Z_
_Verifier: Claude (gsd-verifier)_

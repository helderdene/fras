---
phase: 01-infrastructure-mqtt-foundation
plan: 02
subsystem: infra
tags: [mqtt, php-mqtt, supervisor, artisan-command, topic-routing, concurrently]

# Dependency graph
requires:
  - phase: 01-infrastructure-mqtt-foundation (plan 01)
    provides: config/hds.php with MQTT connection settings and env vars
provides:
  - fras:mqtt-listen artisan command with auto-reconnect and graceful shutdown
  - TopicRouter dispatching MQTT messages to 4 handler classes via regex
  - MqttHandler interface contract for all MQTT message handlers
  - 4 handler stubs (RecognitionHandler, AckHandler, OnlineOfflineHandler, HeartbeatHandler)
  - mqtt-client.php config with clean_session=false and MQTT v3.1.1
  - 3 Supervisor configs for production process management
  - Updated dev script with 5 concurrent processes
  - Mosquitto MQTT broker setup guide
affects: [02-camera-management, 03-personnel-enrollment, 04-recognition-pipeline, 05-realtime-dashboard]

# Tech tracking
tech-stack:
  added: [php-mqtt/laravel-client config published]
  patterns: [topic-router-pattern, handler-interface-contract, long-running-artisan-command, signal-handling]

key-files:
  created:
    - app/Console/Commands/FrasMqttListenCommand.php
    - app/Mqtt/Contracts/MqttHandler.php
    - app/Mqtt/TopicRouter.php
    - app/Mqtt/Handlers/RecognitionHandler.php
    - app/Mqtt/Handlers/AckHandler.php
    - app/Mqtt/Handlers/OnlineOfflineHandler.php
    - app/Mqtt/Handlers/HeartbeatHandler.php
    - config/mqtt-client.php
    - deploy/supervisor/hds-mqtt.conf
    - deploy/supervisor/hds-reverb.conf
    - deploy/supervisor/hds-queue.conf
    - docs/mosquitto-setup.md
    - tests/Feature/Infrastructure/TopicRouterTest.php
    - tests/Feature/Infrastructure/MqttListenerTest.php
    - tests/Feature/Infrastructure/SupervisorConfigTest.php
    - tests/Feature/Infrastructure/DevCommandTest.php
  modified:
    - composer.json

key-decisions:
  - "TopicRouter tests placed in Feature (not Unit) -- requires app container for service resolution via app()"
  - "MQTT protocol set to v3.1.1 (not v3.1) matching camera spec requirement"
  - "clean_session hardcoded to false (not env-configurable) since auto-reconnect requires it per Pitfall #4"
  - "Auth credentials use MQTT_USERNAME/MQTT_PASSWORD env vars (same as hds.php) instead of package default MQTT_AUTH_USERNAME/MQTT_AUTH_PASSWORD"

patterns-established:
  - "TopicRouter pattern: regex-based topic-to-handler dispatch via app container resolution"
  - "MqttHandler interface: all MQTT handlers implement handle(string $topic, string $message): void"
  - "Handler stub pattern: implement interface, log message, defer full logic to later phases"
  - "Supervisor config convention: deploy/supervisor/hds-{service}.conf"

requirements-completed: [INFRA-02, INFRA-04, INFRA-05]

# Metrics
duration: 5min
completed: 2026-04-10
---

# Phase 01 Plan 02: MQTT Listener & Topic Router Summary

**fras:mqtt-listen artisan command with TopicRouter dispatching to 4 handler stubs, Supervisor production configs, and 5-process dev orchestration via concurrently**

## Performance

- **Duration:** 5 min
- **Started:** 2026-04-10T07:00:26Z
- **Completed:** 2026-04-10T07:05:21Z
- **Tasks:** 2
- **Files modified:** 17

## Accomplishments
- MQTT listener command with auto-reconnect (5s delay, 10 max attempts), graceful SIGTERM/SIGINT shutdown, and 4 QoS-0 topic subscriptions
- TopicRouter with regex-based dispatch to RecognitionHandler, AckHandler, OnlineOfflineHandler, and HeartbeatHandler
- Production-ready Supervisor configs for mqtt-listener, reverb, and queue-worker processes
- Dev script updated to 5 concurrent processes (queue, logs, vite, reverb, mqtt) with `php artisan serve` removed (Herd serves the app)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create MQTT listener command, topic router, and handler stubs**
   - `6e2faee` (test) - RED: failing tests for TopicRouter and MqttListener
   - `052c2c1` (feat) - GREEN: implementation passing all tests
2. **Task 2: Create Supervisor configs and update dev orchestration script** - `f5907bf` (feat)

## Files Created/Modified
- `app/Console/Commands/FrasMqttListenCommand.php` - Long-running MQTT subscriber with signal handling and auto-reconnect
- `app/Mqtt/Contracts/MqttHandler.php` - Handler interface contract
- `app/Mqtt/TopicRouter.php` - Regex-based topic-to-handler dispatcher
- `app/Mqtt/Handlers/RecognitionHandler.php` - RecPush event handler stub
- `app/Mqtt/Handlers/AckHandler.php` - Enrollment ACK handler stub
- `app/Mqtt/Handlers/OnlineOfflineHandler.php` - Camera status handler stub
- `app/Mqtt/Handlers/HeartbeatHandler.php` - Heartbeat handler stub
- `config/mqtt-client.php` - Published and configured MQTT client config (clean_session=false, MQTT v3.1.1)
- `deploy/supervisor/hds-mqtt.conf` - Supervisor config for MQTT listener
- `deploy/supervisor/hds-reverb.conf` - Supervisor config for Reverb WebSocket server
- `deploy/supervisor/hds-queue.conf` - Supervisor config for queue worker
- `docs/mosquitto-setup.md` - Mosquitto broker installation and configuration guide
- `composer.json` - Updated dev script with 5 concurrent processes
- `tests/Feature/Infrastructure/TopicRouterTest.php` - 6 tests for topic routing
- `tests/Feature/Infrastructure/MqttListenerTest.php` - 2 tests for command registration and config
- `tests/Feature/Infrastructure/SupervisorConfigTest.php` - 3 tests for Supervisor configs
- `tests/Feature/Infrastructure/DevCommandTest.php` - 2 tests for dev script

## Decisions Made
- TopicRouter tests placed in Feature directory (not Unit as planned) because they require the Laravel app container for `app()` handler resolution and `Log` facade mocking
- Supervisor and DevCommand tests also moved to Feature because they use `base_path()` which requires the booted app
- MQTT protocol version set to 3.1.1 (camera firmware requires MQTT v3.1.1)
- `clean_session` hardcoded to `false` (not env-configurable) because auto-reconnect cannot work with clean sessions per php-mqtt/client documentation (Pitfall #4)
- Auth env vars use `MQTT_USERNAME`/`MQTT_PASSWORD` (matching hds.php) instead of the package default `MQTT_AUTH_USERNAME`/`MQTT_AUTH_PASSWORD`

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] TopicRouter tests moved from Unit to Feature**
- **Found during:** Task 1 (TDD GREEN phase)
- **Issue:** Tests used `$this->app->instance()` and `Log::shouldReceive()` which require the Laravel app container, unavailable in Unit tests
- **Fix:** Moved `TopicRouterTest.php` from `tests/Unit/Infrastructure/` to `tests/Feature/Infrastructure/`
- **Files modified:** tests/Feature/Infrastructure/TopicRouterTest.php
- **Verification:** All 6 tests pass
- **Committed in:** 052c2c1 (Task 1 commit)

**2. [Rule 1 - Bug] Supervisor and DevCommand tests moved from Unit to Feature**
- **Found during:** Task 2
- **Issue:** Tests used `base_path()` helper which requires the full Laravel application (calls `app()->basePath()`)
- **Fix:** Moved both test files from `tests/Unit/Infrastructure/` to `tests/Feature/Infrastructure/`
- **Files modified:** tests/Feature/Infrastructure/SupervisorConfigTest.php, tests/Feature/Infrastructure/DevCommandTest.php
- **Verification:** All 5 tests pass
- **Committed in:** f5907bf (Task 2 commit)

**3. [Rule 2 - Missing Critical] MQTT protocol set to v3.1.1**
- **Found during:** Task 1 (configuring mqtt-client.php)
- **Issue:** Published config defaulted to `MqttClient::MQTT_3_1` but project spec requires MQTT v3.1.1
- **Fix:** Changed protocol to `MqttClient::MQTT_3_1_1`
- **Files modified:** config/mqtt-client.php
- **Verification:** Config file contains correct protocol constant
- **Committed in:** 052c2c1 (Task 1 commit)

---

**Total deviations:** 3 auto-fixed (2 bugs, 1 missing critical)
**Impact on plan:** All auto-fixes necessary for correctness. No scope creep. Test directory change is a pattern established in Plan 01 (HdsConfigTest also in Feature).

## Known Stubs

| File | Description | Resolves In |
|------|-------------|-------------|
| `app/Mqtt/Handlers/RecognitionHandler.php` | Logs message only; full RecPush processing in Phase 04 | 04-recognition-pipeline |
| `app/Mqtt/Handlers/AckHandler.php` | Logs message only; full ACK correlation in Phase 03 | 03-personnel-enrollment |
| `app/Mqtt/Handlers/OnlineOfflineHandler.php` | Logs message only; full status tracking in Phase 02 | 02-camera-management |
| `app/Mqtt/Handlers/HeartbeatHandler.php` | Logs message only; full heartbeat tracking in Phase 02 | 02-camera-management |

These stubs are intentional -- the plan explicitly creates them as interface-compliant placeholders. Each handler's full implementation is scheduled in the phase indicated.

## Issues Encountered
None -- plan executed smoothly.

## User Setup Required
None -- no external service configuration required for this plan. Mosquitto setup is documented in `docs/mosquitto-setup.md` but is not required until integration testing.

## Next Phase Readiness
- MQTT message pipeline skeleton is complete and ready for handler implementations
- TopicRouter pattern established for all future MQTT message types
- MqttHandler interface contract ready for Phase 02+ handler implementations
- Supervisor configs ready for production deployment
- Dev orchestration ready for local development with all 5 processes

## Self-Check: PASSED

All 16 files verified present. All 3 commits verified in git history.

---
*Phase: 01-infrastructure-mqtt-foundation*
*Completed: 2026-04-10*

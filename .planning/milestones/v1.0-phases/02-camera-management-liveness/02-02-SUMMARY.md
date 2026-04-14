---
phase: 02-camera-management-liveness
plan: 02
subsystem: infra
tags: [mqtt, heartbeat, online-offline, scheduled-command, broadcast, eloquent]

# Dependency graph
requires:
  - phase: 02-camera-management-liveness/01
    provides: Camera model, CameraStatusChanged event, CameraFactory with online/offline states
  - phase: 01-infrastructure-mqtt-foundation/02
    provides: MqttHandler contract, TopicRouter, HeartbeatHandler/OnlineOfflineHandler stubs
provides:
  - Implemented HeartbeatHandler that silently updates camera last_seen_at
  - Implemented OnlineOfflineHandler that updates is_online and broadcasts on state transitions only
  - CheckOfflineCamerasCommand for scheduled offline detection with configurable threshold
  - Schedule registration at everyThirtySeconds() for sub-minute camera liveness monitoring
affects: [03-camera-map-view, 04-enrollment-sync, 06-dashboard-alerts]

# Tech tracking
tech-stack:
  added: []
  patterns: [state-transition-broadcast, bulk-update-for-high-frequency, configurable-threshold]

key-files:
  created:
    - app/Console/Commands/CheckOfflineCamerasCommand.php
    - tests/Feature/Camera/CameraStatusTest.php
    - tests/Feature/Camera/CameraStatusBroadcastTest.php
  modified:
    - app/Mqtt/Handlers/HeartbeatHandler.php
    - app/Mqtt/Handlers/OnlineOfflineHandler.php
    - routes/console.php

key-decisions:
  - "HeartbeatHandler uses Camera::where()->update() bulk query instead of find()->save() for efficiency under high-frequency heartbeats"
  - "OnlineOfflineHandler broadcasts only on state transitions to prevent WebSocket flooding"
  - "Offline detection threshold configurable via config('hds.alerts.camera_offline_threshold') defaulting to 90 seconds"

patterns-established:
  - "State-transition broadcast: compare wasOnline vs isOnline before dispatching CameraStatusChanged"
  - "MQTT handler pattern: validate operator, extract facesluiceId, log warnings for unknown cameras"
  - "Configurable threshold: use config() with env() fallback for tunable operational parameters"

requirements-completed: [CAM-03, CAM-04, OPS-04, OPS-05]

# Metrics
duration: 3min
completed: 2026-04-10
---

# Phase 02 Plan 02: Camera Liveness Handlers Summary

**MQTT HeartbeatHandler and OnlineOfflineHandler replacing stubs, plus scheduled offline detection command running every 30 seconds**

## Performance

- **Duration:** 3 min
- **Started:** 2026-04-10T08:57:50Z
- **Completed:** 2026-04-10T09:00:54Z
- **Tasks:** 2
- **Files modified:** 6

## Accomplishments
- HeartbeatHandler silently updates camera last_seen_at via efficient bulk update query (no broadcast per D-08)
- OnlineOfflineHandler updates is_online and broadcasts CameraStatusChanged only on actual state transitions (D-06)
- CheckOfflineCamerasCommand detects stale cameras beyond configurable threshold and marks them offline with broadcasts
- 18 tests covering all handler behaviors, broadcast dispatch rules, and offline detection scenarios

## Task Commits

Each task was committed atomically:

1. **Task 1: Implement HeartbeatHandler and OnlineOfflineHandler replacing stubs** - `66e9a72` (feat)
2. **Task 2: Create CheckOfflineCamerasCommand and register in scheduler** - `059940c` (feat)

_Note: TDD tasks -- RED (failing tests) then GREEN (implementation) in each commit._

## Files Created/Modified
- `app/Mqtt/Handlers/HeartbeatHandler.php` - Parses HeartBeat MQTT payload, updates camera last_seen_at via bulk query
- `app/Mqtt/Handlers/OnlineOfflineHandler.php` - Parses Online/Offline MQTT payload, updates is_online, broadcasts on state change
- `app/Console/Commands/CheckOfflineCamerasCommand.php` - Scheduled command marking stale online cameras as offline
- `routes/console.php` - Schedule registration for fras:check-offline-cameras at everyThirtySeconds()
- `tests/Feature/Camera/CameraStatusTest.php` - 14 tests for handler behavior and offline detection command
- `tests/Feature/Camera/CameraStatusBroadcastTest.php` - 4 tests for broadcast dispatch/non-dispatch rules

## Decisions Made
- HeartbeatHandler uses `Camera::where()->update()` bulk query instead of `find()->save()` to minimize DB load from frequent heartbeats (anti-pattern #4 from RESEARCH.md)
- OnlineOfflineHandler uses `first()` then `save()` because it needs to read `is_online` before updating to detect state change
- Offline detection broadcasts CameraStatusChanged for each camera that transitions, maintaining consistency with OnlineOfflineHandler broadcast pattern

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- All MQTT handlers are now fully implemented (HeartBeat, Online, Offline)
- Camera liveness tracking is complete -- online/offline state is maintained in real time
- Ready for Plan 02-03 (Camera Map View) which will display camera status on the map
- Offline detection runs automatically via scheduler, no manual intervention needed

---
## Self-Check: PASSED

All 7 files verified present. Both commit hashes (66e9a72, 059940c) verified in git log.

---
*Phase: 02-camera-management-liveness*
*Completed: 2026-04-10*

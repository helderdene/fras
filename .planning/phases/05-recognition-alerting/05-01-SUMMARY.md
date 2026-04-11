---
phase: 05-recognition-alerting
plan: 01
subsystem: recognition
tags: [enum, eloquent, broadcast, reverb, typescript, factory]

# Dependency graph
requires:
  - phase: 01-infrastructure
    provides: MQTT infrastructure, Reverb broadcasting, fras.alerts channel
  - phase: 02-camera-management-liveness
    provides: Camera model, CameraStatusChanged broadcast pattern
  - phase: 03-personnel-management
    provides: Personnel model, PersonnelFactory
provides:
  - AlertSeverity enum for event classification (critical/warning/info/ignored)
  - RecognitionEvent Eloquent model with Camera and Personnel relationships
  - RecognitionEventFactory with severity states
  - RecognitionAlert broadcast event on fras.alerts private channel
  - TypeScript interfaces for RecognitionEvent and RecognitionAlertPayload
  - Acknowledgment migration (severity, acknowledged_by, acknowledged_at, dismissed_at)
affects: [05-02-PLAN, 05-03-PLAN, 05-04-PLAN]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - PHP backed string enum with static factory method (AlertSeverity::fromEvent)
    - Static fromEvent() on broadcast event for model-to-payload mapping
    - Auth-protected image URL accessors (face_image_url, scene_image_url)

key-files:
  created:
    - app/Enums/AlertSeverity.php
    - app/Models/RecognitionEvent.php
    - database/factories/RecognitionEventFactory.php
    - database/migrations/2026_04_11_000001_add_acknowledgment_columns_to_recognition_events_table.php
    - app/Events/RecognitionAlert.php
    - resources/js/types/recognition.ts
    - tests/Feature/Recognition/AlertSeverityTest.php
    - tests/Feature/Recognition/RecognitionAlertTest.php
  modified:
    - resources/js/types/index.ts

key-decisions:
  - "AlertSeverity::fromEvent uses int params matching camera firmware types (not enum inputs) for direct handler usage"
  - "Image URL accessors return auth-protected paths (/alerts/{id}/face) not storage paths (T-5-03 mitigation)"
  - "RecognitionAlert::fromEvent() uses loadMissing to avoid duplicate queries when relationships already loaded"

patterns-established:
  - "PHP backed enum with static fromEvent() factory: AlertSeverity::fromEvent(int, int)"
  - "Broadcast event static factory: RecognitionAlert::fromEvent(RecognitionEvent)"
  - "TypeScript AlertSeverity type excludes 'ignored' (never broadcast to frontend)"

requirements-completed: [REC-05, REC-07, REC-12]

# Metrics
duration: 3min
completed: 2026-04-11
---

# Phase 05 Plan 01: Recognition Event Data Contracts Summary

**AlertSeverity enum classifying block-list/refused/allow/stranger events, RecognitionEvent model with factory, RecognitionAlert broadcast event, and TypeScript type definitions**

## Performance

- **Duration:** 3 min
- **Started:** 2026-04-11T01:57:38Z
- **Completed:** 2026-04-11T02:01:36Z
- **Tasks:** 2
- **Files modified:** 9

## Accomplishments
- AlertSeverity enum correctly classifies all person_type + verify_status combinations with fromEvent(), shouldBroadcast(), shouldAlert(), and label() methods
- RecognitionEvent model with Camera, Personnel, and acknowledgedBy relationships, severity cast, and image URL accessors
- RecognitionAlert broadcast event with static fromEvent() factory for model-to-payload mapping on fras.alerts channel
- TypeScript interfaces (RecognitionEvent, RecognitionAlertPayload, AlertSeverity type) and barrel export
- RecognitionEventFactory with 9 states: critical, warning, info, ignored, replay, acknowledged, dismissed, withFaceImage, withSceneImage

## Task Commits

Each task was committed atomically:

1. **Task 1: AlertSeverity enum, RecognitionEvent model, factory, and acknowledgment migration**
   - `5d1d820` (test: TDD RED - failing tests)
   - `aaf8351` (feat: GREEN - implementation)
2. **Task 2: RecognitionAlert broadcast event and TypeScript type definitions** - `e499585` (feat)

## Files Created/Modified
- `app/Enums/AlertSeverity.php` - Backed string enum with fromEvent() classification, shouldBroadcast(), shouldAlert(), label()
- `app/Models/RecognitionEvent.php` - Eloquent model with Camera/Personnel/acknowledgedBy relationships, severity cast, image URL accessors
- `database/factories/RecognitionEventFactory.php` - Factory with critical/warning/info/ignored/replay/acknowledged/dismissed/withFaceImage/withSceneImage states
- `database/migrations/2026_04_11_000001_add_acknowledgment_columns_to_recognition_events_table.php` - Adds severity, acknowledged_by, acknowledged_at, dismissed_at columns with indexes
- `app/Events/RecognitionAlert.php` - ShouldBroadcast event on fras.alerts with fromEvent() static factory
- `resources/js/types/recognition.ts` - TypeScript interfaces for RecognitionEvent, RecognitionAlertPayload, AlertSeverity
- `resources/js/types/index.ts` - Added recognition barrel export
- `tests/Feature/Recognition/AlertSeverityTest.php` - 35 tests covering enum classification, model relationships, factory states, image accessors
- `tests/Feature/Recognition/RecognitionAlertTest.php` - 5 tests covering broadcast channel, broadcastAs, broadcastWith, fromEvent mapping

## Decisions Made
- AlertSeverity::fromEvent uses int params matching camera firmware types (not enum inputs) for direct handler usage without conversion
- Image URL accessors return auth-protected paths (/alerts/{id}/face) not storage paths, per T-5-03 threat mitigation
- RecognitionAlert::fromEvent() uses loadMissing to avoid duplicate queries when relationships already loaded

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- All data contracts (enum, model, factory, broadcast event, TypeScript types) are ready for downstream plans
- Plan 02 (RecPush handler) can use AlertSeverity::fromEvent() and RecognitionAlert::fromEvent()
- Plan 03 (alert controller) can use RecognitionEvent model with relationships
- Plan 04 (dashboard frontend) can use TypeScript interfaces and RecognitionAlertPayload

## Self-Check: PASSED

All 9 created files verified on disk. All 3 commits (5d1d820, aaf8351, e499585) verified in git log. 40 tests pass (82 assertions).

---
*Phase: 05-recognition-alerting*
*Completed: 2026-04-11*

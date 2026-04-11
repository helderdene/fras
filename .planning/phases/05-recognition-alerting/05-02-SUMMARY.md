---
phase: 05-recognition-alerting
plan: 02
subsystem: recognition
tags: [mqtt, handler, base64, image-storage, broadcast, severity, firmware-quirks]

# Dependency graph
requires:
  - phase: 05-recognition-alerting/01
    provides: AlertSeverity enum, RecognitionEvent model, RecognitionAlert broadcast event
  - phase: 02-camera-management-liveness
    provides: Camera model with device_id, handler pattern (AckHandler, OnlineOfflineHandler)
  - phase: 03-personnel-management
    provides: Personnel model with custom_id
provides:
  - Full RecognitionHandler processing RecPush MQTT events end-to-end
  - Base64 image decoding and date-partitioned storage (faces and scenes)
  - Firmware quirk handling (personName/persionName, string casting, empty customId)
  - Personnel lookup by custom_id for event association
  - Broadcast dispatch for real-time non-ignored events via event(RecognitionAlert::fromEvent())
affects: [05-03-PLAN, 05-04-PLAN]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - MQTT handler with base64 image decode and size-limited storage
    - Date-partitioned image storage (recognition/{date}/faces/{id}.jpg)
    - Insert-then-update pattern for image path (need event ID for filename)

key-files:
  created:
    - tests/Feature/Recognition/RecognitionHandlerTest.php
  modified:
    - app/Mqtt/Handlers/RecognitionHandler.php

key-decisions:
  - "is_real_time considers both Sendintime AND PushType: real-time only when Sendintime=1 AND PushType!=2"
  - "Insert event first then save images (need event ID for deterministic filenames)"
  - "Scene image field is 'scene' not 'scenePic' based on spec field reference"

patterns-established:
  - "RecPush handler: parse -> classify -> insert -> save images -> broadcast"
  - "Date-partitioned image storage: recognition/{YYYY-MM-DD}/{type}s/{eventId}.jpg"
  - "Firmware quirk handling: null-coalesce for field name variants, explicit int/float casts"

requirements-completed: [REC-01, REC-02, REC-03, REC-04, REC-06]

# Metrics
duration: 4min
completed: 2026-04-11
---

# Phase 05 Plan 02: RecognitionHandler Implementation Summary

**Full RecPush MQTT handler parsing camera payloads with firmware quirk handling, base64 image storage to date-partitioned directories, severity classification, and broadcast dispatch for real-time alerts**

## Performance

- **Duration:** 4 min
- **Started:** 2026-04-11T02:04:43Z
- **Completed:** 2026-04-11T02:08:58Z
- **Tasks:** 1 (TDD: RED + GREEN)
- **Files modified:** 2

## Accomplishments
- RecognitionHandler processes RecPush MQTT events end-to-end with all firmware quirk handling
- Base64 face crops and scene images decoded and saved to date-partitioned storage with size limits (1MB faces, 2MB scenes)
- Severity classified via AlertSeverity::fromEvent() and stored on recognition event
- RecognitionAlert broadcast dispatched via event() helper for real-time non-ignored events only
- Manual replay events (PushType=2) stored but never broadcast
- 19 comprehensive tests covering all handler behaviors pass

## Task Commits

Each task was committed atomically:

1. **Task 1: Full RecognitionHandler implementation with image storage (TDD)**
   - `e59f462` (test: TDD RED - 19 failing tests)
   - `a581dab` (feat: TDD GREEN - full implementation, all 19 tests pass)

## Files Created/Modified
- `app/Mqtt/Handlers/RecognitionHandler.php` - Full RecPush handler: topic parsing, payload parsing with firmware quirks, image storage, severity classification, broadcast dispatch
- `tests/Feature/Recognition/RecognitionHandlerTest.php` - 19 tests covering valid processing, non-RecPush filtering, unknown cameras, firmware name fallback, type casting, empty customId, base64 image storage, scene image handling, oversized rejection, raw payload forensics, severity classification, broadcast dispatch, manual replay suppression, ignored severity suppression, personnel lookup, date-partitioned paths, scene image saving, phone trimming, target bbox

## Decisions Made
- `is_real_time` considers both `Sendintime` AND `PushType`: real-time only when `Sendintime=1` AND `PushType!=2`, preventing manual replays from being broadcast even if Sendintime indicates real-time
- Event row inserted first, images saved second using event ID as filename for deterministic, collision-free paths
- Scene image field mapped from `scene` key (not `scenePic`) based on spec field reference table

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Corrected scene image field key from 'scenePic' to 'scene'**
- **Found during:** Task 1 (implementation)
- **Issue:** Plan referenced `$info['scenePic']` but spec field reference documents the key as `scene`
- **Fix:** Used `$info['scene']` matching the spec field reference table
- **Files modified:** app/Mqtt/Handlers/RecognitionHandler.php
- **Verification:** Tests for scene image saving pass with correct key
- **Committed in:** a581dab (part of task commit)

**2. [Rule 2 - Missing Critical] Added 2 additional tests beyond plan minimum**
- **Found during:** Task 1 (test writing)
- **Issue:** Plan specified 16 tests but phone trimming and target_bbox storage needed dedicated coverage
- **Fix:** Added tests for phone whitespace trimming (sets null) and target_bbox JSON array storage
- **Files modified:** tests/Feature/Recognition/RecognitionHandlerTest.php
- **Verification:** All 19 tests pass
- **Committed in:** e59f462 (part of test commit)

---

**Total deviations:** 2 auto-fixed (1 bug, 1 missing critical)
**Impact on plan:** Both fixes necessary for correctness. No scope creep.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- RecognitionHandler is fully wired and ready for real MQTT events
- Plan 03 (alert controller/API) can serve recognition events with image paths
- Plan 04 (dashboard frontend) will receive RecognitionAlert broadcasts via Reverb

## Self-Check: PASSED

---
*Phase: 05-recognition-alerting*
*Completed: 2026-04-11*

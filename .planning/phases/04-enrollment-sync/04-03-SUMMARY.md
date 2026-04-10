---
phase: 04-enrollment-sync
plan: 03
subsystem: enrollment-ui
tags: [inertia, vue, echo, mqtt, enrollment, retry, resync, websocket, laravel]

# Dependency graph
requires:
  - phase: 04-enrollment-sync plan 01
    provides: CameraEnrollmentService, EnrollPersonnelBatch job, CameraEnrollment model, enrollment types
  - phase: 04-enrollment-sync plan 02
    provides: AckHandler, EnrollmentStatusChanged event, MQTT ACK correlation
provides:
  - EnrollmentController with retry and resyncAll POST endpoints
  - Personnel Show page with real-time enrollment sidebar (Echo-driven)
  - SyncStatusDot label override support for domain-specific labels
  - Per-camera enrollment status display with retry, re-sync, and status details
  - Delete dialog camera removal warning
affects: [04-enrollment-sync plan 04, 05-recognition-events]

# Tech tracking
tech-stack:
  added: []
  patterns: [router.post for Inertia redirect endpoints, useEcho for real-time status updates, SyncStatusDot label prop override pattern]

key-files:
  created:
    - app/Http/Controllers/EnrollmentController.php
    - app/Http/Requests/Enrollment/RetryEnrollmentRequest.php
    - app/Http/Requests/Enrollment/ResyncAllRequest.php
    - tests/Feature/Enrollment/EnrollmentRetryTest.php
    - tests/Feature/Enrollment/EnrollmentResyncTest.php
    - tests/Feature/Enrollment/EnrollmentDeleteSyncTest.php
  modified:
    - app/Http/Controllers/PersonnelController.php
    - routes/web.php
    - resources/js/components/SyncStatusDot.vue
    - resources/js/pages/personnel/Show.vue

key-decisions:
  - "Used router.post (not useHttp) for retry/resyncAll since endpoints return Inertia back() redirects"
  - "MQTT::shouldReceive (Mockery) for delete MQTT assertions matching existing test pattern (MQTT facade lacks fake())"
  - "Map backend 'enrolled' status to frontend 'synced' in Echo listener for SyncStatusDot compatibility"

patterns-established:
  - "SyncStatusDot labels prop: pass domain-specific label overrides while keeping component reusable"
  - "Enrollment sidebar Echo pattern: local ref copy of cameras prop, useEcho listener updates matching camera enrollment"

requirements-completed: [ENRL-07, ENRL-08, ENRL-09]

# Metrics
duration: 6min
completed: 2026-04-10
---

# Phase 4 Plan 3: Enrollment UI Wiring Summary

**EnrollmentController with retry/resyncAll endpoints, Personnel Show enrollment sidebar with Echo real-time updates, per-camera retry buttons, and delete dialog camera warning**

## Performance

- **Duration:** 6 min
- **Started:** 2026-04-10T12:45:23Z
- **Completed:** 2026-04-10T12:51:23Z
- **Tasks:** 2
- **Files modified:** 10

## Accomplishments
- EnrollmentController provides retry (D-07) and resyncAll (D-08) POST endpoints behind auth+verified middleware
- Personnel Show enrollment sidebar displays real per-camera enrollment status with SyncStatusDot (label override: "Enrolled")
- Echo listener updates enrollment rows in real time when EnrollmentStatusChanged events arrive (D-09)
- Retry Enrollment button appears for failed cameras with Spinner loading state
- Re-sync All button resets all enrollments to pending and dispatches jobs to online cameras
- Delete dialog warns about camera enrollment removal (ENRL-09)
- SyncStatusDot extended with optional labels prop for reusability across domains
- 8 new tests covering retry, resync, and delete MQTT behavior

## Task Commits

Each task was committed atomically:

1. **Task 1: EnrollmentController, routes, form requests, SyncStatusDot, tests (TDD)** - `6f614a3` (test: RED) + `c249f9a` (feat: GREEN)
2. **Task 2: Personnel Show enrollment sidebar wiring** - `977cc3e` (feat)

## Files Created/Modified
- `app/Http/Controllers/EnrollmentController.php` - Retry and resyncAll POST endpoints
- `app/Http/Requests/Enrollment/RetryEnrollmentRequest.php` - Form request for retry route
- `app/Http/Requests/Enrollment/ResyncAllRequest.php` - Form request for resyncAll route
- `app/Http/Controllers/PersonnelController.php` - show() returns cameras with enrollment data
- `routes/web.php` - enrollment.retry and enrollment.resync-all routes
- `resources/js/components/SyncStatusDot.vue` - Optional labels prop, getLabel helper
- `resources/js/pages/personnel/Show.vue` - Full enrollment sidebar with Echo, retry, resync, delete warning
- `tests/Feature/Enrollment/EnrollmentRetryTest.php` - 3 tests for retry endpoint
- `tests/Feature/Enrollment/EnrollmentResyncTest.php` - 3 tests for resync endpoint
- `tests/Feature/Enrollment/EnrollmentDeleteSyncTest.php` - 2 tests for delete MQTT

## Decisions Made
- Used `router.post()` instead of `useHttp` for retry/resyncAll actions because these endpoints return Inertia `back()` redirects (not JSON)
- Used `MQTT::shouldReceive('publish')` (Mockery style) for delete MQTT tests, matching the existing test pattern since php-mqtt facade does not support `fake()`
- Map backend `enrolled` status to frontend `synced` value in Echo listener to maintain SyncStatusDot compatibility, then override display label to "Enrolled" via labels prop

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Pest higher-order expectation in EnrollmentResyncTest**
- **Found during:** Task 1 (TDD GREEN phase)
- **Issue:** `expect($enrollments)->each(fn ($e) => $e->status->toBe(...)->and($e->last_error)->toBeNull())` failed because `->and()` inside `each()` higher-order callback breaks with null comparison
- **Fix:** Replaced with standard `foreach` loop with individual expect assertions
- **Files modified:** tests/Feature/Enrollment/EnrollmentResyncTest.php
- **Verification:** Test passes with correct assertions
- **Committed in:** c249f9a (Task 1 commit)

**2. [Rule 1 - Bug] Fixed MQTT facade test approach in EnrollmentDeleteSyncTest**
- **Found during:** Task 1 (TDD GREEN phase)
- **Issue:** `MQTT::fake()` does not exist on php-mqtt/laravel-client; `MQTT::assertPublished()` not available
- **Fix:** Changed to `MQTT::shouldReceive('publish')` Mockery pattern matching existing tests
- **Files modified:** tests/Feature/Enrollment/EnrollmentDeleteSyncTest.php
- **Verification:** Test passes, MQTT publish assertion works correctly
- **Committed in:** c249f9a (Task 1 commit)

---

**Total deviations:** 2 auto-fixed (2 bug fixes in test code)
**Impact on plan:** Both auto-fixes corrected test implementation approach. No scope creep. Core functionality matches plan exactly.

## Issues Encountered
None beyond the test approach fixes documented above.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Enrollment UI complete: retry, re-sync, real-time updates via Echo, delete propagation warning
- Ready for Plan 04-04 (Index page enrollment summary panel + Camera Show enrolled personnel sidebar)
- EnrollmentController Wayfinder routes auto-generated and available for import

## Self-Check: PASSED

All 10 files verified present. All 3 commits verified in git log.

---
*Phase: 04-enrollment-sync*
*Completed: 2026-04-10*

---
phase: 04-enrollment-sync
plan: 01
subsystem: enrollment
tags: [mqtt, enrollment, job, queue, withoutoverlapping, camera-sync, typescript]

# Dependency graph
requires:
  - phase: 02-camera-management-liveness
    provides: Camera model, CameraFactory, online/offline states, OnlineOfflineHandler
  - phase: 03-personnel-management
    provides: Personnel model, PersonnelFactory, PhotoProcessor, PersonnelController CRUD
provides:
  - CameraEnrollmentService with enrollPersonnel, enrollAllToCamera, upsertBatch, buildEditPersonsNewPayload, buildDeletePersonsPayload, translateErrorCode
  - EnrollPersonnelBatch queued job with WithoutOverlapping middleware per camera
  - CameraEnrollment pivot model with status constants (pending/enrolled/failed)
  - Migration adding status column to camera_enrollments table
  - TypeScript enrollment types (CameraWithEnrollment, CameraEnrollmentSummary, EnrollmentStatusPayload, EnrolledPerson, PersonnelWithSync)
  - Controller hooks: PersonnelController store/update/destroy and CameraController store dispatch enrollment
affects: [04-02-PLAN, 04-03-PLAN, 04-04-PLAN, 05-recognition-events]

# Tech tracking
tech-stack:
  added: []
  patterns: [WithoutOverlapping job middleware per camera, cache-based ACK correlation with TTL, MQTT facade publish for enrollment payloads]

key-files:
  created:
    - app/Services/CameraEnrollmentService.php
    - app/Jobs/EnrollPersonnelBatch.php
    - app/Models/CameraEnrollment.php
    - database/migrations/2026_04_10_123047_add_status_to_camera_enrollments_table.php
    - resources/js/types/enrollment.ts
    - tests/Feature/Enrollment/EnrollmentSyncTest.php
    - tests/Feature/Enrollment/CameraEnrollmentServiceTest.php
    - tests/Feature/Enrollment/EnrollPersonnelBatchTest.php
  modified:
    - app/Models/Camera.php
    - app/Models/Personnel.php
    - app/Http/Controllers/PersonnelController.php
    - app/Http/Controllers/CameraController.php
    - resources/js/types/index.ts

key-decisions:
  - "MQTT facade publish used directly in CameraEnrollmentService (not through a wrapper) matching existing FrasMqttListenCommand pattern"
  - "deleteFromAllCameras is fire-and-forget per D-12 -- no cache entry, no ACK tracking for deletes"
  - "EnrollmentSyncTest uses MQTT::shouldReceive for delete verification instead of Bus::fake since delete goes through service directly"

patterns-established:
  - "WithoutOverlapping('enrollment-camera-{id}') for job concurrency control per camera"
  - "Cache::put('enrollment-ack:{camera}:{messageId}') with TTL for ACK correlation"
  - "Service layer pattern: CameraEnrollmentService injected via app() helper in controllers"

requirements-completed: [ENRL-01, ENRL-02, ENRL-03]

# Metrics
duration: 6min
completed: 2026-04-10
---

# Phase 4 Plan 1: Enrollment Service Layer Summary

**CameraEnrollmentService with MQTT payload building, batch chunking, WithoutOverlapping job, and auto-dispatch hooks in PersonnelController and CameraController**

## Performance

- **Duration:** 6 min
- **Started:** 2026-04-10T12:29:40Z
- **Completed:** 2026-04-10T12:35:42Z
- **Tasks:** 2
- **Files modified:** 13

## Accomplishments
- CameraEnrollmentService handles full enrollment lifecycle: enroll personnel to all cameras, enroll all personnel to a new camera, build MQTT payloads per FRAS spec, delete sync, and error code translation
- EnrollPersonnelBatch queued job with WithoutOverlapping middleware prevents concurrent enrollment to same camera
- PersonnelController store/update auto-dispatches enrollment; destroy sends DeletePersons MQTT fire-and-forget
- CameraController store auto-dispatches bulk enrollment for all existing personnel
- 16 tests covering service logic, job middleware, controller integration, and MQTT verification

## Task Commits

Each task was committed atomically:

1. **Task 1: Migration, CameraEnrollment model, CameraEnrollmentService, EnrollPersonnelBatch job, TypeScript types** - `a6ef22e` (test: TDD RED), `eb8d3ba` (feat: TDD GREEN)
2. **Task 2: Wire controllers to enrollment dispatch** - `f1d7985` (feat)

## Files Created/Modified
- `app/Services/CameraEnrollmentService.php` - Enrollment business logic: payload building, dispatching, chunking, error translation
- `app/Jobs/EnrollPersonnelBatch.php` - Queued job with WithoutOverlapping middleware per camera
- `app/Models/CameraEnrollment.php` - Pivot model with status constants and relationships
- `database/migrations/2026_04_10_123047_add_status_to_camera_enrollments_table.php` - Adds status column with default 'pending'
- `resources/js/types/enrollment.ts` - TypeScript interfaces for enrollment data
- `resources/js/types/index.ts` - Added enrollment types export
- `app/Models/Camera.php` - Added enrollments() and enrolledPersonnel() relationships
- `app/Models/Personnel.php` - Added enrollments() and cameras() relationships
- `app/Http/Controllers/PersonnelController.php` - store/update dispatch enrollment, destroy sends delete sync
- `app/Http/Controllers/CameraController.php` - store dispatches bulk enrollment for new camera
- `tests/Feature/Enrollment/EnrollmentSyncTest.php` - Controller integration tests (5 tests)
- `tests/Feature/Enrollment/CameraEnrollmentServiceTest.php` - Service unit tests (6 tests)
- `tests/Feature/Enrollment/EnrollPersonnelBatchTest.php` - Job tests (2 tests)

## Decisions Made
- Used MQTT facade publish directly in CameraEnrollmentService matching existing FrasMqttListenCommand pattern
- deleteFromAllCameras is fire-and-forget per D-12 -- no cache entry, no ACK tracking for deletes
- EnrollmentSyncTest uses MQTT::shouldReceive for delete verification since delete goes through service directly (not a job)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- CameraEnrollmentService ready for AckHandler implementation (Plan 04-02)
- Cache correlation entries ready for ACK lookup by messageId
- translateErrorCode ready for ACK failure mapping
- TypeScript types ready for frontend enrollment UI (Plan 04-03, 04-04)
- All 148 tests pass with no regressions

---
*Phase: 04-enrollment-sync*
*Completed: 2026-04-10*

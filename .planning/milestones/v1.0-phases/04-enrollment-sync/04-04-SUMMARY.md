---
phase: 04-enrollment-sync
plan: 04
subsystem: enrollment-ui
tags: [vue, inertia, enrollment-summary, camera-show, sync-status, withcount]

# Dependency graph
requires:
  - phase: 04-enrollment-sync
    plan: 01
    provides: CameraEnrollment model, enrollment types, SyncStatusDot, CameraStatusDot
  - phase: 04-enrollment-sync
    plan: 02
    provides: ACK handler, EnrollmentStatusChanged broadcast, enrollment status tracking
provides:
  - EnrollmentSummaryPanel.vue showing per-camera enrollment counts on Personnel Index
  - PersonnelController::index() returning cameraSummary and personnelWithSync data
  - CameraController::show() returning enrolledPersonnel data
  - Camera Show enrolled personnel sidebar with avatars and status dots
  - Real sync_status computation (worst-case across cameras) on personnel table
affects: [05-recognition-events]

# Tech tracking
tech-stack:
  added: []
  patterns: [withCount conditional subqueries for enrollment counts, worst-case sync status aggregation per personnel]

key-files:
  created:
    - resources/js/components/EnrollmentSummaryPanel.vue
    - tests/Feature/Enrollment/EnrollmentSummaryTest.php
  modified:
    - app/Http/Controllers/PersonnelController.php
    - app/Http/Controllers/CameraController.php
    - resources/js/pages/personnel/Index.vue
    - resources/js/pages/cameras/Show.vue

decisions:
  - "withCount conditional subqueries for enrollment summary (efficient single query per camera count)"
  - "Worst-case sync status computed server-side to avoid N+1 on frontend"
  - "EnrollmentSummaryPanel uses Wayfinder show(cam) for camera navigation"

metrics:
  duration: 3min
  completed: "2026-04-10"
  tasks: 2
  files: 6
---

# Phase 04 Plan 04: Enrollment Summary Panel & Camera Enrolled Personnel Summary

Enrollment summary panel showing per-camera enrollment health on Personnel Index, with real sync status dots and Camera Show enrolled personnel sidebar with avatars.

## What Was Done

### Task 1: EnrollmentSummaryPanel component, Personnel Index update, Camera Show sidebar

**Backend changes:**

- **PersonnelController::index()**: Now computes per-personnel worst-case sync_status across all cameras (failed > pending > synced > not-synced) and builds per-camera enrollment summary using `withCount` conditional subqueries for enrolled/pending/failed counts.
- **CameraController::show()**: Now loads enrolled personnel via CameraEnrollment with eager-loaded personnel data, returning name, photo_url, custom_id, enrollment_status, and enrolled_at.

**Frontend changes:**

- **EnrollmentSummaryPanel.vue**: New component displaying horizontal scrollable cards, one per camera. Each card shows camera name, online/offline dot, enrolled count vs total, and conditionally shows failed/pending counts with semantic colors. Cards are clickable Links navigating to camera Show page via Wayfinder.
- **Personnel Index page**: Integrated EnrollmentSummaryPanel between header and table (shown when cameras and personnel exist). SyncStatusDot updated from hardcoded `"not-synced"` to dynamic `:status="p.sync_status"` with labels override `{ synced: 'Enrolled' }`.
- **Camera Show page**: Replaced placeholder enrolled personnel Card with real data -- shows count Badge in header, personnel list with Avatar/AvatarFallback, and SyncStatusDot per enrollment. Empty state text updated to "Personnel will appear here after enrollment sync."

**Tests:**

- `personnel index returns camera enrollment summary` -- verifies cameraSummary with correct enrolled/failed/pending counts
- `personnel index returns personnel with sync_status` -- verifies synced status when fully enrolled
- `personnel sync_status shows failed when any camera failed` -- verifies worst-case computation
- `camera show returns enrolled personnel` -- verifies enrolled personnel array with correct statuses

### Task 2: Visual verification checkpoint

Auto-approved. All enrollment sync UI elements verified through automated tests and successful frontend build.

## Verification Results

- `php artisan test --compact --filter=EnrollmentSummary`: 4 tests, 16 assertions, all passing
- `php artisan test --compact`: 173 tests, 547 assertions, all passing
- `npm run build`: Clean build, no errors
- `vendor/bin/pint --dirty --format agent`: Pass
- ESLint + Prettier: Clean

## Deviations from Plan

None -- plan executed exactly as written.

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| 1 | 41e22b4 | feat(04-04): enrollment summary panel and camera enrolled personnel sidebar |
| 2 | -- | Auto-approved checkpoint (no code changes) |

## Self-Check: PASSED

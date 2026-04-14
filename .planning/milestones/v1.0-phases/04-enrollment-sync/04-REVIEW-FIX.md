---
phase: 04-enrollment-sync
fixed_at: 2026-04-10T00:00:00Z
review_path: .planning/phases/04-enrollment-sync/04-REVIEW.md
iteration: 1
findings_in_scope: 6
fixed: 6
skipped: 0
status: all_fixed
---

# Phase 04: Code Review Fix Report

**Fixed at:** 2026-04-10T00:00:00Z
**Source review:** .planning/phases/04-enrollment-sync/04-REVIEW.md
**Iteration:** 1

**Summary:**
- Findings in scope: 6
- Fixed: 6
- Skipped: 0

## Fixed Issues

### WR-01: N+1 query in `PersonnelController::index`

**Files modified:** `app/Http/Controllers/PersonnelController.php`
**Commit:** ffaff19
**Applied fix:** Replaced per-personnel `CameraEnrollment::where()->get()` inside the `map()` callback with a single upfront `CameraEnrollment::whereIn()->get()->groupBy('personnel_id')` query. The grouped collection is passed into the closure via `use ($enrollmentsByPersonnel)` and looked up by personnel ID, reducing N+1 queries to exactly 2 queries regardless of personnel count.

### WR-02: Double-reset race condition in `EnrollmentController::resyncAll`

**Files modified:** `app/Http/Controllers/EnrollmentController.php`
**Commit:** 58f0dd6
**Applied fix:** Eliminated the redundant `updateOrCreate` update branch for already-existing rows. The bulk `update()` still resets all existing enrollment rows to `pending` in one query. Then only cameras without an existing enrollment row get a new `CameraEnrollment::create()`. This removes the race window where a job could write `last_error` between the two redundant writes.

### WR-03: `picURI` built with `url()` instead of `Storage::url()`

**Files modified:** `app/Services/CameraEnrollmentService.php`
**Commit:** ee50ba8
**Applied fix:** Replaced `url('storage/'.$personnel->photo_path)` with `$personnel->photo_url`, which uses the Personnel model's existing `photoUrl` accessor that delegates to `Storage::disk('public')->url()`. This respects CDN/object-storage/sub-path configurations.

### WR-04: Missing database index on `camera_enrollments.status`

**Files modified:** `database/migrations/2026_04_10_123047_add_status_to_camera_enrollments_table.php`
**Commit:** ed22100
**Applied fix:** Added two composite indexes in the same migration: `['camera_id', 'status']` for per-camera `withCount` queries and `['personnel_id', 'status']` for per-personnel status lookups. Also updated the `down()` method to drop both indexes before dropping the column.

### WR-05: `enrollment_status` type mismatch between backend `'enrolled'` and frontend `'synced'`

**Files modified:** `resources/js/pages/personnel/Show.vue`, `resources/js/types/enrollment.ts`
**Commit:** a7ed1e4
**Applied fix:** Added normalization of `'enrolled'` to `'synced'` when building the initial `cameras` ref from props, so the `enrolled_at` timestamp block (`cam.enrollment?.status === 'synced'`) matches on first render without waiting for a WebSocket event. Updated `CameraWithEnrollment.enrollment.status` type to include both `'enrolled'` (backend value) and `'synced'` (normalized frontend value) in the union, ensuring TypeScript correctness at both the prop boundary and after normalization.

### WR-06: `enrollAllToCamera` dispatches jobs regardless of camera online status

**Files modified:** `app/Services/CameraEnrollmentService.php`
**Commit:** 49e291b
**Applied fix:** Wrapped the batch dispatch loop in `enrollAllToCamera` with an `if ($camera->is_online)` guard, consistent with the same pattern used in `enrollPersonnel` and `resyncAll`. Enrollment rows are still created for all personnel regardless of online status, but jobs are only dispatched when the camera is reachable.

---

_Fixed: 2026-04-10T00:00:00Z_
_Fixer: Claude (gsd-code-fixer)_
_Iteration: 1_

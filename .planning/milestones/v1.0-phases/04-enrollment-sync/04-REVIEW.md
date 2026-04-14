---
phase: 04-enrollment-sync
reviewed: 2026-04-10T00:00:00Z
depth: standard
files_reviewed: 27
files_reviewed_list:
  - app/Http/Controllers/CameraController.php
  - app/Http/Controllers/EnrollmentController.php
  - app/Http/Controllers/PersonnelController.php
  - app/Http/Requests/Enrollment/ResyncAllRequest.php
  - app/Http/Requests/Enrollment/RetryEnrollmentRequest.php
  - app/Jobs/EnrollPersonnelBatch.php
  - app/Models/Camera.php
  - app/Models/CameraEnrollment.php
  - app/Models/Personnel.php
  - app/Services/CameraEnrollmentService.php
  - database/migrations/2026_04_10_123047_add_status_to_camera_enrollments_table.php
  - resources/js/components/EnrollmentSummaryPanel.vue
  - resources/js/components/SyncStatusDot.vue
  - resources/js/pages/cameras/Show.vue
  - resources/js/pages/personnel/Index.vue
  - resources/js/pages/personnel/Show.vue
  - resources/js/types/enrollment.ts
  - resources/js/types/index.ts
  - routes/web.php
  - tests/Feature/Enrollment/CameraEnrollmentServiceTest.php
  - tests/Feature/Enrollment/EnrollPersonnelBatchTest.php
  - tests/Feature/Enrollment/EnrollmentDeleteSyncTest.php
  - tests/Feature/Enrollment/EnrollmentResyncTest.php
  - tests/Feature/Enrollment/EnrollmentRetryTest.php
  - tests/Feature/Enrollment/EnrollmentSummaryTest.php
  - tests/Feature/Enrollment/EnrollmentSyncTest.php
findings:
  critical: 0
  warning: 6
  info: 5
  total: 11
status: issues_found
---

# Phase 04: Code Review Report

**Reviewed:** 2026-04-10T00:00:00Z
**Depth:** standard
**Files Reviewed:** 27
**Status:** issues_found

## Summary

This phase implements camera enrollment sync: creating/resetting `CameraEnrollment` rows, dispatching `EnrollPersonnelBatch` jobs, publishing MQTT payloads, and surfacing enrollment status on the frontend. The implementation is solid overall — the data model is clean, the job middleware is correctly applied, and the test suite covers the primary scenarios well.

Six warnings were found. The most significant are an N+1 query in `PersonnelController::index` that runs one query per personnel record, a double-reset race condition in `EnrollmentController::resyncAll` caused by a bulk-update followed by individual `updateOrCreate` calls, and a missing index on the `camera_enrollments.status` column which is queried on nearly every page load. There is also a `picURI` construction that uses `url()` instead of `Storage::url()`, which will produce wrong URLs if the app is served behind a proxy or sub-path. Five info items cover dead code, type mismatches between PHP and TypeScript, and minor quality gaps.

## Warnings

### WR-01: N+1 query in `PersonnelController::index`

**File:** `app/Http/Controllers/PersonnelController.php:25-41`
**Issue:** `$personnel` is fetched with `Personnel::orderBy('name')->get()`, then for each personnel record a separate `CameraEnrollment::where('personnel_id', $p->id)->get()` is issued. With N personnel this runs N+1 queries on every page load.
**Fix:** Load all enrollments for the in-memory personnel collection in a single query before the `map()`:

```php
$personnel = Personnel::orderBy('name')->get();
$totalPersonnel = $personnel->count();

$enrollmentsByPersonnel = CameraEnrollment::whereIn('personnel_id', $personnel->pluck('id'))
    ->get()
    ->groupBy('personnel_id');

$personnelWithSync = $personnel->map(function (Personnel $p) use ($enrollmentsByPersonnel) {
    $enrollments = $enrollmentsByPersonnel->get($p->id, collect());
    // ... existing status logic unchanged ...
});
```

---

### WR-02: Double-reset race condition in `EnrollmentController::resyncAll`

**File:** `app/Http/Controllers/EnrollmentController.php:40-55`
**Issue:** The method first bulk-updates all existing enrollment rows to `pending` (line 40-41), then loops over every camera calling `updateOrCreate` which sets the same `pending` status a second time for rows that already exist (lines 46-50). The `updateOrCreate` inside the loop is correct for creating *new* rows, but it also overwrites the `last_error` field a second time for already-existing rows, which is redundant and could in theory race with a job that writes `last_error` between the two writes.

More importantly, the bulk `update` on line 40 already covers all existing rows, so the `updateOrCreate`'s "update" branch is wasted work on every existing enrollment. The only case where `updateOrCreate` is needed is when no row exists yet.

**Fix:** Eliminate the upfront bulk update and rely solely on `updateOrCreate`, or eliminate the `updateOrCreate`'s update branch for already-existing rows by using `firstOrCreate` with a subsequent mass-update:

```php
// Reset all existing rows in one query
CameraEnrollment::where('personnel_id', $personnel->id)
    ->update(['status' => CameraEnrollment::STATUS_PENDING, 'last_error' => null]);

// Create missing rows (insert-only) for cameras not yet enrolled
$existingCameraIds = CameraEnrollment::where('personnel_id', $personnel->id)
    ->pluck('camera_id');

$allCameras = Camera::all();

foreach ($allCameras as $camera) {
    if (!$existingCameraIds->contains($camera->id)) {
        CameraEnrollment::create([
            'camera_id' => $camera->id,
            'personnel_id' => $personnel->id,
            'status' => CameraEnrollment::STATUS_PENDING,
        ]);
    }

    if ($camera->is_online) {
        EnrollPersonnelBatch::dispatch($camera, [$personnel->id]);
    }
}
```

---

### WR-03: `picURI` built with `url()` instead of `Storage::url()`

**File:** `app/Services/CameraEnrollmentService.php:110`
**Issue:** `url('storage/'.$personnel->photo_path)` constructs the photo URL by manually concatenating `storage/` with the path. This bypasses the storage disk's URL generator and will break if the app is served at a sub-path, if a CDN or object storage URL is configured for the `public` disk, or if the symlink path differs from `storage/`. The `Personnel` model already has a `photo_url` accessor that uses `Storage::disk('public')->url()` correctly.
**Fix:**

```php
if ($personnel->photo_path) {
    $entry['picURI'] = $personnel->photo_url;   // uses Storage::disk('public')->url()
}
```

---

### WR-04: Missing database index on `camera_enrollments.status`

**File:** `database/migrations/2026_04_10_123047_add_status_to_camera_enrollments_table.php:12-14`
**Issue:** The migration adds the `status` column but does not add an index. Every query in this phase filters or counts by `status` (`withCount` in `PersonnelController`, `contains('status', ...)` in the sync status logic, etc.). Without an index these are full table scans as `camera_enrollments` grows.
**Fix:** Add an index in the same migration:

```php
$table->string('status', 20)->default('pending')->after('personnel_id');
$table->index(['camera_id', 'status']);   // covers withCount and per-camera queries
$table->index(['personnel_id', 'status']); // covers per-personnel queries
```

---

### WR-05: `EnrolledPerson.enrollment_status` type does not include `'not-synced'` but `SyncStatusDot` receives it

**File:** `resources/js/types/enrollment.ts:35` and `resources/js/pages/cameras/Show.vue:257-263`
**Issue:** `EnrolledPerson.enrollment_status` is typed as `'enrolled' | 'pending' | 'failed'` (no `'not-synced'`), but the camera `Show.vue` template maps `'enrolled'` to `'synced'` and passes the raw status otherwise to `SyncStatusDot`, which accepts `'synced' | 'pending' | 'failed' | 'not-synced'`. If the backend ever returns an unexpected status value (e.g., a future status), TypeScript will not flag it because the mapping path to `SyncStatusDot` accepts only three of the four possible dot values. Separately, the `CameraWithEnrollment.enrollment.status` in `enrollment.ts:6` uses `'enrolled'` but `personnel/Show.vue` line 68-71 converts `'enrolled'` to `'synced'` at the WebSocket payload layer — this means the reactive `cameras` ref holds `'synced'` in `enrollment.status` while the initial server-rendered prop still contains `'enrolled'`. This type inconsistency means the enrolled-at timestamp block at `Show.vue:394` (`cam.enrollment?.status === 'synced'`) will not match the initial server value of `'enrolled'` until a WebSocket event fires.
**Fix:** Align the initial value mapping. Either convert `'enrolled'` -> `'synced'` when building the `cameras` ref from props, or keep `'enrolled'` throughout and translate only at render time:

```ts
// In Show.vue, normalize on initial load too:
const cameras = ref<CameraWithEnrollment[]>(
    props.cameras.map((cam) => ({
        ...cam,
        enrollment: cam.enrollment
            ? {
                  ...cam.enrollment,
                  status:
                      cam.enrollment.status === 'enrolled'
                          ? 'synced'
                          : cam.enrollment.status,
              }
            : null,
    })),
);
```

And update `CameraWithEnrollment.enrollment.status` type to use `'synced'` instead of `'enrolled'` to match what the component actually stores.

---

### WR-06: `enrollAllToCamera` dispatches jobs regardless of camera online status

**File:** `app/Services/CameraEnrollmentService.php:43-58`
**Issue:** `enrollAllToCamera` (called when a new camera is stored) dispatches `EnrollPersonnelBatch` for the camera unconditionally — there is no `$camera->is_online` check. Contrast this with `enrollPersonnel` (line 32-35) and `resyncAll` (line 52-54) which both gate on `is_online`. If a camera is registered while offline, a batch job will be dispatched and immediately fail or time out waiting for an offline device.
**Fix:**

```php
if ($camera->is_online) {
    foreach (array_chunk($personnelIds, $batchSize) as $chunk) {
        EnrollPersonnelBatch::dispatch($camera, $chunk);
    }
}
```

## Info

### IN-01: `show()` route function called with `cam` (summary object) instead of a Camera model shape

**File:** `resources/js/components/EnrollmentSummaryPanel.vue:19`
**Issue:** `show(cam)` passes a `CameraEnrollmentSummary` object. Wayfinder's `show()` for cameras is designed to accept a Camera-shaped object with an `id`. `CameraEnrollmentSummary` includes `id`, so this works at runtime, but the TypeScript type may produce a warning depending on how Wayfinder generates the function signature. Worth verifying after `wayfinder:generate` if type errors surface.
**Fix:** No code change required unless a type error appears; document the intent if it does.

---

### IN-02: Redundant `$table` property declaration in `CameraEnrollment` model

**File:** `app/Models/CameraEnrollment.php:19`
**Issue:** `protected $table = 'camera_enrollments';` is declared explicitly, but Laravel's default table name resolution for `CameraEnrollment` would produce `camera_enrollments` automatically (snake_case + plural). This is dead configuration.
**Fix:** Remove the line:

```php
// Remove: protected $table = 'camera_enrollments';
```

---

### IN-03: `upsertBatch` re-chunks already-chunked data

**File:** `app/Services/CameraEnrollmentService.php:71`
**Issue:** `upsertBatch` receives `$personnelIds` (an array), fetches the Personnel models, then calls `$personnel->chunk($batchSize)`. The job dispatcher in `enrollAllToCamera` already chunks before dispatching (line 56), so each job receives at most `$batchSize` IDs. The inner `chunk()` in `upsertBatch` will always iterate a single chunk of exactly the right size, making it a no-op overhead. For the `enrollPersonnel` path (single-personnel IDs), it is also a no-op. The logic is not wrong but it is misleading.
**Fix:** Either document that `upsertBatch` is expected to handle an already-chunked slice and remove the inner loop, or remove the chunking from the caller and let `upsertBatch` own the chunking entirely. Either approach is cleaner than the current double-chunking.

---

### IN-04: `formatRelativeTime` duplicated across two page components

**File:** `resources/js/pages/cameras/Show.vue:65-93` and `resources/js/pages/personnel/Show.vue:122-150`
**Issue:** The `formatRelativeTime` function is copy-pasted verbatim in both page components. Per project conventions, shared utilities belong in composables.
**Fix:** Extract to `resources/js/composables/useRelativeTime.ts` and import from both pages.

---

### IN-05: `EnrollPersonnelBatch` test uses `$this->mock()` (PHPUnit style) in a Pest test

**File:** `tests/Feature/Enrollment/EnrollPersonnelBatchTest.php:22`
**Issue:** `$this->mock(CameraEnrollmentService::class)` is available in Pest via the `TestCase` base, but the idiomatic Pest 4 / Laravel approach is to use `mock()` as a global helper or `$this->mock()` with `->shouldReceive()`. The test is functional, but project convention uses `Bus::fake()` and similar Laravel fakes rather than Mockery directly for service substitution. This is a minor style gap.
**Fix:** No functional change required; acceptable as-is.

---

_Reviewed: 2026-04-10T00:00:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

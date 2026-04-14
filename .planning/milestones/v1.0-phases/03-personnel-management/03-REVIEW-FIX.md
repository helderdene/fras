---
phase: 03-personnel-management
fixed_at: 2026-04-10T00:00:00Z
review_path: .planning/phases/03-personnel-management/03-REVIEW.md
iteration: 1
findings_in_scope: 6
fixed: 5
skipped: 1
status: partial
---

# Phase 03: Code Review Fix Report

**Fixed at:** 2026-04-10
**Source review:** .planning/phases/03-personnel-management/03-REVIEW.md
**Iteration:** 1

**Summary:**
- Findings in scope: 6
- Fixed: 5
- Skipped: 1

## Fixed Issues

### CR-01: Old photo deleted before new upload is confirmed -- data loss on storage failure

**Files modified:** `app/Http/Controllers/PersonnelController.php`
**Commit:** b002da6
**Applied fix:** Reordered the update logic to process (store) the new photo first, then delete the old photo only after the new path is confirmed stored. This prevents data loss if `Storage::put()` fails mid-operation.

### WR-01: No authorization -- any authenticated user can modify any personnel record

**Files modified:** `app/Http/Requests/Personnel/StorePersonnelRequest.php`, `app/Http/Requests/Personnel/UpdatePersonnelRequest.php`
**Commit:** 34bf001
**Applied fix:** Added PHPDoc documentation to both Form Request `authorize()` methods explaining the intentional absence of granular authorization for this single-site, single-operator facility, and noting where role-based access control should be added if multi-role support is introduced.

### WR-02: `file_get_contents($file->path())` is unsafe for large uploads

**Files modified:** `app/Services/PhotoProcessor.php`
**Commit:** def1c8b
**Applied fix:** Replaced `Image::decode(file_get_contents($file->path()))` with `Image::decodePath($file->path())`, which reads the file directly from disk without loading the entire contents into a PHP string first. This is the correct Intervention Image v4 API for path-based decoding.

### WR-03: `encodeUsingFileExtension('jpg', quality: $quality)` -- incorrect API call

**Files modified:** `app/Services/PhotoProcessor.php`
**Commit:** 6212115
**Applied fix:** Replaced `encodeUsingFileExtension('jpg', quality: $quality)` with `$image->encode(new JpegEncoder(quality: $quality))` (both occurrences). While the variadic spread in Intervention v4 does pass named arguments through correctly, using the explicit `JpegEncoder` is more self-documenting and guarantees the quality parameter is applied without relying on the internal argument forwarding chain.

### WR-04: Delete test does not use `assertModelMissing()`

**Files modified:** `tests/Feature/Personnel/PersonnelCrudTest.php`
**Commit:** 8ae269b
**Applied fix:** Replaced `expect(Personnel::find($personnel->id))->toBeNull()` with `$this->assertModelMissing($personnel)`, which is the idiomatic Laravel assertion that checks the database directly and is resilient to SoftDeletes being added in the future.

## Skipped Issues

### WR-05: Sync status is hardcoded to `'not-synced'` throughout the UI

**File:** `resources/js/pages/personnel/Index.vue:175`, `resources/js/pages/personnel/Show.vue:236`
**Reason:** The hardcoded `status="not-synced"` is an intentional placeholder for this phase. The enrollment status data does not yet exist on the backend (no pivot table or `enrollment_status` field on the personnel/camera relationship). Binding `:status="p.enrollment_status ?? 'not-synced'"` as suggested would reference a property that does not exist on the TypeScript type, causing compilation errors. The real fix requires backend schema and API changes that belong to the enrollment phase, not a code review fix.
**Original issue:** `<SyncStatusDot status="not-synced" />` is hardcoded in both the index table and per-camera enrollment list on the show page. The component supports multiple states but the actual enrollment status from the backend is never passed down.

---

_Fixed: 2026-04-10_
_Fixer: Claude (gsd-code-fixer)_
_Iteration: 1_

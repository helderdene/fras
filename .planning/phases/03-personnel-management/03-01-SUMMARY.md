---
phase: 03-personnel-management
plan: 01
subsystem: api
tags: [eloquent, crud, intervention-image, photo-processing, inertia, typescript]

# Dependency graph
requires:
  - phase: 01-infrastructure-mqtt-foundation
    provides: "MQTT infrastructure, hds.php config, database migrations"
  - phase: 02-camera-management-liveness
    provides: "Camera model, CameraController pattern, CRUD test pattern"
provides:
  - "Personnel Eloquent model with fillable attributes, casts, photo_url accessor"
  - "PersonnelController with all 7 resource methods (index/create/show/edit/store/update/destroy)"
  - "PhotoProcessor service for resize, compress, hash, store, delete"
  - "StorePersonnelRequest and UpdatePersonnelRequest form requests"
  - "PersonnelFactory with blockList and withPhoto states"
  - "TypeScript Personnel interface"
  - "Resource route under auth+verified middleware"
  - "Stub Vue pages for personnel (Index, Create, Show, Edit)"
affects: [03-02, 03-03, 04-enrollment-sync]

# Tech tracking
tech-stack:
  added: [intervention/image v4, intervention/image-laravel v4]
  patterns: [PhotoProcessor service for image preprocessing, photo_url accessor with Storage facade]

key-files:
  created:
    - app/Models/Personnel.php
    - app/Http/Controllers/PersonnelController.php
    - app/Services/PhotoProcessor.php
    - app/Http/Requests/Personnel/StorePersonnelRequest.php
    - app/Http/Requests/Personnel/UpdatePersonnelRequest.php
    - database/factories/PersonnelFactory.php
    - database/seeders/PersonnelSeeder.php
    - resources/js/types/personnel.ts
    - resources/js/pages/personnel/Index.vue
    - resources/js/pages/personnel/Create.vue
    - resources/js/pages/personnel/Show.vue
    - resources/js/pages/personnel/Edit.vue
    - tests/Feature/Personnel/PersonnelCrudTest.php
    - tests/Feature/Personnel/PhotoProcessorTest.php
  modified:
    - database/seeders/DatabaseSeeder.php
    - routes/web.php
    - resources/js/types/index.ts

key-decisions:
  - "Intervention Image v4 API: decode() + encodeUsingFileExtension() (not v3 read/encodeByExtension)"
  - "Explicit $table = 'personnel' on model (Laravel pluralizes to 'personnels' otherwise)"
  - "Stub Vue page components created for Inertia test resolution; full implementation in Plan 03-02/03-03"

patterns-established:
  - "PhotoProcessor service pattern: orient, scaleDown, encode JPEG with quality fallback loop, UUID filenames"
  - "Personnel model with photo_url appended accessor via Storage::disk('public')->url()"

requirements-completed: [PERS-01, PERS-02, PERS-03, PERS-07]

# Metrics
duration: 7min
completed: 2026-04-10
---

# Phase 03 Plan 01: Personnel Backend Core Summary

**Personnel CRUD API with PhotoProcessor service (resize/compress/hash via Intervention Image v4), 27 tests passing, TypeScript type contract**

## Performance

- **Duration:** 7 min
- **Started:** 2026-04-10T10:46:43Z
- **Completed:** 2026-04-10T10:54:29Z
- **Tasks:** 2
- **Files modified:** 16

## Accomplishments
- Personnel model with all fillable attributes, integer/date casts, and photo_url accessor auto-appended to JSON
- PersonnelController with all 7 resource methods delegating photo processing to PhotoProcessor service
- PhotoProcessor service resizes to max 1080px, compresses to JPEG with iterative quality fallback, computes MD5 hash, stores with UUID filenames
- 27 comprehensive tests (17 CRUD + 10 photo processing) all passing, full suite 135 tests with no regressions

## Task Commits

Each task was committed atomically:

1. **Task 1: Personnel model, factory, seeder, controller, form requests, routes, TypeScript type** - `2830d7c` (feat)
2. **Task 2: PhotoProcessor service tests for resize, compress, hash, delete** - `0615490` (test)

## Files Created/Modified
- `app/Models/Personnel.php` - Eloquent model with fillable, casts, photo_url accessor, explicit table name
- `app/Http/Controllers/PersonnelController.php` - Resource controller with all 7 CRUD methods
- `app/Services/PhotoProcessor.php` - Photo preprocessing: orient, resize, compress, hash, store, delete
- `app/Http/Requests/Personnel/StorePersonnelRequest.php` - Create validation with required photo
- `app/Http/Requests/Personnel/UpdatePersonnelRequest.php` - Update validation with optional photo, unique ignore
- `database/factories/PersonnelFactory.php` - Factory with blockList and withPhoto states
- `database/seeders/PersonnelSeeder.php` - Creates 8 allow + 2 block personnel records
- `database/seeders/DatabaseSeeder.php` - Added PersonnelSeeder call
- `routes/web.php` - Added personnel resource route under auth middleware
- `resources/js/types/personnel.ts` - TypeScript Personnel interface
- `resources/js/types/index.ts` - Added personnel barrel export
- `resources/js/pages/personnel/Index.vue` - Stub page for Inertia rendering
- `resources/js/pages/personnel/Create.vue` - Stub page for Inertia rendering
- `resources/js/pages/personnel/Show.vue` - Stub page for Inertia rendering
- `resources/js/pages/personnel/Edit.vue` - Stub page for Inertia rendering
- `tests/Feature/Personnel/PersonnelCrudTest.php` - 17 CRUD and validation tests
- `tests/Feature/Personnel/PhotoProcessorTest.php` - 10 photo processing tests

## Decisions Made
- **Intervention Image v4 API:** Used `decode()` + `encodeUsingFileExtension()` instead of plan's v3 `read()` + `encodeByExtension()`. v4 renamed these methods.
- **Explicit table name:** Added `$table = 'personnel'` because Laravel's pluralizer converts "personnel" to "personnels", which doesn't match the migration's table name.
- **Stub Vue pages:** Created minimal Vue page components so Inertia test assertions can resolve page components. Full implementation deferred to Plans 03-02 and 03-03.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Intervention Image v4 API method names**
- **Found during:** Task 1 (PhotoProcessor implementation)
- **Issue:** Plan specified `Image::read()` and `encodeByExtension()` which are Intervention Image v3 methods. v4 uses `decode()` and `encodeUsingFileExtension()`.
- **Fix:** Used `Image::decode(file_get_contents($file->path()))` and `$image->encodeUsingFileExtension('jpg', quality: $quality)`
- **Files modified:** app/Services/PhotoProcessor.php
- **Verification:** All photo processing tests pass
- **Committed in:** 2830d7c (Task 1 commit)

**2. [Rule 1 - Bug] Fixed table name pluralization**
- **Found during:** Task 1 (running tests)
- **Issue:** Laravel auto-pluralized "personnel" to "personnels" but migration creates table "personnel"
- **Fix:** Added `protected $table = 'personnel'` to Personnel model
- **Files modified:** app/Models/Personnel.php
- **Verification:** All 17 CRUD tests pass
- **Committed in:** 2830d7c (Task 1 commit)

**3. [Rule 3 - Blocking] Created stub Vue pages for Inertia test resolution**
- **Found during:** Task 1 (controller renders Inertia pages that must exist)
- **Issue:** PersonnelController renders personnel/Index, Create, Show, Edit pages that don't exist yet
- **Fix:** Created minimal stub Vue components in resources/js/pages/personnel/
- **Files modified:** resources/js/pages/personnel/{Index,Create,Show,Edit}.vue
- **Verification:** All Inertia assertions pass
- **Committed in:** 2830d7c (Task 1 commit)

---

**Total deviations:** 3 auto-fixed (2 bugs, 1 blocking)
**Impact on plan:** All auto-fixes necessary for correctness. No scope creep.

## Issues Encountered
None - all issues were anticipated deviation types resolved inline.

## User Setup Required
None - no external service configuration required.

## Known Stubs
- `resources/js/pages/personnel/Index.vue` - Stub page, full implementation in Plan 03-02
- `resources/js/pages/personnel/Create.vue` - Stub page, full implementation in Plan 03-02
- `resources/js/pages/personnel/Show.vue` - Stub page, full implementation in Plan 03-03
- `resources/js/pages/personnel/Edit.vue` - Stub page, full implementation in Plan 03-02

These stubs are intentional -- Plans 03-02 and 03-03 will implement the full Vue frontend.

## Next Phase Readiness
- Backend API complete and tested, ready for frontend implementation in Plans 03-02 and 03-03
- TypeScript Personnel interface available for frontend type safety
- PhotoProcessor service ready for enrollment sync in Phase 04
- All 7 resource routes registered and accessible

## Self-Check: PASSED

All 15 key files verified present. Both task commits (2830d7c, 0615490) verified in git log. 27 personnel tests pass, 135 full suite tests pass with no regressions.

---
*Phase: 03-personnel-management*
*Completed: 2026-04-10*

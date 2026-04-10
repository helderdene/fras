---
phase: 02-camera-management-liveness
plan: 01
subsystem: database, api
tags: [eloquent, inertia, broadcast, reverb, crud, mapbox]

# Dependency graph
requires:
  - phase: 01-infrastructure-mqtt-foundation
    provides: MQTT listener, Reverb broadcasting, cameras migration, hds.php config
provides:
  - Camera Eloquent model with factory and seeder
  - CameraController with 7 resource CRUD methods
  - StoreCameraRequest and UpdateCameraRequest form request validation
  - CameraStatusChanged broadcast event on fras.alerts channel
  - Camera resource routes behind auth+verified middleware
  - Stub Vue page components (cameras/Index, Create, Show, Edit)
affects: [02-camera-management-liveness, 03-personnel-management, 04-enrollment-sync, 05-recognition-alerts-dashboard]

# Tech tracking
tech-stack:
  added: []
  patterns: [resource controller with Inertia rendering, broadcast event for model state changes, form request with unique-ignore-self validation]

key-files:
  created:
    - app/Models/Camera.php
    - app/Http/Controllers/CameraController.php
    - app/Http/Requests/Camera/StoreCameraRequest.php
    - app/Http/Requests/Camera/UpdateCameraRequest.php
    - app/Events/CameraStatusChanged.php
    - database/factories/CameraFactory.php
    - database/seeders/CameraSeeder.php
    - tests/Feature/Camera/CameraCrudTest.php
    - resources/js/pages/cameras/Index.vue
    - resources/js/pages/cameras/Create.vue
    - resources/js/pages/cameras/Show.vue
    - resources/js/pages/cameras/Edit.vue
  modified:
    - database/seeders/DatabaseSeeder.php
    - routes/web.php

key-decisions:
  - "Used CarbonImmutable for datetime cast assertion (Laravel 13 default)"
  - "Created stub Vue page components to satisfy Inertia v3 page existence check during testing"
  - "Used test() convention (not it()) to match existing project test style"

patterns-established:
  - "Resource controller pattern: Inertia::render with mapbox config props for map-enabled pages"
  - "Form request pattern: unique-ignore-self via Rule::unique()->ignore() for update requests"
  - "Broadcast event pattern: ShouldBroadcast on PrivateChannel('fras.alerts') with typed constructor props"
  - "Factory state pattern: online()/offline() states for camera status testing"

requirements-completed: [CAM-01, CAM-02, CAM-05, CAM-06]

# Metrics
duration: 5min
completed: 2026-04-10
---

# Phase 02 Plan 01: Camera CRUD Backend Summary

**Camera Eloquent model with resource controller, form request validation, CameraStatusChanged broadcast event, factory/seeder, and 18 passing feature tests**

## Performance

- **Duration:** 5 min
- **Started:** 2026-04-10T08:50:09Z
- **Completed:** 2026-04-10T08:55:27Z
- **Tasks:** 2
- **Files modified:** 14

## Accomplishments
- Camera model with fillable attributes, decimal/boolean/datetime casts, and factory with online/offline states
- CameraController with all 7 resource methods rendering Inertia pages with mapbox config props
- CameraStatusChanged broadcast event on fras.alerts private channel for real-time status updates
- Form request validation with unique device_id enforcement and coordinate range checks
- 18 feature tests covering model behavior, event broadcasting, validation rules, and all CRUD routes

## Task Commits

Each task was committed atomically:

1. **Task 1: Camera model, factory, seeder, event, form requests, and tests** - `d4195d3` (feat)
2. **Task 2: CameraController with CRUD methods, routes, and route tests** - `5de09a0` (feat)

## Files Created/Modified
- `app/Models/Camera.php` - Eloquent model with Fillable attribute and decimal/boolean/datetime casts
- `app/Http/Controllers/CameraController.php` - Resource controller with 7 CRUD methods rendering Inertia pages
- `app/Http/Requests/Camera/StoreCameraRequest.php` - Validation for camera creation with unique device_id
- `app/Http/Requests/Camera/UpdateCameraRequest.php` - Validation for camera updates with unique-ignore-self
- `app/Events/CameraStatusChanged.php` - Broadcast event implementing ShouldBroadcast on fras.alerts
- `database/factories/CameraFactory.php` - Factory with Butuan City coordinates and online/offline states
- `database/seeders/CameraSeeder.php` - 4 seeded cameras with recognizable names and real Butuan coordinates
- `database/seeders/DatabaseSeeder.php` - Added CameraSeeder call
- `routes/web.php` - Camera resource routes behind auth+verified middleware
- `tests/Feature/Camera/CameraCrudTest.php` - 18 feature tests for model, event, validation, and routes
- `resources/js/pages/cameras/Index.vue` - Stub page component for camera list
- `resources/js/pages/cameras/Create.vue` - Stub page component for camera creation form
- `resources/js/pages/cameras/Show.vue` - Stub page component for camera detail view
- `resources/js/pages/cameras/Edit.vue` - Stub page component for camera edit form

## Decisions Made
- Used `CarbonImmutable` for datetime cast assertion since Laravel 13 uses immutable dates by default
- Created stub Vue page components with typed props to satisfy Inertia v3 page existence checks during testing; these will be replaced with full implementations in Plan 02-02
- Used `test()` function convention (not `it()`) to match the existing project test style per Pest skill consistency-first principle
- Added `beforeEach` with `$this->withoutVite()` to bypass Vite manifest requirement in tests since Vue pages are stubs

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed CarbonImmutable assertion in casts test**
- **Found during:** Task 1 (GREEN phase)
- **Issue:** Test asserted `toBeInstanceOf(Carbon::class)` but Laravel 13 casts datetime to `CarbonImmutable`
- **Fix:** Changed assertion to use `CarbonImmutable::class`
- **Files modified:** tests/Feature/Camera/CameraCrudTest.php
- **Verification:** Test passes with correct CarbonImmutable type check
- **Committed in:** d4195d3 (Task 1 commit)

**2. [Rule 3 - Blocking] Created stub Vue page components for Inertia test resolution**
- **Found during:** Task 2 (route-level tests)
- **Issue:** Inertia v3 validates page component files exist on disk; ViteException and page-not-found assertion failures
- **Fix:** Created minimal stub Vue components with typed props at resources/js/pages/cameras/
- **Files modified:** resources/js/pages/cameras/{Index,Create,Show,Edit}.vue
- **Verification:** All 18 tests pass, no Vite or page existence errors
- **Committed in:** 5de09a0 (Task 2 commit)

**3. [Rule 3 - Blocking] Added withoutVite() to bypass Vite manifest in tests**
- **Found during:** Task 2 (route-level tests)
- **Issue:** Vite manifest not available during test runs, causing 500 errors on Inertia page renders
- **Fix:** Added `beforeEach` with `$this->withoutVite()` to test file
- **Files modified:** tests/Feature/Camera/CameraCrudTest.php
- **Verification:** GET route tests return 200 instead of 500
- **Committed in:** 5de09a0 (Task 2 commit)

---

**Total deviations:** 3 auto-fixed (1 bug, 2 blocking)
**Impact on plan:** All auto-fixes necessary for test correctness. No scope creep. Stub Vue components are intentional placeholders that will be fully implemented in Plan 02-02.

## Known Stubs

| File | Line | Reason |
|------|------|--------|
| resources/js/pages/cameras/Index.vue | entire file | Placeholder -- full UI implementation in Plan 02-02 |
| resources/js/pages/cameras/Create.vue | entire file | Placeholder -- full UI implementation in Plan 02-02 |
| resources/js/pages/cameras/Show.vue | entire file | Placeholder -- full UI implementation in Plan 02-02 |
| resources/js/pages/cameras/Edit.vue | entire file | Placeholder -- full UI implementation in Plan 02-02 |

## Issues Encountered
None -- plan executed cleanly after auto-fixes.

## User Setup Required
None -- no external service configuration required.

## Next Phase Readiness
- Camera model and CRUD backend complete, ready for frontend implementation in Plan 02-02
- CameraStatusChanged event ready for heartbeat handler integration in Plan 02-03
- Factory and seeder provide realistic test data for all subsequent plans
- 7 resource routes registered and verified with route:list

## Self-Check: PASSED

All 14 created/modified files verified on disk. Both task commits (d4195d3, 5de09a0) verified in git log. 18/18 tests passing, 7/7 routes registered.

---
*Phase: 02-camera-management-liveness*
*Completed: 2026-04-10*

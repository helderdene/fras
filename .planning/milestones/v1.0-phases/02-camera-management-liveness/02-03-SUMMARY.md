---
phase: 02-camera-management-liveness
plan: 03
subsystem: ui
tags: [mapbox-gl, vue3, inertia, echo, websocket, tailwindcss, wayfinder]

# Dependency graph
requires:
  - phase: 02-camera-management-liveness/02-01
    provides: Camera model, CameraController with CRUD methods, resource routes, form requests
  - phase: 02-camera-management-liveness/02-02
    provides: HeartbeatHandler, OnlineOfflineHandler, CameraStatusChanged event broadcasting
provides:
  - Camera TypeScript interfaces (Camera, CameraStatusPayload)
  - MapboxMap reusable component (interactive + read-only modes)
  - CameraStatusDot status indicator component
  - Camera Index page with table and real-time Echo updates
  - Camera Create page with interactive map and bidirectional coordinate sync
  - Camera Edit page with pre-populated fields
  - Camera Show page with two-column layout, read-only map, delete dialog
  - Sidebar navigation with Cameras link
affects: [dashboard, personnel-management, enrollment-sync, alert-dashboard]

# Tech tracking
tech-stack:
  added: [mapbox-gl v3]
  patterns: [setLayoutProps for dynamic breadcrumbs, useEcho for real-time updates, bidirectional map-coordinate sync]

key-files:
  created:
    - resources/js/types/camera.ts
    - resources/js/components/MapboxMap.vue
    - resources/js/components/CameraStatusDot.vue
  modified:
    - resources/js/types/index.ts
    - resources/js/components/AppSidebar.vue
    - resources/js/pages/cameras/Index.vue
    - resources/js/pages/cameras/Create.vue
    - resources/js/pages/cameras/Edit.vue
    - resources/js/pages/cameras/Show.vue
    - package.json

key-decisions:
  - "Used setLayoutProps instead of defineOptions for dynamic breadcrumbs referencing props (Vue compiler-sfc hoisting limitation)"
  - "MapboxMap uses plain let variables (not ref) for map/marker instances to avoid Vue 3 Proxy breaking mapbox-gl internals"
  - "Coordinate inputs use type=text (not number) to preserve decimal precision"
  - "Enrolled Personnel placeholder on Show page -- intentional stub for Phase 4 enrollment sync"

patterns-established:
  - "setLayoutProps pattern: Use setLayoutProps() from Inertia v3 for breadcrumbs that reference component props"
  - "MapboxMap reuse: Interactive mode for create/edit forms, read-only mode for detail pages"
  - "Real-time status: useEcho composable on fras.alerts channel for CameraStatusChanged events"
  - "Bidirectional sync: Map click updates form inputs, form input changes update map center (debounced 300ms)"

requirements-completed: [CAM-01, CAM-02, CAM-05, CAM-06]

# Metrics
duration: 6min
completed: 2026-04-10
---

# Phase 02 Plan 03: Camera Management UI Summary

**Complete camera management UI with Mapbox GL JS maps, real-time Echo status updates, and full CRUD pages**

## Performance

- **Duration:** 6 min
- **Started:** 2026-04-10T09:03:17Z
- **Completed:** 2026-04-10T09:09:17Z
- **Tasks:** 4 (3 auto + 1 checkpoint auto-approved)
- **Files modified:** 11

## Accomplishments
- Installed mapbox-gl v3 and created Camera/CameraStatusPayload TypeScript interfaces
- Built reusable MapboxMap component with interactive (create/edit) and read-only (show) modes
- Implemented all 4 camera pages: Index (table + empty state + Echo), Create (form + map), Edit (pre-populated + map), Show (two-column + delete dialog)
- Added Cameras to sidebar navigation after Dashboard
- Real-time camera status updates via Echo WebSocket on Index and Show pages

## Task Commits

Each task was committed atomically:

1. **Task 1: Install mapbox-gl, create types, build components, update sidebar** - `659a51d` (feat)
2. **Task 2: Create camera Index and Create pages** - `58e569a` (feat)
3. **Task 3: Create camera Edit and Show pages** - `598fe22` (feat)
4. **Task 4: Verify camera management UI in browser** - Auto-approved checkpoint

## Files Created/Modified
- `package.json` - Added mapbox-gl v3 dependency
- `resources/js/types/camera.ts` - Camera and CameraStatusPayload TypeScript interfaces
- `resources/js/types/index.ts` - Added camera type barrel export
- `resources/js/components/MapboxMap.vue` - Reusable Mapbox GL JS map with interactive/read-only modes
- `resources/js/components/CameraStatusDot.vue` - Online/offline status indicator with aria-label
- `resources/js/components/AppSidebar.vue` - Added Cameras nav item with Camera icon
- `resources/js/pages/cameras/Index.vue` - Camera list with table, empty state, and real-time Echo updates
- `resources/js/pages/cameras/Create.vue` - Create form with interactive map and bidirectional coordinate sync
- `resources/js/pages/cameras/Edit.vue` - Edit form with pre-populated fields and interactive map
- `resources/js/pages/cameras/Show.vue` - Detail page with two-column layout, read-only map, delete dialog

## Decisions Made
- Used `setLayoutProps()` instead of `defineOptions()` for Edit and Show page breadcrumbs that reference `props.camera` -- Vue compiler-sfc hoists defineOptions outside setup() and cannot reference local variables
- MapboxMap stores map/marker as plain `let` variables (not Vue refs) to avoid Proxy interference with mapbox-gl internals
- Coordinate inputs use `type="text"` to preserve decimal precision (browser number inputs can lose precision)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed defineOptions referencing props in Edit.vue and Show.vue**
- **Found during:** Task 3 (Camera Edit and Show pages)
- **Issue:** Plan used `defineOptions({ layout: { breadcrumbs: [{ title: props.camera.name }] } })` but Vue compiler-sfc hoists defineOptions outside setup(), so `props` is not accessible
- **Fix:** Replaced `defineOptions()` with `setLayoutProps()` from Inertia v3, which runs inside setup() and can reference props
- **Files modified:** resources/js/pages/cameras/Edit.vue, resources/js/pages/cameras/Show.vue
- **Verification:** `npm run build` exits 0, no compilation errors
- **Committed in:** 598fe22 (Task 3 commit)

---

**Total deviations:** 1 auto-fixed (1 bug fix)
**Impact on plan:** Essential fix for compilation. setLayoutProps is the correct Inertia v3 pattern for dynamic layout data. No scope creep.

## Known Stubs

| File | Line | Stub | Reason |
|------|------|------|--------|
| resources/js/pages/cameras/Show.vue | ~207-225 | "No personnel enrolled" placeholder | Intentional -- enrollment UI will be built in Phase 4 (enrollment sync). Documented in plan as D-16. |

## Issues Encountered
None beyond the defineOptions compilation fix documented above.

## User Setup Required
None - no external service configuration required. Mapbox token is already configured via CameraController server-side props.

## Next Phase Readiness
- All camera CRUD UI complete, ready for personnel management (Phase 3)
- MapboxMap component reusable for future dashboard map view
- Echo real-time pattern established for future alert broadcasting
- Enrolled Personnel placeholder ready to be wired in Phase 4

---
*Phase: 02-camera-management-liveness*
*Completed: 2026-04-10*

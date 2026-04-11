---
phase: 06-dashboard-map
plan: 02
subsystem: ui
tags: [mapbox, vue, inertia, websocket, echo, css-animation, markers, popups]

# Dependency graph
requires:
  - phase: 06-dashboard-map-01
    provides: DashboardController, DashboardLayout, DashboardTopNav, StatusBar, ConnectionBanner, three-panel shell
  - phase: 05-recognition-alerting
    provides: RecognitionAlertPayload, AlertSeverity, RecognitionEvent, useAlertSound, broadcast infrastructure
  - phase: 02-camera-management-liveness
    provides: Camera model with GPS, CameraStatusPayload, MapboxMap patterns
provides:
  - DashboardMap.vue with multi-marker placement, Mapbox popups, pulse ring animation
  - useDashboardMap.ts composable for map instance management, marker CRUD, flyTo, switchStyle, resizeMap
  - Pulse ring CSS keyframes animation (3s expanding red ring)
  - Echo listeners for RecognitionAlert (pulse + count + sound) and CameraStatusChanged (marker color + popup update)
  - Theme toggle -> Mapbox style switch integration
  - Panel toggle -> map resize integration
  - Sound toggle button in DashboardTopNav
  - Empty state when no cameras registered
affects: [06-03-PLAN]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Custom HTML markers with mapboxgl.Marker element option for CSS-controlled styling and DOM-based pulse animation
    - setDOMContent for XSS-safe Mapbox popup content (T-6-04 mitigation)
    - flyTo with explicit getPopup().addTo(map) instead of togglePopup to ensure popup always opens
    - CSS keyframes pulse-ring animation with animationend self-removal for overlapping rings

key-files:
  created:
    - resources/js/components/DashboardMap.vue
    - resources/js/composables/useDashboardMap.ts
  modified:
    - resources/css/app.css
    - resources/js/pages/Dashboard.vue
    - resources/js/components/DashboardTopNav.vue

key-decisions:
  - "Custom HTML markers (not GeoJSON layers) for persistence across Mapbox setStyle dark/light toggle"
  - "setDOMContent for popup content (XSS-safe DOM API) instead of setHTML with string interpolation"
  - "flyTo uses getPopup().addTo(map) not togglePopup to guarantee popup opens from left rail clicks"
  - "Pulse ring CSS keyframes with animationend self-removal allows multiple overlapping rings"
  - "Sound toggle button passed as prop from Dashboard.vue to DashboardTopNav (no provide/inject complexity)"

patterns-established:
  - "DashboardMap expose pattern: defineExpose for parent component access to triggerPulse, flyTo, switchStyle, resizeMap"
  - "Marker management: plain Map<number, MarkerEntry> keyed by camera.id for O(1) lookup on real-time events"
  - "Panel resize debounce: 250ms setTimeout after panel toggle to account for 200ms CSS transition"

requirements-completed: [DASH-01, DASH-02, DASH-03, DASH-06]

# Metrics
duration: 6min
completed: 2026-04-11
---

# Phase 6 Plan 02: DashboardMap & Real-Time Integration Summary

**Multi-marker Mapbox map with camera popups, pulse ring animation on recognition events, theme-synced style toggle, and sound/empty-state integration**

## Performance

- **Duration:** 6 min
- **Started:** 2026-04-11T05:28:12Z
- **Completed:** 2026-04-11T05:34:32Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- DashboardMap.vue renders all cameras as green/gray HTML markers at GPS coordinates with Mapbox popups (camera name, status, last seen, recognition count, detail link)
- useDashboardMap composable manages map lifecycle, marker CRUD, pulse animation, flyTo with explicit popup open, style switching, and resize
- Echo listeners for RecognitionAlert trigger pulse ring animation on camera markers, increment recognition counts, and play alert sound for critical events
- Echo listeners for CameraStatusChanged update marker color and popup content in real time
- Theme toggle switches both app dark/light mode and Mapbox map style simultaneously
- Sound toggle button added to DashboardTopNav with tooltip and aria-label
- Empty state renders with camera icon, heading, and "Add Camera" CTA when no cameras exist

## Task Commits

Each task was committed atomically:

1. **Task 1: DashboardMap component, useDashboardMap composable, pulse ring CSS, and Dashboard.vue map integration** - `d668697` (feat)
2. **Task 2: Theme toggle, sound toggle, empty state, and StatusBar integration** - `88509a9` (feat)

## Files Created/Modified
- `resources/js/components/DashboardMap.vue` - Multi-marker map component with skeleton loading, error state, and defineExpose for parent access
- `resources/js/composables/useDashboardMap.ts` - Map instance management composable: marker CRUD, pulse trigger, flyTo, switchStyle, resizeMap, cleanup
- `resources/css/app.css` - Pulse ring CSS keyframes animation and camera marker styles (online/offline/dark mode)
- `resources/js/pages/Dashboard.vue` - Map integration with Echo listeners (RecognitionAlert, CameraStatusChanged), theme-to-style watch, panel-resize watch, alert sound, empty state
- `resources/js/components/DashboardTopNav.vue` - Dynamic theme toggle aria-label ("Switch to light/dark mode"), sound toggle button with tooltip

## Decisions Made
- Custom HTML markers chosen over GeoJSON layers because HTML markers (DOM elements) persist across Mapbox setStyle() calls, avoiding complex re-add logic on dark/light toggle
- setDOMContent used for popup content (not setHTML) as defense-in-depth XSS mitigation per T-6-04
- flyTo explicitly opens popup via getPopup().addTo(map) instead of togglePopup() to prevent closing an already-open popup when clicking the same camera in left rail
- Sound toggle passed as prop/emit from Dashboard.vue to DashboardTopNav for simplicity (no provide/inject overhead)
- formatRelativeTime utility duplicated in composable (same logic as cameras/Index.vue) to avoid cross-module coupling

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed TypeScript null safety on popup in flyTo**
- **Found during:** Task 1 (verification step)
- **Issue:** `entry.marker.getPopup()` can return null/undefined per mapbox-gl types, causing TS18049 error
- **Fix:** Added null check: `if (popup && !popup.isOpen())` before calling `popup.addTo(map!)`
- **Files modified:** resources/js/composables/useDashboardMap.ts
- **Verification:** `npx vue-tsc --noEmit` passes with no new errors
- **Committed in:** d668697 (part of Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** TypeScript strict null safety fix. No scope creep.

## Issues Encountered
- Pre-existing TypeScript error in `resources/js/pages/personnel/Show.vue` (SyncStatusDot type mismatch) -- not caused by this plan, no new errors in dashboard files

## User Setup Required
None - no external service configuration required.

## Known Stubs
- Left rail `<aside>` is empty placeholder (CameraRail component added in Plan 03)
- Right feed `<aside>` is empty placeholder (DashboardAlertFeed component added in Plan 03)

These stubs are intentional -- they represent the panel structure that Plan 03 will fill with actual components.

## Next Phase Readiness
- Plan 03 (CameraRail + AlertFeed) can fill the left and right `<aside>` panels
- DashboardMap exposes triggerPulse, flyTo, updateMarkerStatus, switchStyle, resizeMap for Plan 03 integration
- selectedCameraId ref in Dashboard.vue ready for Plan 03 camera-to-feed filtering (D-13)
- cameras reactive array in Dashboard.vue ready for Plan 03 left rail rendering

---
*Phase: 06-dashboard-map*
*Completed: 2026-04-11*

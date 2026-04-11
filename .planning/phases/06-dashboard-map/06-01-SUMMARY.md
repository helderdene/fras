---
phase: 06-dashboard-map
plan: 01
subsystem: ui
tags: [inertia, vue, dashboard, layout, mapbox, reverb, websocket, status-bar]

# Dependency graph
requires:
  - phase: 05-recognition-alerting
    provides: RecognitionEvent model, AlertSeverity enum, broadcast infrastructure
  - phase: 02-camera-management-liveness
    provides: Camera model with GPS coordinates, CameraStatusDot pattern
provides:
  - DashboardController with cameras, todayStats, recentEvents, mapbox props
  - Queue depth JSON endpoint for status bar polling
  - Camera.recognitionEvents() HasMany relationship
  - DashboardLayout full-viewport shell (no AppLayout sidebar)
  - DashboardTopNav with logo, panel toggles, theme toggle, settings, user menu
  - StatusBar with MQTT/Reverb connection dots and queue depth
  - ConnectionBanner amber warning on Reverb disconnect
  - Dashboard.vue three-panel layout shell (280px, flex-1, 360px)
  - Layout resolver routing Dashboard to DashboardLayout
affects: [06-02-PLAN, 06-03-PLAN]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - DashboardLayout bypasses AppSidebarLayout for full-viewport pages
    - useConnectionStatus from @laravel/echo-vue for Reverb health monitoring
    - Queue depth polling via setInterval+fetch (not usePoll) for JSON endpoints
    - MQTT status inferred from Reverb connection (both share pipeline)

key-files:
  created:
    - app/Http/Controllers/DashboardController.php
    - resources/js/layouts/DashboardLayout.vue
    - resources/js/components/DashboardTopNav.vue
    - resources/js/components/StatusBar.vue
    - resources/js/components/ConnectionBanner.vue
    - tests/Feature/DashboardControllerTest.php
  modified:
    - app/Models/Camera.php
    - routes/web.php
    - resources/js/app.ts
    - resources/js/pages/Dashboard.vue

key-decisions:
  - "DashboardLayout is minimal shell (flex column + Toaster); Dashboard.vue orchestrates all sub-components directly to avoid prop drilling"
  - "Queue depth polled via setInterval+fetch every 30s (not usePoll which does full Inertia page reloads)"
  - "MQTT status inferred from Reverb connection status (both share real-time pipeline)"
  - "Theme toggle in DashboardTopNav switches between dark/light (not system) for operator simplicity"

patterns-established:
  - "DashboardLayout pattern: full-viewport layout bypassing AppSidebarLayout via app.ts resolver case"
  - "JSON endpoint polling: setInterval+fetch for lightweight status data, cleanup in onUnmounted"
  - "Panel toggle pattern: v-show with fixed widths for instant show/hide without re-render"

requirements-completed: [DASH-04, DASH-05]

# Metrics
duration: 7min
completed: 2026-04-11
---

# Phase 6 Plan 01: Dashboard Shell & Backend Summary

**DashboardController serving cameras/stats/events with full-viewport three-panel layout shell, top nav, status bar, and connection banner**

## Performance

- **Duration:** 7 min
- **Started:** 2026-04-11T05:18:06Z
- **Completed:** 2026-04-11T05:25:06Z
- **Tasks:** 2
- **Files modified:** 10

## Accomplishments
- DashboardController aggregates cameras with today_recognition_count, todayStats (4 metrics), recentEvents (50 max), and mapbox config
- Queue depth endpoint returns authenticated JSON for status bar polling
- Full-viewport DashboardLayout with DashboardTopNav (logo, panel toggles, theme toggle, settings, user menu), StatusBar (MQTT/Reverb dots + queue depth), and ConnectionBanner (amber disconnect warning)
- Dashboard.vue three-panel layout shell with toggleable panels (280px left, flex-1 center, 360px right)
- 9 comprehensive feature tests covering all controller endpoints and data aggregation

## Task Commits

Each task was committed atomically:

1. **Task 1: DashboardController, Camera relationship, route update, and tests** - `c8d06dd` (test: failing tests), `d436915` (feat: implementation)
2. **Task 2: DashboardLayout, DashboardTopNav, StatusBar, ConnectionBanner, layout resolver, Dashboard.vue shell** - `80bc227` (feat)

## Files Created/Modified
- `app/Http/Controllers/DashboardController.php` - Dashboard data aggregation (index + queueDepth)
- `app/Models/Camera.php` - Added recognitionEvents() HasMany relationship
- `routes/web.php` - Dashboard route to controller, queue-depth endpoint added
- `resources/js/layouts/DashboardLayout.vue` - Full-viewport flex column shell with Toaster
- `resources/js/components/DashboardTopNav.vue` - Top nav with logo, panel toggles, theme toggle, settings link, user dropdown
- `resources/js/components/StatusBar.vue` - Bottom bar with MQTT/Reverb connection dots and queue depth
- `resources/js/components/ConnectionBanner.vue` - Amber banner on Reverb disconnect with slide transition
- `resources/js/app.ts` - Layout resolver routes Dashboard to DashboardLayout
- `resources/js/pages/Dashboard.vue` - Three-panel command center shell with queue depth polling
- `tests/Feature/DashboardControllerTest.php` - 9 tests covering auth, props, stats, events, mapbox, queue depth

## Decisions Made
- DashboardLayout is a minimal wrapper (flex column + Toaster); Dashboard.vue directly renders DashboardTopNav, ConnectionBanner, panels, and StatusBar to avoid prop drilling through the layout
- Queue depth uses setInterval+fetch (not Inertia usePoll) because it needs lightweight JSON polling, not full page reloads
- MQTT connection status is inferred from Reverb status (both share the real-time pipeline; granular MQTT health check deferred)
- Theme toggle in DashboardTopNav switches between dark/light directly (not system) for quick operator toggling
- RecognitionEvent factory in tests shares a single Personnel instance to avoid inflating enrolled count

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed enrolled count inflation in todayStats test**
- **Found during:** Task 1 (TDD GREEN phase)
- **Issue:** RecognitionEvent factory auto-creates Personnel via `personnel_id => Personnel::factory()`, inflating the enrolled count beyond the explicit 5
- **Fix:** Shared a single Personnel instance across all recognition events in the test, created only 4 additional personnel
- **Files modified:** tests/Feature/DashboardControllerTest.php
- **Verification:** All 9 tests pass with correct enrolled count of 5
- **Committed in:** d436915 (part of Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** Test data setup fix only. No scope creep.

## Issues Encountered
- Pre-existing TypeScript error in `resources/js/pages/personnel/Show.vue` (SyncStatusDot type mismatch) -- not caused by this plan, no new errors in dashboard files

## User Setup Required
None - no external service configuration required.

## Known Stubs
- Left rail `<aside>` is empty placeholder (CameraRail component added in Plan 03)
- Center `<main>` is empty placeholder (DashboardMap component added in Plan 02)
- Right feed `<aside>` is empty placeholder (DashboardAlertFeed component added in Plan 03)

These stubs are intentional -- they represent the three-panel structure that Plans 02 and 03 will fill with actual components.

## Next Phase Readiness
- Plan 02 (DashboardMap) can fill the center `<main>` panel with the Mapbox map component
- Plan 03 (CameraRail + AlertFeed) can fill the left and right `<aside>` panels
- DashboardController provides all data props needed by Plans 02 and 03 (cameras, recentEvents, mapbox config)
- useConnectionStatus pattern established for Reverb health monitoring

---
*Phase: 06-dashboard-map*
*Completed: 2026-04-11*

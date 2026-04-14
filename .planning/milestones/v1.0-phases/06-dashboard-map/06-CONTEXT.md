# Phase 6: Dashboard & Map - Context

**Gathered:** 2026-04-11
**Status:** Ready for planning

<domain>
## Phase Boundary

Operators have a full-viewport command center with a live Mapbox map showing camera positions with GPS markers, real-time expanding ring animations on recognition events, a camera list rail with per-camera recognition counts and "Today" statistics, a live alert feed reusing Phase 5 components, a status bar showing MQTT/Reverb/queue health, and dark/light map style toggle.

</domain>

<decisions>
## Implementation Decisions

### Three-Panel Layout
- **D-01:** Fixed-width panels — left rail ~280px, right alert feed ~360px, center map fills remaining space. Panels can be toggled open/closed but not resized by dragging.
- **D-02:** Full-viewport dedicated layout — dashboard gets its own layout WITHOUT the standard AppLayout sidebar. Maximum screen real estate. Navigation to other pages via a minimal top bar with logo and settings link.
- **D-03:** Dashboard replaces the current placeholder Dashboard.vue and becomes the default landing page after login at `/dashboard`. This is the system's core value — operators land directly on the command center.
- **D-04:** Status bar at the bottom of the viewport, below all three panels.

### Camera Marker Interactions
- **D-05:** Clicking a camera marker opens a Mapbox popup with camera summary: camera name, online/offline status, last seen time, and recent recognition count. Optional link to full camera detail page.
- **D-06:** Recognition event pulse animation — a red circle expands outward from the camera marker and fades to transparent over ~3 seconds. Classic radar/sonar expanding ring effect. Multiple events can produce overlapping rings.
- **D-07:** Marker color reflects online/offline status only — green for online, gray for offline (per DASH-02). The pulse animation handles event severity visually. No severity tinting on the marker itself.

### Status Bar & System Health
- **D-08:** Dark/light map style toggle in the top navigation bar (sun/moon icon), near the right side. Switches both the Mapbox map style AND the app theme together, consistent with the existing appearance toggle pattern.
- **D-09:** Connection loss surfacing — status bar shows green/red dots for MQTT and Reverb. When disconnected, a subtle amber banner appears below the top bar: "Real-time connection lost. Alerts may be delayed." Auto-dismisses on reconnect.
- **D-10:** Status bar displays three indicators per DASH-05: MQTT connection (green/red dot + label), Reverb WebSocket (green/red dot + label), queue depth (number of pending jobs). Minimal and functional.

### Left Rail Content & Stats
- **D-11:** Compact camera list rows — each camera as a compact row: status dot (green/gray), camera name, recognition count badge. Clicking a camera pans the map to that marker and opens its popup. Similar density to app sidebar nav items.
- **D-12:** "Today" statistics panel with 4 key metrics in a 2x2 grid: total recognitions today, critical events today, warnings today, total enrolled personnel. Quick pulse check for the operator.
- **D-13:** Clicking a camera in the left rail also filters the right alert feed to show only that camera's events. Click again (or click "All") to clear the filter. Integrated camera-focused view.

### Claude's Discretion
- Mapbox popup HTML structure and styling
- Expanding ring animation implementation (CSS keyframes vs Mapbox GL layers vs canvas)
- Status bar component structure and health check polling mechanism
- MQTT connection status detection approach (likely via Echo connection state)
- Queue depth API endpoint design
- "Today" stats query optimization (eager load vs separate endpoint)
- Left rail scroll behavior when many cameras exist
- Map resize handling when panels are toggled

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — Camera GPS coordinates, device_id for marker identification

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 6 requirements: DASH-01 through DASH-08
- `.planning/ROADMAP.md` — Phase 6 success criteria (5 criteria that must be TRUE)

### Configuration
- `config/hds.php` — Mapbox token, dark_style, light_style URLs, camera_offline_threshold

### Prior Phase Context
- `.planning/phases/02-camera-management-liveness/02-CONTEXT.md` — MapboxMap component (D-03), camera model with GPS (D-01), CameraStatusDot pattern (D-06)
- `.planning/phases/05-recognition-alerting/05-CONTEXT.md` — AlertFeedItem compact rows (D-01), severity coloring (D-11), Echo real-time pattern, alert feed cap at 50 (D-03)

### Existing Code (patterns to follow and extend)
- `resources/js/components/MapboxMap.vue` — Existing map component with dark/light styles, marker placement, interactive mode
- `resources/js/pages/alerts/Index.vue` — Alert feed with Echo listener, filter pills, severity filtering
- `resources/js/components/AlertFeedItem.vue` — Compact alert row component for reuse in dashboard
- `resources/js/components/AlertDetailModal.vue` — Detail modal for alert clicks
- `resources/js/components/CameraStatusDot.vue` — Online/offline indicator dot
- `resources/js/composables/useAlertSound.ts` — Audio composable for critical event chimes
- `resources/js/composables/useAppearance.ts` — Existing appearance toggle (dark/light/system)
- `resources/js/pages/cameras/Index.vue` — Echo listener pattern for CameraStatusChanged
- `resources/js/pages/Dashboard.vue` — Current placeholder to be replaced
- `routes/channels.php` — fras.alerts channel authorization

### Codebase Maps
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns
- `.planning/codebase/STRUCTURE.md` — Where to add new code
- `.planning/codebase/ARCHITECTURE.md` — Overall application architecture

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `MapboxMap.vue`: Full map component with marker support — extend for multi-marker and pulse animation
- `AlertFeedItem.vue` + `AlertDetailModal.vue`: Complete alert feed row and modal — embed directly in dashboard right panel
- `SeverityBadge.vue`: Severity-colored badge for alert display
- `CameraStatusDot.vue`: Online/offline indicator for camera list
- `useAlertSound.ts`: Audio composable already handles critical event chimes
- `useAppearance.ts`: Appearance toggle composable — extend to also switch Mapbox style
- `useEcho()` from `@laravel/echo-vue`: Real-time subscription pattern used in cameras and alerts

### Established Patterns
- MapboxMap uses plain `let` (not `ref`) for map/marker instances to avoid Vue 3 Proxy breaking mapbox-gl internals
- Echo listeners create local reactive arrays from initial props, then mutate on WebSocket events
- Inertia `defineOptions({ layout: ... })` for layout assignment in app.ts
- Date-partitioned image storage with auth-protected serving routes

### Integration Points
- `resources/js/pages/Dashboard.vue` — Replace placeholder with command center
- `resources/js/app.ts` — Add new DashboardLayout to the layout resolver (Dashboard page uses DashboardLayout, not AppLayout)
- `routes/web.php` — Dashboard route already exists at `/dashboard`, just needs the controller to pass camera and stats data
- `app/Http/Controllers/` — New DashboardController for initial page props (cameras, stats, recent events)

</code_context>

<specifics>
## Specific Ideas

- Command center should feel like a security operations center — dense, professional, always-on
- The three-panel layout is the core interaction model: operators watch the map center, scan alerts on the right, and reference camera status on the left
- Expanding ring pulse animation should be visible from across the room — this is how operators notice events without staring at the alert feed
- Camera-to-alert-feed filtering creates an integrated workflow: click a camera, see only its events, understand its activity at a glance

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 06-dashboard-map*
*Context gathered: 2026-04-11*

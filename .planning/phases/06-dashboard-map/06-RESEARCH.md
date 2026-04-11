# Phase 6: Dashboard & Map - Research

**Researched:** 2026-04-11
**Domain:** Full-viewport dashboard with Mapbox GL JS map, real-time WebSocket updates, three-panel layout
**Confidence:** HIGH

## Summary

Phase 6 builds the operator command center -- the core value proposition of FRAS. This is primarily a frontend-intensive phase that creates a new full-viewport layout with three panels (camera rail, Mapbox map, alert feed) and a status bar, wired to existing real-time broadcast infrastructure (Laravel Echo/Reverb). The backend work is limited to a new DashboardController that aggregates camera data, today's statistics, and recent events, plus a small API endpoint for queue depth polling.

The project already has all major building blocks in place: MapboxMap.vue (single-marker, extends to multi-marker), AlertFeedItem.vue + AlertDetailModal.vue (reusable in right panel), CameraStatusDot.vue (reusable in left rail), useAlertSound.ts (critical event audio), useAppearance.ts (theme toggle -- extend to also switch Mapbox style), and the Echo real-time subscription pattern (used in cameras/Index.vue and alerts/Index.vue). The existing `fras.alerts` broadcast channel already carries both RecognitionAlert and CameraStatusChanged events.

The key technical challenges are: (1) creating a DashboardLayout that bypasses the standard AppSidebarLayout, (2) building the DashboardMap component with custom HTML markers, popups, and CSS pulse ring animations on recognition events, (3) detecting Reverb/Echo connection status using the `useConnectionStatus` composable from `@laravel/echo-vue`, and (4) handling Mapbox style switching (dark/light) without losing markers.

**Primary recommendation:** Build from existing components outward. DashboardMap.vue is a new component (NOT extending MapboxMap.vue, which is a single-marker picker). Reuse AlertFeedItem, AlertDetailModal, CameraStatusDot, and useAlertSound directly. Create composables for dashboard-specific state (useDashboardMap, useQueueDepth).

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Fixed-width panels -- left rail ~280px, right alert feed ~360px, center map fills remaining space. Panels can be toggled open/closed but not resized by dragging.
- **D-02:** Full-viewport dedicated layout -- dashboard gets its own layout WITHOUT the standard AppLayout sidebar. Maximum screen real estate. Navigation to other pages via a minimal top bar with logo and settings link.
- **D-03:** Dashboard replaces the current placeholder Dashboard.vue and becomes the default landing page after login at `/dashboard`. This is the system's core value -- operators land directly on the command center.
- **D-04:** Status bar at the bottom of the viewport, below all three panels.
- **D-05:** Clicking a camera marker opens a Mapbox popup with camera summary: camera name, online/offline status, last seen time, and recent recognition count. Optional link to full camera detail page.
- **D-06:** Recognition event pulse animation -- a red circle expands outward from the camera marker and fades to transparent over ~3 seconds. Classic radar/sonar expanding ring effect. Multiple events can produce overlapping rings.
- **D-07:** Marker color reflects online/offline status only -- green for online, gray for offline (per DASH-02). The pulse animation handles event severity visually. No severity tinting on the marker itself.
- **D-08:** Dark/light map style toggle in the top navigation bar (sun/moon icon), near the right side. Switches both the Mapbox map style AND the app theme together, consistent with the existing appearance toggle pattern.
- **D-09:** Connection loss surfacing -- status bar shows green/red dots for MQTT and Reverb. When disconnected, a subtle amber banner appears below the top bar: "Real-time connection lost. Alerts may be delayed." Auto-dismisses on reconnect.
- **D-10:** Status bar displays three indicators per DASH-05: MQTT connection (green/red dot + label), Reverb WebSocket (green/red dot + label), queue depth (number of pending jobs). Minimal and functional.
- **D-11:** Compact camera list rows -- each camera as a compact row: status dot (green/gray), camera name, recognition count badge. Clicking a camera pans the map to that marker and opens its popup. Similar density to app sidebar nav items.
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

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DASH-01 | Dashboard displays all cameras as markers on a Mapbox GL JS map with GPS positioning | DashboardMap component with custom HTML markers positioned by camera latitude/longitude. mapbox-gl v3.21.0 already installed. |
| DASH-02 | Camera markers colored by status: green online, gray offline | Custom HTML marker elements with conditional CSS classes (emerald-500 / neutral-400), matching CameraStatusDot pattern. Real-time updates via CameraStatusChanged broadcast. |
| DASH-03 | Recognition event pulse animation ~3 seconds | CSS @keyframes on absolutely-positioned div appended to marker HTML container. Element self-removes after animationend event. |
| DASH-04 | Three-panel layout: camera list rail, map, alert feed | DashboardLayout.vue bypasses AppSidebarLayout. CSS flexbox with fixed-width side panels, flex-1 center. Panel toggle via CSS transition. |
| DASH-05 | Status bar: MQTT, Reverb, queue depth | useConnectionStatus() from @laravel/echo-vue for Reverb status. MQTT status inferred from same (both use Reverb). Queue depth via polling JSON endpoint querying jobs table count. |
| DASH-06 | Map dark/light style toggle | Extend useAppearance to call map.setStyle(). Re-add markers after style.load event since setStyle removes custom layers. |
| DASH-07 | Left rail: camera list with online/offline indicators and recognition counts | CameraRail component with CameraRailItem rows. Recognition counts computed from events prop or served from DashboardController. |
| DASH-08 | Left rail "Today" statistics panel | TodayStats component receiving aggregated counts from DashboardController. Four metrics in 2x2 grid. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| mapbox-gl | 3.21.0 | Map rendering, markers, popups | Already installed in project. Custom HTML markers for full styling control. [VERIFIED: npm ls] |
| @laravel/echo-vue | 2.3.4 | Real-time WebSocket composables | Already installed. useEcho for events, useConnectionStatus for Reverb health. [VERIFIED: npm ls] |
| pusher-js | 8.5.0 | WebSocket transport for Echo | Already installed. Connection state accessible via Echo connector. [VERIFIED: npm ls] |
| vue | 3.5.13 | UI framework | Project standard. [VERIFIED: package.json] |
| @inertiajs/vue3 | ^3.0.0 | SPA bridge | Project standard. usePage, Head, Link, usePoll for queue depth polling. [VERIFIED: package.json] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| lucide-vue-next | ^0.468.0 | Icons | Sun, Moon, PanelLeft, PanelRight, Camera, Settings, ShieldAlert icons. Already installed. [VERIFIED: package.json] |
| reka-ui | ^2.6.1 | Headless UI primitives | DropdownMenu for user menu in top nav. Already installed. [VERIFIED: package.json] |
| vue-sonner | ^2.0.0 | Toast notifications | Flash toast in DashboardLayout. Already installed. [VERIFIED: package.json] |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom HTML markers | Mapbox GeoJSON source + circle layer | GeoJSON layers get removed on setStyle(), harder to attach DOM-based pulse animation. HTML markers persist across style changes. |
| CSS keyframe pulse | Canvas-based animated icon (StyleImageInterface) | Canvas requires per-frame rendering, more complex. CSS keyframes are simpler, performant, and match the UI-SPEC exactly. |
| usePoll for queue depth | Server-Sent Events or WebSocket channel | Over-engineering for a single metric. Polling every 15-30s is sufficient for queue depth display. |

**Installation:**
```bash
# No new packages needed -- all dependencies already installed
```

**Version verification:** All versions confirmed via `npm ls` against installed packages. No new installations required. [VERIFIED: npm ls]

## Architecture Patterns

### Recommended Project Structure
```
resources/js/
  layouts/
    DashboardLayout.vue          # New full-viewport layout
  components/
    DashboardTopNav.vue          # Minimal top nav bar
    DashboardMap.vue             # Multi-marker map with popups and pulse
    DashboardAlertFeed.vue       # Right panel wrapping AlertFeedItem
    CameraRail.vue               # Left rail: stats + camera list
    CameraRailItem.vue           # Single camera row
    TodayStats.vue               # 2x2 statistics grid
    StatusBar.vue                # Bottom status bar
    ConnectionBanner.vue         # Amber disconnection warning
    PulseRing.vue                # Expanding ring animation element
  composables/
    useDashboardMap.ts           # Map instance management, marker CRUD, pulse triggers
    useQueueDepth.ts             # Polling composable for queue depth endpoint
  pages/
    Dashboard.vue                # Replaced -- full command center page
app/Http/Controllers/
  DashboardController.php        # Serves camera, stats, and recent events data
routes/
  web.php                        # Update dashboard route from inertia to controller
```

### Pattern 1: DashboardLayout Bypasses AppSidebarLayout
**What:** Dashboard uses its own layout without the standard sidebar navigation. The layout resolver in app.ts needs a new case.
**When to use:** When the Dashboard page is loaded.
**Example:**
```typescript
// Source: resources/js/app.ts (existing pattern, extended)
layout: (name) => {
    switch (true) {
        case name === 'Welcome':
            return null;
        case name === 'Dashboard':
            return DashboardLayout;  // NEW: dedicated full-viewport layout
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;
    }
},
```
[VERIFIED: app.ts line 15-25, existing layout resolver pattern]

### Pattern 2: Custom HTML Markers with Mapbox GL JS
**What:** Use the `element` option of `mapboxgl.Marker` to provide custom-styled DOM elements instead of default markers.
**When to use:** For camera markers that need CSS-controlled colors, borders, and child elements (pulse rings).
**Example:**
```typescript
// Source: Mapbox GL JS docs - Custom markers
// CRITICAL: Do NOT use ref() for map/marker instances (Vue 3 Proxy breaks mapbox-gl)
const markerElement = document.createElement('div');
markerElement.className = 'camera-marker';
markerElement.style.width = '12px';
markerElement.style.height = '12px';
markerElement.style.borderRadius = '50%';
markerElement.style.backgroundColor = camera.is_online ? '#10b981' : '#a3a3a3';
markerElement.style.border = '2px solid white';

const marker = new mapboxgl.Marker({ element: markerElement })
    .setLngLat([camera.longitude, camera.latitude])
    .addTo(map);
```
[CITED: https://docs.mapbox.com/mapbox-gl-js/api/markers/]

### Pattern 3: Echo Real-Time Pattern (Existing)
**What:** Local reactive array initialized from props, mutated on WebSocket events. Existing pattern from alerts/Index.vue and cameras/Index.vue.
**When to use:** For both the alert feed and camera status updates on the dashboard.
**Example:**
```typescript
// Source: resources/js/pages/alerts/Index.vue (existing pattern)
const alerts = ref<RecognitionEvent[]>([...props.events]);

useEcho('fras.alerts', '.RecognitionAlert', (payload: RecognitionAlertPayload) => {
    const event = mapPayloadToEvent(payload);
    alerts.value.unshift(event);
    if (alerts.value.length > 50) {
        alerts.value = alerts.value.slice(0, 50);
    }
});
```
[VERIFIED: resources/js/pages/alerts/Index.vue lines 130-155]

### Pattern 4: Connection Status Monitoring
**What:** Use `useConnectionStatus()` from `@laravel/echo-vue` to reactively track WebSocket connection state.
**When to use:** For the status bar Reverb indicator and connection loss banner.
**Example:**
```typescript
// Source: @laravel/echo-vue types
import { useConnectionStatus } from '@laravel/echo-vue';
import type { ConnectionStatus } from '@laravel/echo-vue';

const connectionStatus = useConnectionStatus();
// Values: "connected" | "disconnected" | "connecting" | "reconnecting" | "failed"
const isConnected = computed(() => connectionStatus.value === 'connected');
```
[VERIFIED: node_modules/laravel-echo/dist/echo.d.ts line 116, node_modules/@laravel/echo-vue/dist/index.d.ts]

### Pattern 5: Mapbox Style Switching with Marker Preservation
**What:** When toggling dark/light map style, HTML markers (DOM-based) persist across `map.setStyle()` calls. GeoJSON source layers do NOT persist. This is a key reason to use HTML markers.
**When to use:** When implementing the theme toggle that switches both app theme and Mapbox style.
**Example:**
```typescript
// Source: Mapbox GL JS docs - setStyle behavior
function switchMapStyle(styleUrl: string): void {
    if (!map) return;
    map.setStyle(styleUrl);
    // HTML markers persist automatically -- they are DOM elements, not style layers
    // If using GeoJSON sources, they would need to be re-added after 'style.load'
}
```
[CITED: https://docs.mapbox.com/mapbox-gl-js/example/setstyle/, https://github.com/mapbox/mapbox-gl-js/issues/8660]

### Anti-Patterns to Avoid
- **Using ref() for map/marker instances:** Vue 3 Proxy breaks mapbox-gl internals. Use plain `let` variables. [VERIFIED: MapboxMap.vue line 29 comment, Phase 2 decision]
- **Building a new map component by extending MapboxMap.vue:** MapboxMap.vue is a single-marker coordinate picker. DashboardMap needs multi-marker management, popups, pulse animations -- build fresh, but follow the same patterns (plain let, onMounted init, onUnmounted cleanup).
- **Using GeoJSON layers instead of HTML markers:** GeoJSON layers are removed on `setStyle()` (dark/light toggle), requiring complex re-add logic. HTML markers are DOM elements and persist across style changes.
- **Polling queue depth too frequently:** Every 15-30 seconds is sufficient. Queue depth is informational, not critical-path.
- **Creating a separate MQTT status check:** MQTT status is not directly observable from the browser. Both MQTT messages and recognition events flow through Reverb. The Reverb connection status effectively represents the real-time pipeline health. Show Reverb status for both indicators, or add a server-side MQTT health check endpoint if granular status is needed.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| WebSocket connection status | Custom ping/pong or heartbeat tracking | `useConnectionStatus()` from `@laravel/echo-vue` | Built-in reactive composable that tracks Pusher connection state machine (connected/disconnected/connecting/reconnecting/failed) [VERIFIED: echo-vue types] |
| Theme toggle | New dark/light toggle system | Extend existing `useAppearance()` composable | Already handles localStorage, cookie, CSS class toggling. Just needs Mapbox style callback. [VERIFIED: useAppearance.ts] |
| Alert feed real-time | New WebSocket subscription system | Existing Echo pattern from alerts/Index.vue | Pattern is battle-tested, uses same `fras.alerts` channel. Copy and adapt. [VERIFIED: alerts/Index.vue] |
| Camera status real-time | New status polling | Existing CameraStatusChanged broadcast event | Already broadcast on state transitions, already consumed in cameras/Index.vue. [VERIFIED: cameras/Index.vue, CameraStatusChanged.php] |
| Map marker popups | Custom tooltip component | `mapboxgl.Popup` with `setDOMContent()` | Native Mapbox popup with positioning, z-index, close-on-click-outside. [CITED: Mapbox GL JS API docs] |
| Periodic data refresh | setInterval with fetch | `usePoll` from `@inertiajs/vue3` | Handles cleanup on unmount, throttles when tab inactive. [VERIFIED: inertia-vue-development skill] |

**Key insight:** This phase is 80% composition of existing assets. The only truly new pieces are the DashboardLayout, DashboardMap (multi-marker + pulse animation), and the DashboardController backend aggregation.

## Common Pitfalls

### Pitfall 1: Vue 3 Proxy Breaking Mapbox GL Internals
**What goes wrong:** Storing `mapboxgl.Map` or `mapboxgl.Marker` instances in `ref()` or `reactive()` causes Vue's Proxy to intercept internal mapbox-gl property access, leading to silent failures or errors.
**Why it happens:** Vue 3's reactivity system wraps objects in Proxy, which interferes with mapbox-gl's internal state management.
**How to avoid:** Use plain `let` variables for all mapbox-gl instances. Document this in code comments.
**Warning signs:** Map renders but markers don't appear, or map becomes unresponsive after the first interaction.
[VERIFIED: MapboxMap.vue line 29, Phase 2 decision in STATE.md]

### Pitfall 2: Map Not Resizing When Panels Toggle
**What goes wrong:** When the left rail or right alert feed panel is toggled, the map doesn't fill the available space -- it stays at its previous dimensions.
**Why it happens:** Mapbox GL JS calculates canvas dimensions on initialization. Container size changes require explicit `map.resize()`.
**How to avoid:** Call `map.resize()` after panel toggle animation completes (~200ms per UI-SPEC). Use `setTimeout` or listen for CSS `transitionend` event.
**Warning signs:** Gray area appears where the map should expand into after hiding a panel.
[CITED: Mapbox GL JS docs - map.resize()]

### Pitfall 3: Pulse Ring Animation Z-Index and Positioning
**What goes wrong:** Expanding ring appears behind the map tiles or at the wrong position relative to the marker.
**Why it happens:** The pulse ring div needs to be absolutely positioned within the marker container and centered. Mapbox markers use CSS transform for positioning, so the ring must be a child of the marker element, not appended elsewhere.
**How to avoid:** Append pulse ring divs as children of the marker's HTML element. Use `position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%)` for centering. Set appropriate z-index.
**Warning signs:** Ring appears at map corner, or doesn't move when map pans.

### Pitfall 4: Style Toggle Flicker
**What goes wrong:** When switching between dark/light Mapbox styles, there's a brief white flash or the map goes blank during style load.
**Why it happens:** `map.setStyle()` triggers a full style reload from Mapbox servers.
**How to avoid:** HTML markers persist through style changes (they're DOM elements, not style layers), so no marker re-creation needed. For the map itself, consider keeping the map container opaque during the transition. The brief loading is inherent to Mapbox and acceptable.
**Warning signs:** Map goes blank for 200-500ms during theme toggle.

### Pitfall 5: MQTT Status Cannot Be Directly Observed from Browser
**What goes wrong:** Attempting to show separate MQTT and Reverb connection status when only Reverb is directly observable from the browser.
**Why it happens:** The MQTT listener runs as a PHP long-running process on the server. The browser connects to Reverb (WebSocket), not MQTT directly. MQTT status requires server-side health checking.
**How to avoid:** For the MQTT indicator, either (a) use a server-side endpoint that checks if the MQTT process is running / last heartbeat received, or (b) infer MQTT health from whether camera heartbeats are being received (if cameras are online and heartbeats are flowing, MQTT is working). Option (b) is pragmatic for v1.
**Warning signs:** MQTT indicator always shows "connected" because nothing is actually checking MQTT status.

### Pitfall 6: Dashboard Route Change Breaks Existing Tests
**What goes wrong:** Changing the dashboard route from `Route::inertia()` to a controller route breaks DashboardTest.php assertions.
**Why it happens:** The test expects a simple Inertia page render; a controller may return different props or component name.
**How to avoid:** Update DashboardTest.php to assert the new component name and expected props structure.
**Warning signs:** Test failures in DashboardTest.php after route change.

## Code Examples

### DashboardController - Aggregating Dashboard Data
```php
// Source: Pattern from AlertController.php + Camera model queries
class DashboardController extends Controller
{
    public function index(): Response
    {
        $cameras = Camera::select('id', 'device_id', 'name', 'location_label', 'latitude', 'longitude', 'is_online', 'last_seen_at')
            ->withCount(['recognitionEvents as today_recognition_count' => function ($query) {
                $query->whereDate('captured_at', today());
            }])
            ->get();

        $todayStats = [
            'recognitions' => RecognitionEvent::whereDate('captured_at', today())->where('is_real_time', true)->count(),
            'critical' => RecognitionEvent::whereDate('captured_at', today())->where('severity', AlertSeverity::Critical)->where('is_real_time', true)->count(),
            'warnings' => RecognitionEvent::whereDate('captured_at', today())->where('severity', AlertSeverity::Warning)->where('is_real_time', true)->count(),
            'enrolled' => Personnel::count(),
        ];

        $recentEvents = RecognitionEvent::with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path'])
            ->whereIn('severity', [AlertSeverity::Critical, AlertSeverity::Warning, AlertSeverity::Info])
            ->where('is_real_time', true)
            ->latest('captured_at')
            ->limit(50)
            ->get();

        return Inertia::render('Dashboard', [
            'cameras' => $cameras,
            'todayStats' => $todayStats,
            'recentEvents' => $recentEvents,
            'mapbox' => [
                'token' => config('hds.mapbox.token'),
                'darkStyle' => config('hds.mapbox.dark_style'),
                'lightStyle' => config('hds.mapbox.light_style'),
            ],
        ]);
    }
}
```
[VERIFIED: AlertController.php pattern, Camera model structure, RecognitionEvent model]

### Queue Depth Endpoint
```php
// Source: jobs table schema verified via tinker
Route::get('api/queue-depth', function () {
    return response()->json([
        'depth' => DB::table('jobs')->count(),
    ]);
})->middleware(['auth']);
```
[VERIFIED: jobs table has columns: id, queue, payload, attempts, reserved_at, available_at, created_at]

### Pulse Ring CSS Animation
```css
/* Source: UI-SPEC contract */
@keyframes pulse-ring {
    0% {
        width: 12px;
        height: 12px;
        opacity: 0.6;
    }
    100% {
        width: 48px;
        height: 48px;
        opacity: 0;
    }
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    background-color: rgba(239, 68, 68, 0.6);
    animation: pulse-ring 3s ease-out forwards;
    pointer-events: none;
}
```
[CITED: 06-UI-SPEC.md pulse ring animation section]

### DashboardLayout Structure
```vue
<!-- Source: Pattern from AppSidebarLayout.vue, adapted for full-viewport -->
<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="flex h-screen w-screen flex-col overflow-hidden">
        <DashboardTopNav />
        <ConnectionBanner />
        <div class="flex flex-1 overflow-hidden">
            <CameraRail />
            <main class="flex-1">
                <slot />
            </main>
            <DashboardAlertFeed />
        </div>
        <StatusBar />
        <Toaster />
    </div>
</template>
```
[VERIFIED: AppSidebarLayout.vue structure, UI-SPEC layout contract]

### useConnectionStatus for Reverb Health
```typescript
// Source: @laravel/echo-vue exported composable
import { useConnectionStatus } from '@laravel/echo-vue';

const status = useConnectionStatus();
// Returns Ref<"connected" | "disconnected" | "connecting" | "reconnecting" | "failed">

const isReverbConnected = computed(() => status.value === 'connected');
```
[VERIFIED: @laravel/echo-vue/dist/index.d.ts, laravel-echo/dist/echo.d.ts line 116]

### Fitting Map Bounds to All Cameras
```typescript
// Source: Mapbox GL JS docs
import mapboxgl from 'mapbox-gl';

function fitCameraBounds(map: mapboxgl.Map, cameras: Camera[]): void {
    if (cameras.length === 0) return;
    
    const bounds = new mapboxgl.LngLatBounds();
    cameras.forEach(camera => {
        bounds.extend([camera.longitude, camera.latitude]);
    });
    
    map.fitBounds(bounds, { padding: 48 });
}
```
[CITED: Mapbox GL JS docs - LngLatBounds, fitBounds]

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Custom WebSocket ping/pong for connection status | `useConnectionStatus()` composable from `@laravel/echo-vue` | laravel-echo v2.x / echo-vue v2.x | No need to manually track Pusher connection events |
| GeoJSON source layers for map markers | Custom HTML markers via `element` option | Always available in mapbox-gl, but HTML markers are better for this use case | Persist across style changes, support DOM-based animations |
| Manual Echo connector.pusher.connection.bind() | `useConnectionStatus()` composable | @laravel/echo-vue 2.x | Clean reactive API instead of manual event binding |

**Deprecated/outdated:**
- `Inertia::lazy()` / `LazyProp` removed in Inertia v3 -- use `Inertia::optional()` instead [VERIFIED: CLAUDE.md Inertia v3 section]
- `router.cancel()` replaced by `router.cancelAll()` in Inertia v3 [VERIFIED: CLAUDE.md]

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | HTML markers persist across `map.setStyle()` calls because they are DOM elements, not style layers | Architecture Patterns - Pattern 5 | If markers disappear on style change, will need marker re-creation on `style.load` event. Low risk -- this is standard Mapbox behavior. |
| A2 | MQTT status can be inferred from Reverb connection + camera heartbeat activity rather than a dedicated MQTT health endpoint | Common Pitfalls - Pitfall 5 | If operators need granular MQTT-vs-Reverb status, a server-side health check endpoint would be needed. Medium risk for v1 acceptability. |
| A3 | Queue depth polling every 15-30 seconds via `usePoll` is sufficient for operator awareness | Architecture Patterns | If real-time queue depth is needed, would require a broadcast event. Very low risk. |
| A4 | Camera `recognitionEvents` relationship can be added to Camera model for `withCount` query | Code Examples - DashboardController | Need to verify or add `hasMany(RecognitionEvent::class)` on Camera model. Low risk -- standard Eloquent pattern. |

## Open Questions

1. **Camera HasMany RecognitionEvents Relationship**
   - What we know: RecognitionEvent has `camera_id` foreign key. Camera model currently has `enrollments()` and `enrolledPersonnel()` relationships but not `recognitionEvents()`.
   - What's unclear: Whether to add the relationship to the model or use a raw query for `withCount`.
   - Recommendation: Add `recognitionEvents(): HasMany` to Camera model -- it's a natural relationship and useful beyond this phase.

2. **MQTT Status Granularity**
   - What we know: Reverb connection status is directly observable via `useConnectionStatus()`. MQTT runs server-side.
   - What's unclear: Whether operators need to distinguish "MQTT is down but Reverb is up" from "both down."
   - Recommendation: For v1, show Reverb status for both indicators. If the MQTT listener process dies, camera heartbeats stop flowing, cameras eventually show offline, and that itself alerts operators. Add a dedicated MQTT health check in a future iteration if needed.

3. **Today Stats Real-Time Updates**
   - What we know: Stats are served on page load. Recognition events arrive via WebSocket.
   - What's unclear: Whether to update stats client-side (increment counters on each RecognitionAlert) or use `usePoll` to refresh.
   - Recommendation: Increment client-side on each RecognitionAlert event for instant feedback. The stats are simple counters. Optionally use `usePoll` at 60s intervals to reconcile with server truth.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 with pest-plugin-laravel v4.1 |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact --filter=DashboardController` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DASH-01 | Dashboard renders with cameras as map data | Feature | `php artisan test --compact --filter=DashboardControllerTest` | Wave 0 |
| DASH-02 | Cameras include is_online status in response | Feature | `php artisan test --compact --filter=DashboardControllerTest` | Wave 0 |
| DASH-03 | Pulse animation on recognition event | Manual | Visual verification in browser | N/A (frontend CSS) |
| DASH-04 | Three-panel layout renders | Feature | `php artisan test --compact --filter=DashboardControllerTest` (component assertion) | Wave 0 |
| DASH-05 | Queue depth endpoint returns count | Feature | `php artisan test --compact --filter=QueueDepthTest` | Wave 0 |
| DASH-06 | Mapbox config passed to frontend | Feature | `php artisan test --compact --filter=DashboardControllerTest` | Wave 0 |
| DASH-07 | Cameras include today recognition count | Feature | `php artisan test --compact --filter=DashboardControllerTest` | Wave 0 |
| DASH-08 | Today stats aggregation correct | Feature | `php artisan test --compact --filter=DashboardControllerTest` | Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --compact --filter=Dashboard`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/DashboardControllerTest.php` -- covers DASH-01, DASH-02, DASH-04, DASH-06, DASH-07, DASH-08 (replaces simple DashboardTest.php)
- [ ] `tests/Feature/QueueDepthTest.php` -- covers DASH-05
- [ ] Camera model `recognitionEvents()` relationship (needed for withCount)

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | All dashboard routes behind `auth` + `verified` middleware (existing) |
| V3 Session Management | no | No session changes in this phase |
| V4 Access Control | yes | Queue depth endpoint must require auth middleware. Dashboard controller behind existing auth group. |
| V5 Input Validation | no | Dashboard is read-only. No user input beyond URL parameters (camera ID for filtering is client-side). |
| V6 Cryptography | no | No crypto operations in this phase |

### Known Threat Patterns for This Phase

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Unauthenticated queue depth access | Information Disclosure | Auth middleware on queue-depth endpoint |
| Mapbox token exposure in page source | Information Disclosure | Token is already client-side (required by Mapbox GL JS). Use Mapbox URL restrictions to scope token to allowed domains. |
| XSS via Mapbox popup content | Tampering | Use `setDOMContent()` (safe DOM nodes) instead of `setHTML()` with untrusted content |

## Project Constraints (from CLAUDE.md)

- **Pint formatting:** Run `vendor/bin/pint --dirty --format agent` before finalizing any PHP changes
- **Test enforcement:** Every change must be programmatically tested. Write or update tests, then run to verify.
- **Wayfinder:** Use Wayfinder-generated route functions instead of hardcoded URLs. After adding DashboardController, run `php artisan wayfinder:generate` (or Vite plugin handles it).
- **search-docs:** Use `search-docs` Boost tool before making code changes for version-specific guidance.
- **Single root element:** Vue components must have a single root element.
- **`<script setup lang="ts">`:** All Vue components use this pattern exclusively.
- **defineOptions for layout metadata:** Use `defineOptions({ layout: { ... } })` for breadcrumbs (though Dashboard uses DashboardLayout which may not use breadcrumbs).
- **Inertia::flash for toasts:** Use `Inertia::flash('toast', ...)` pattern for success messages.
- **artisan make commands:** Use `php artisan make:controller`, `php artisan make:test --pest` etc. for file scaffolding.
- **Herd serving:** App served at `https://fras.test` via Laravel Herd. Never run serve commands.

## Sources

### Primary (HIGH confidence)
- `resources/js/components/MapboxMap.vue` -- Existing map patterns (plain let, onMounted, onUnmounted)
- `resources/js/pages/alerts/Index.vue` -- Echo real-time subscription pattern, alert feed management
- `resources/js/pages/cameras/Index.vue` -- CameraStatusChanged Echo listener pattern
- `resources/js/composables/useAppearance.ts` -- Theme toggle composable to extend
- `resources/js/app.ts` -- Layout resolver pattern
- `node_modules/@laravel/echo-vue/dist/index.d.ts` -- useConnectionStatus, useEcho API
- `node_modules/laravel-echo/dist/echo.d.ts` -- ConnectionStatus type definition
- `app/Http/Controllers/AlertController.php` -- Query patterns for RecognitionEvent
- `app/Models/Camera.php` -- Camera model structure and relationships
- `app/Events/RecognitionAlert.php` -- Broadcast event payload structure
- `app/Events/CameraStatusChanged.php` -- Camera status broadcast
- `config/hds.php` -- Mapbox configuration keys
- `.planning/phases/06-dashboard-map/06-UI-SPEC.md` -- Complete visual and interaction contract
- `.planning/phases/06-dashboard-map/06-CONTEXT.md` -- User decisions D-01 through D-13

### Secondary (MEDIUM confidence)
- [Mapbox GL JS Markers API](https://docs.mapbox.com/mapbox-gl-js/api/markers/) -- Marker element option, Popup API
- [Mapbox GL JS setStyle example](https://docs.mapbox.com/mapbox-gl-js/example/setstyle/) -- Style switching behavior
- [Mapbox GL JS custom markers tutorial](https://docs.mapbox.com/help/tutorials/custom-markers-gl-js/) -- Custom HTML marker pattern
- [Mapbox GL JS animate marker on appearance](https://docs.mapbox.com/mapbox-gl-js/example/animate-marker-on-appearance/) -- CSS animation on markers (nested div pattern)
- [Pusher connection docs](https://pusher.com/docs/channels/using_channels/connection/) -- Connection state machine

### Tertiary (LOW confidence)
- [Mapbox setStyle removes layers issue #8660](https://github.com/mapbox/mapbox-gl-js/issues/8660) -- Confirms layers removed on style change (not markers)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already installed and version-verified
- Architecture: HIGH -- patterns directly observed in existing codebase, components identified for reuse
- Pitfalls: HIGH -- Vue 3 Proxy issue verified in existing code comments, style switching behavior well-documented
- Real-time infrastructure: HIGH -- Echo patterns proven in Phase 5, useConnectionStatus verified in type definitions

**Research date:** 2026-04-11
**Valid until:** 2026-05-11 (stable -- all dependencies already locked in project)

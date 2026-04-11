---
phase: 06-dashboard-map
reviewed: 2026-04-11T00:00:00Z
depth: standard
files_reviewed: 17
files_reviewed_list:
  - app/Http/Controllers/DashboardController.php
  - app/Models/Camera.php
  - resources/css/app.css
  - resources/js/app.ts
  - resources/js/components/CameraRail.vue
  - resources/js/components/CameraRailItem.vue
  - resources/js/components/ConnectionBanner.vue
  - resources/js/components/DashboardAlertFeed.vue
  - resources/js/components/DashboardMap.vue
  - resources/js/components/DashboardTopNav.vue
  - resources/js/components/StatusBar.vue
  - resources/js/components/TodayStats.vue
  - resources/js/composables/useDashboardMap.ts
  - resources/js/layouts/DashboardLayout.vue
  - resources/js/pages/Dashboard.vue
  - routes/web.php
  - tests/Feature/DashboardControllerTest.php
findings:
  critical: 0
  warning: 3
  info: 2
  total: 5
status: issues_found
---

# Phase 6: Code Review Report

**Reviewed:** 2026-04-11T00:00:00Z
**Depth:** standard
**Files Reviewed:** 17
**Status:** issues_found

## Summary

Reviewed the full dashboard-map feature: Laravel controller, Camera model, CSS animations, Inertia app bootstrap, eight Vue components, the `useDashboardMap` composable, the dashboard layout, the Dashboard page, web routes, and the feature test suite.

Overall the implementation is well-structured. The component decomposition is clean, Wayfinder is used consistently for route generation in templates, and the test suite covers the controller's key behaviours. Three issues need attention before this phase is considered clean.

---

## Warnings

### WR-01: Map stays in loading skeleton forever when container ref is null

**File:** `resources/js/composables/useDashboardMap.ts:258-260`

**Issue:** `initMap()` only sets `hasError.value = true` when `!options.accessToken`. When the container ref is `null` at mount time (e.g., SSR hydration edge-case, component destroyed before mount completes) the function returns early without setting `hasError`, leaving `isLoaded` and `hasError` both `false`. `DashboardMap.vue` shows the `<Skeleton>` only while `!isLoaded && !hasError`, so the loading skeleton is displayed indefinitely with no error fallback shown to the operator.

```typescript
// Current
function initMap(): void {
    if (!options.container.value || !options.accessToken) {
        hasError.value = !options.accessToken;  // null container silently ignored
        return;
    }
    // ...
}

// Fix: treat a missing container as an error too
function initMap(): void {
    if (!options.container.value || !options.accessToken) {
        hasError.value = true;
        return;
    }
    // ...
}
```

---

### WR-02: MQTT and Reverb connection indicators always show the same value

**File:** `resources/js/pages/Dashboard.vue:70`

**Issue:** `isMqttConnected` is assigned the same computed ref as `isReverbConnected`:

```typescript
const isMqttConnected = isReverbConnected;
```

`StatusBar` renders two separate indicators labelled "MQTT" and "Reverb". Using identical reactive values means the MQTT dot is driven entirely by Reverb's WebSocket state, not the MQTT broker connection. An operator seeing a red MQTT dot cannot distinguish a real broker failure from a transient WebSocket blip. More importantly, a live MQTT feed with a dropped WebSocket would show both dots red, hiding the real cause.

**Fix:** Either wire `isMqttConnected` to a separate MQTT liveness signal (e.g., track the last MQTT heartbeat event from the `CameraStatusChanged` channel, or expose a dedicated boolean via a `useMqttStatus` composable), or collapse the two indicators into one "Connection" indicator until independent tracking is implemented. The current state is misleading.

```typescript
// Minimal fix until proper MQTT liveness is tracked:
// Remove the separate MQTT indicator from StatusBar or make it explicit
// that it mirrors the WebSocket:
const isMqttConnected = isReverbConnected; // proxy until real MQTT liveness is wired

// Or track independently using last MQTT activity time:
const lastMqttActivity = ref<number>(Date.now());
useEcho('fras.alerts', '.CameraStatusChanged', () => {
    lastMqttActivity.value = Date.now();
});
const isMqttConnected = computed(
    () => Date.now() - lastMqttActivity.value < 60_000,
);
```

---

### WR-03: Hardcoded URL in queue-depth polling violates Wayfinder convention

**File:** `resources/js/pages/Dashboard.vue:273`

**Issue:** `fetchQueueDepth` uses a hardcoded string `/api/queue-depth` instead of the Wayfinder-generated route function. The project's CLAUDE.md states explicitly: "NEVER hardcode URLs; always use Wayfinder-generated functions." The named route `queue-depth` is registered in `routes/web.php` and Wayfinder will have generated a corresponding typed function.

```typescript
// Current
const res = await fetch('/api/queue-depth');

// Fix: import and use the Wayfinder route function
import { queueDepth as queueDepthRoute } from '@/routes/queue-depth';

const res = await fetch(queueDepthRoute.url());
```

---

## Info

### IN-01: Missing `DB` facade import in Pest test file

**File:** `tests/Feature/DashboardControllerTest.php:150`

**Issue:** `DB::table('jobs')->insert([...])` is called without a `use Illuminate\Support\Facades\DB;` import at the top of the file. The test passes because Laravel auto-imports the `DB` alias globally, but the explicit import is required per PHP strict standards and project convention (all imports must be declared). Other test files in the project declare their imports explicitly.

```php
// Add to the imports block at the top of the file:
use Illuminate\Support\Facades\DB;
```

---

### IN-02: `popup.addTo(map!)` non-null assertion is unreachable

**File:** `resources/js/composables/useDashboardMap.ts:232`

**Issue:** The non-null assertion `map!` inside `flyTo` is unreachable because the outer guard `if (!entry || !map)` already ensures `map` is non-null at that point. This is a minor type-narrowing issue — TypeScript cannot infer the non-null state across the closure boundary — but the `!` operator suppresses a potential future error rather than expressing intent.

```typescript
// Current (line 232)
popup.addTo(map!);

// Fix: assign to a local const to satisfy TypeScript without suppressing:
const currentMap = map;
if (popup && !popup.isOpen()) {
    popup.addTo(currentMap);
}
```

---

_Reviewed: 2026-04-11T00:00:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

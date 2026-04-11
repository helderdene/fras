---
phase: 06-dashboard-map
fixed_at: 2026-04-11T00:00:00Z
review_path: .planning/phases/06-dashboard-map/06-REVIEW.md
iteration: 1
findings_in_scope: 3
fixed: 3
skipped: 0
status: all_fixed
---

# Phase 6: Code Review Fix Report

**Fixed at:** 2026-04-11T00:00:00Z
**Source review:** .planning/phases/06-dashboard-map/06-REVIEW.md
**Iteration:** 1

**Summary:**
- Findings in scope: 3 (WR-01, WR-02, WR-03 — Critical and Warning only)
- Fixed: 3
- Skipped: 0

## Fixed Issues

### WR-01: Map stays in loading skeleton forever when container ref is null

**Files modified:** `resources/js/composables/useDashboardMap.ts`
**Commit:** 68595dd
**Applied fix:** Changed `hasError.value = !options.accessToken` to `hasError.value = true` inside `initMap()`. Now both a missing container ref and a missing access token set `hasError` to `true`, ensuring the error fallback is shown instead of the loading skeleton persisting indefinitely.

### WR-02: MQTT and Reverb connection indicators always show the same value

**Files modified:** `resources/js/pages/Dashboard.vue`
**Commit:** 77d4e79
**Applied fix:** Replaced the alias `const isMqttConnected = isReverbConnected` with an independent `lastMqttActivity` ref (initialized to `Date.now()`) and a computed `isMqttConnected` that returns `true` when a MQTT-bridged event arrived within the last 60 seconds. The `CameraStatusChanged` Echo listener now updates `lastMqttActivity` on each event, giving the MQTT indicator a real liveness signal distinct from the WebSocket state.

### WR-03: Hardcoded URL in queue-depth polling violates Wayfinder convention

**Files modified:** `resources/js/pages/Dashboard.vue`
**Commit:** 66ad703
**Applied fix:** Imported the Wayfinder-generated `queueDepth` function from `@/routes` (aliased as `queueDepthRoute`) and replaced the hardcoded string `'/api/queue-depth'` in `fetchQueueDepth` with `queueDepthRoute.url()`. Import was placed before `@/routes/alerts` to maintain alphabetical import order per project ESLint rules.

---

_Fixed: 2026-04-11T00:00:00Z_
_Fixer: Claude (gsd-code-fixer)_
_Iteration: 1_

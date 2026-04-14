---
phase: 02-camera-management-liveness
verified: 2026-04-10T10:00:00Z
status: human_needed
score: 10/11 must-haves verified
overrides_applied: 0
deferred:
  - truth: "Camera detail page displays camera configuration and a list of personnel enrolled on that camera"
    addressed_in: "Phase 4"
    evidence: "Phase 4 success criteria 4: 'Deleting a personnel record sends MQTT DeletePersons to all cameras and removes per-camera enrollment records'; ENRL-01 through ENRL-10 cover all per-camera enrollment tracking. Show.vue has intentional placeholder (documented in 02-03-SUMMARY.md as D-16)."
human_verification:
  - test: "MQTT heartbeat updates last_seen_at in the browser UI within seconds"
    expected: "After sending a HeartBeat MQTT message for an enrolled camera, the 'Last Seen' column on the Cameras list refreshes to 'Just now' without a page reload"
    why_human: "Requires running MQTT broker, Reverb WebSocket, and a live browser connection. Cannot simulate end-to-end broadcast loop in tests."
  - test: "Real-time online/offline status dot updates on Index page"
    expected: "When a camera transitions from Online to Offline (or vice versa), the colored status dot on cameras/Index updates live via Echo WebSocket without reloading the page"
    why_human: "Requires live Echo WebSocket connection and a real MQTT Online/Offline message to propagate through the full broadcast path."
  - test: "Mapbox map renders correctly with credentials"
    expected: "cameras/Create, cameras/Edit, and cameras/Show all render Mapbox GL JS maps with the configured dark/light Mapbox Studio styles. Map click on Create/Edit updates the lat/lng inputs bidirectionally."
    why_human: "Requires valid MAPBOX_ACCESS_TOKEN and MAPBOX_DARK_STYLE/MAPBOX_LIGHT_STYLE env vars. Visual map render cannot be verified programmatically."
---

# Phase 02: Camera Management & Liveness Verification Report

**Phase Goal:** Admin can register and manage cameras, and the system tracks each camera's online/offline state in real time via MQTT
**Verified:** 2026-04-10T10:00:00Z
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Admin can create, edit, and delete cameras with device ID, name, location, and GPS coordinates | VERIFIED | CameraController has all 7 resource methods; 18 CameraCrudTest tests pass including `can store a camera`, `can update a camera`, `can delete a camera` |
| 2 | Camera list page shows all cameras with online/offline indicator and last-seen timestamp | VERIFIED | cameras/Index.vue renders table with CameraStatusDot and `formatRelativeTime(camera.last_seen_at)`; prop `cameras[]` fed from `CameraController::index()` ordering by name |
| 3 | When a camera sends an MQTT heartbeat, its last_seen_at updates within seconds; when heartbeat absent >90 seconds, camera shows as offline | VERIFIED | HeartbeatHandler updates `last_seen_at` via bulk query; CheckOfflineCamerasCommand runs every 30 seconds via scheduler; 14 CameraStatusTest tests pass covering both behaviors |
| 4 | Camera detail page displays camera configuration and a list of personnel enrolled on that camera | DEFERRED | Show.vue renders camera configuration correctly; "Enrolled Personnel" panel is intentional placeholder — Phase 4 covers per-camera enrollment tracking (ENRL-01 to ENRL-10) |
| 5 | MQTT listener processes Online/Offline messages and HeartBeat messages to maintain camera state | VERIFIED | OnlineOfflineHandler and HeartbeatHandler are fully implemented (not stubs); 4 CameraStatusBroadcastTest tests and 8 CameraStatusTest handler tests pass |
| 6 | Camera status updates in real time on list and detail pages via Echo WebSocket | VERIFIED (code) / NEEDS HUMAN (runtime) | Index.vue and Show.vue both use `useEcho('fras.alerts', '.CameraStatusChanged', ...)` and mutate local reactive `cameras` ref; wiring confirmed. End-to-end runtime requires human. |
| 7 | Duplicate device_id is rejected with a validation error | VERIFIED | StoreCameraRequest has `unique:cameras,device_id`; UpdateCameraRequest uses `Rule::unique()->ignore()` for self; test `store camera request enforces unique device_id` passes |
| 8 | Camera pages are inaccessible to unauthenticated users | VERIFIED | Routes registered inside `['auth', 'verified']` middleware group; test `requires authentication for camera routes` passes |
| 9 | Only state transitions trigger broadcasts — repeated Online messages do not flood WebSocket | VERIFIED | OnlineOfflineHandler checks `$wasOnline !== $isOnline` before dispatching; test `online offline handler does not dispatch event when status stays same` passes |
| 10 | Cameras appears in the sidebar navigation after Dashboard | VERIFIED | AppSidebar.vue imports `index as camerasIndex` from `@/routes/cameras` and adds `{ title: 'Cameras', href: camerasIndex(), icon: Camera }` after Dashboard entry |
| 11 | Seeded cameras appear in development with realistic Butuan City coordinates | VERIFIED | CameraSeeder creates 4 cameras with named Butuan City locations and decimal coordinates in 8.94-8.95 lat / 125.54 lng range; DatabaseSeeder calls CameraSeeder |

**Score:** 10/11 truths verified (1 deferred to Phase 4, 1 needs human for runtime path)

### Deferred Items

Items not yet met but explicitly addressed in later milestone phases.

| # | Item | Addressed In | Evidence |
|---|------|-------------|----------|
| 1 | Enrolled personnel list on camera detail page | Phase 4 | Phase 4 success criteria covers ENRL-01 to ENRL-10 (enrollment sync, per-camera status). Show.vue D-16 placeholder documented as intentional in 02-03-SUMMARY.md. |

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `app/Models/Camera.php` | Eloquent model with casts and fillable attributes | VERIFIED | `#[Fillable(['device_id','name','location_label','latitude','longitude'])]`; casts: decimal:7, boolean, datetime |
| `app/Http/Controllers/CameraController.php` | Full resource controller with 7 CRUD methods | VERIFIED | All 7 methods: index, create, store, show, edit, update, destroy — all return typed responses |
| `app/Http/Requests/Camera/StoreCameraRequest.php` | Validation for camera creation | VERIFIED | Contains `unique:cameras,device_id` and `between:-90,90` coordinate rules |
| `app/Http/Requests/Camera/UpdateCameraRequest.php` | Validation for camera updates | VERIFIED | Contains `Rule::unique('cameras', 'device_id')->ignore($this->route('camera'))` |
| `app/Events/CameraStatusChanged.php` | Broadcast event for camera online/offline state changes | VERIFIED | Implements `ShouldBroadcast` on `PrivateChannel('fras.alerts')`; typed constructor with camera_id, camera_name, is_online, last_seen_at |
| `app/Mqtt/Handlers/HeartbeatHandler.php` | Heartbeat handler updating last_seen_at | VERIFIED | Uses `Camera::where('device_id', $facesluiceId)->update(['last_seen_at' => now()])`. No CameraStatusChanged import (no broadcast on heartbeat). |
| `app/Mqtt/Handlers/OnlineOfflineHandler.php` | Online/Offline handler updating is_online and broadcasting | VERIFIED | Updates is_online, dispatches CameraStatusChanged only when `$wasOnline !== $isOnline` |
| `app/Console/Commands/CheckOfflineCamerasCommand.php` | Scheduled command for offline detection | VERIFIED | Signature `fras:check-offline-cameras`; queries `is_online=true AND last_seen_at < now()-threshold`; configurable threshold |
| `routes/console.php` | Schedule registration for offline check | VERIFIED | `Schedule::command('fras:check-offline-cameras')->everyThirtySeconds()` |
| `routes/web.php` | Camera resource routes | VERIFIED | `Route::resource('cameras', CameraController::class)` inside `['auth','verified']` group |
| `resources/js/types/camera.ts` | Camera and CameraStatusPayload TypeScript interfaces | VERIFIED | Both interfaces exported with correct field types |
| `resources/js/components/MapboxMap.vue` | Reusable Mapbox GL JS map with interactive and read-only modes | VERIFIED | Uses `mapboxgl`; interactive prop controls cursor and click handler; non-reactive `let map` to avoid Proxy issues |
| `resources/js/components/CameraStatusDot.vue` | Status indicator component with colored dot and label | VERIFIED | Takes `isOnline: boolean` prop; renders emerald/neutral dot with Online/Offline label |
| `resources/js/pages/cameras/Index.vue` | Camera list page with table and real-time status updates | VERIFIED | Full table render; `useEcho('fras.alerts', '.CameraStatusChanged', ...)` wired; not a stub |
| `resources/js/pages/cameras/Create.vue` | Camera create form with Mapbox map | VERIFIED | Full form with all 5 fields; `CameraController.store.form()` Wayfinder binding; MapboxMap with bidirectional coordinate sync |
| `resources/js/pages/cameras/Edit.vue` | Camera edit form with pre-populated fields | VERIFIED | Pre-populates all fields from `props.camera`; `CameraController.update.form(props.camera)` binding |
| `resources/js/pages/cameras/Show.vue` | Camera detail page with two-column layout | VERIFIED | Two-column layout; read-only MapboxMap; CameraStatusDot; delete dialog; `useEcho` for real-time updates |
| `resources/js/components/AppSidebar.vue` | Sidebar with Cameras link | VERIFIED | `camerasIndex()` from `@/routes/cameras` added after Dashboard |
| `database/factories/CameraFactory.php` | Factory with online/offline states | VERIFIED | `online()` and `offline()` states; Butuan City coordinate ranges |
| `database/seeders/CameraSeeder.php` | 4 seeded cameras | VERIFIED | device_id 1026700-1026703, named locations, Butuan City GPS |
| `tests/Feature/Camera/CameraCrudTest.php` | 18 feature tests for CRUD | VERIFIED | 18/18 passing |
| `tests/Feature/Camera/CameraStatusTest.php` | 14 handler and offline detection tests | VERIFIED | 14/14 passing |
| `tests/Feature/Camera/CameraStatusBroadcastTest.php` | 4 broadcast dispatch tests | VERIFIED | 4/4 passing |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `CameraController.php` | `Camera.php` | Eloquent queries | WIRED | `Camera::create`, `Camera::orderBy`, `$camera->update`, `$camera->delete` |
| `CameraController.php` | `StoreCameraRequest.php` | type-hinted injection | WIRED | `store(StoreCameraRequest $request)` |
| `CameraController.php` | `UpdateCameraRequest.php` | type-hinted injection | WIRED | `update(UpdateCameraRequest $request, Camera $camera)` |
| `routes/web.php` | `CameraController.php` | Route::resource | WIRED | `Route::resource('cameras', CameraController::class)` — 7 routes confirmed by `php artisan route:list` |
| `HeartbeatHandler.php` | `Camera.php` | Eloquent update query | WIRED | `Camera::where('device_id', $facesluiceId)->update(['last_seen_at' => now()])` |
| `OnlineOfflineHandler.php` | `CameraStatusChanged.php` | event dispatch | WIRED | `CameraStatusChanged::dispatch(...)` inside `if ($wasOnline !== $isOnline)` guard |
| `CheckOfflineCamerasCommand.php` | `CameraStatusChanged.php` | event dispatch on state change | WIRED | `CameraStatusChanged::dispatch(...)` inside foreach over stale cameras |
| `routes/console.php` | `CheckOfflineCamerasCommand.php` | Laravel scheduler | WIRED | `Schedule::command('fras:check-offline-cameras')->everyThirtySeconds()` |
| `cameras/Index.vue` | `fras.alerts` channel | useEcho composable | WIRED | `useEcho('fras.alerts', '.CameraStatusChanged', (payload) => { camera.is_online = payload.is_online; })` |
| `cameras/Show.vue` | `CameraController.destroy` | Inertia Form with Wayfinder | WIRED | `CameraController.destroy.form(camera)` in delete dialog |
| `cameras/Create.vue` | `CameraController.store` | Inertia Form with Wayfinder | WIRED | `CameraController.store.form()` binding on `<Form>` |
| `AppSidebar.vue` | `cameras.index` route | Wayfinder route function | WIRED | `import { index as camerasIndex } from '@/routes/cameras'`; used in `mainNavItems` |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
|----------|--------------|--------|-------------------|--------|
| `cameras/Index.vue` | `cameras` (ref) | `CameraController::index()` returns `Camera::orderBy('name')->get([...])` | Yes — DB query | FLOWING |
| `cameras/Show.vue` | `camera` (ref) | `CameraController::show()` returns `$camera` model | Yes — route model binding | FLOWING |
| `cameras/Edit.vue` | `props.camera` | `CameraController::edit()` returns `$camera` | Yes — route model binding | FLOWING |
| `cameras/Index.vue` | `cameras[i].is_online` (Echo update) | `OnlineOfflineHandler` → `CameraStatusChanged::dispatch` → Reverb → Echo | Yes — state transition triggers real broadcast | FLOWING |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| All CRUD tests pass | `php artisan test --compact --filter=CameraCrud` | 18 passed (89 assertions) | PASS |
| All status handler tests pass | `php artisan test --compact --filter=CameraStatus` | 18 passed (29 assertions) | PASS |
| 7 camera resource routes registered | `php artisan route:list --name=cameras` | 7 routes (index, create, store, show, edit, update, destroy) | PASS |
| Frontend TypeScript compiles clean | `npm run build` | Built in 2.40s, no errors | PASS |
| Offline detection command registered | `php artisan list \| grep fras` | `fras:check-offline-cameras` present | PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|------------|------------|-------------|--------|----------|
| CAM-01 | 02-01, 02-03 | Admin can register camera with device ID, name, location label, GPS coordinates | SATISFIED | CameraController::store + StoreCameraRequest; cameras/Create.vue with full form; 18 passing tests |
| CAM-02 | 02-01, 02-03 | Admin can edit and delete camera records | SATISFIED | CameraController::update/destroy; cameras/Edit.vue + Show.vue delete dialog; `can update a camera` and `can delete a camera` tests |
| CAM-03 | 02-02 | System tracks camera online/offline state via MQTT heartbeat messages | SATISFIED | HeartbeatHandler updates last_seen_at; OnlineOfflineHandler updates is_online; handler tests pass |
| CAM-04 | 02-02 | System marks camera offline when heartbeat absent for more than 90 seconds | SATISFIED | CheckOfflineCamerasCommand with configurable threshold (default 90s), running every 30 seconds; `marks stale online cameras as offline` test passes |
| CAM-05 | 02-01, 02-03 | Camera list page shows all cameras with online/offline state and last seen time | SATISFIED | cameras/Index.vue renders CameraStatusDot and formatRelativeTime; data flows from CameraController |
| CAM-06 | 02-01, 02-03 | Camera detail page shows camera configuration and list of enrolled personnel | PARTIAL | Configuration section fully implemented on Show.vue; enrolled personnel panel is intentional placeholder deferred to Phase 4 |
| OPS-04 | 02-02 | MQTT listener handles Online/Offline messages to update camera is_online state | SATISFIED | OnlineOfflineHandler fully implemented; 8 tests pass covering all operator cases |
| OPS-05 | 02-02 | MQTT listener handles HeartBeat messages to update camera last_seen_at | SATISFIED | HeartbeatHandler fully implemented using efficient bulk update; 4 tests pass covering all edge cases |

**Note on CAM-06:** The requirement says "list of enrolled personnel" — the configuration detail portion is fully implemented. The personnel list is a deliberate Phase 4 placeholder. See Deferred Items section.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `cameras/Show.vue` | ~205-225 | "No personnel enrolled" placeholder (Enrolled Personnel card) | INFO | Intentional — documented in 02-03-SUMMARY.md as D-16. Phase 4 will wire real enrollment data. Not a blocker. |
| `MapboxMap-BLaT8yVd.js` (build) | n/a | Chunk size >500KB (mapbox-gl is 1.7MB bundled) | INFO | Expected for mapbox-gl v3. No performance impact beyond initial load. Not a code defect. |

No TODO/FIXME/STUB comments found in any production code. No empty `return null` or `return {}` implementation stubs. All handlers fully replace the Phase 1 stubs.

### Human Verification Required

#### 1. MQTT heartbeat updates last_seen_at live in browser

**Test:** Send a HeartBeat MQTT message for device_id `1026700` while watching the Cameras list at `/cameras`
**Expected:** The "Last Seen" column for Main Entrance updates to "Just now" within one scheduler cycle (30 seconds) without a page reload
**Why human:** Requires running MQTT broker + Reverb + Laravel scheduler. Cannot be automated without full infrastructure.

#### 2. Real-time online/offline status dot updates on Index and Show pages

**Test:** Send an `Online` MQTT message for an offline camera, then an `Offline` message. Observe `/cameras` and `/cameras/{id}` in a browser with Echo connected.
**Expected:** The colored status dot transitions between emerald (online) and neutral (offline) live without page reload
**Why human:** Requires live Echo WebSocket connection (Reverb) and real MQTT broker. End-to-end broadcast path cannot be mocked in unit tests.

#### 3. Mapbox map renders correctly with credentials

**Test:** Navigate to `/cameras/create`, `/cameras/{id}/edit`, and `/cameras/{id}` with valid Mapbox credentials in `.env`
**Expected:** Maps render with correct dark/light styles. Clicking the map on Create/Edit page updates the lat/lng input fields. Map center updates when lat/lng inputs change (300ms debounce).
**Why human:** Requires valid `MAPBOX_ACCESS_TOKEN`, `MAPBOX_DARK_STYLE`, `MAPBOX_LIGHT_STYLE` in `.env`. Visual rendering and bidirectional coordinate sync cannot be verified programmatically.

### Gaps Summary

No gaps blocking goal achievement. All 36 tests (18 CRUD + 14 status + 4 broadcast) pass. All backend artifacts are substantive, wired, and data flows are confirmed. All frontend pages replace the Plan 01 stubs with full implementations. The three items in Human Verification are runtime/visual checks that require live infrastructure — they are not code defects.

---

_Verified: 2026-04-10T10:00:00Z_
_Verifier: Claude (gsd-verifier)_

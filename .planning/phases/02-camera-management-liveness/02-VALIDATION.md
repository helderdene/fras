---
phase: 2
slug: camera-management-liveness
status: draft
nyquist_compliant: true
wave_0_complete: false
created: 2026-04-10
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP), vue-tsc (TypeScript) |
| **Config file** | `phpunit.xml`, `tsconfig.json` |
| **Quick run command** | `php artisan test --compact --filter=Camera` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Camera`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | CAM-01, CAM-02, CAM-05, CAM-06 | T-02-01..03 | Auth gating, validation, unique constraint | feature | `php artisan test --compact --filter=CameraCrud` | ❌ W0 | ⬜ pending |
| 02-01-02 | 01 | 1 | CAM-01, CAM-02 | T-02-01..04 | Auth + verified middleware, CSRF | feature | `php artisan test --compact --filter=CameraCrud` | ❌ W0 | ⬜ pending |
| 02-02-01 | 02 | 1 | OPS-04, CAM-03 | T-02-05..08 | Payload validation, state transition gating | feature | `php artisan test --compact --filter=CameraStatus` | ❌ W0 | ⬜ pending |
| 02-02-02 | 02 | 1 | OPS-05, CAM-04 | T-02-09 | Configurable threshold, no duplicate broadcasts | feature | `php artisan test --compact --filter=CameraStatus` | ❌ W0 | ⬜ pending |
| 02-03-01 | 03 | 2 | CAM-05 | T-02-10..12 | — | lint+build | `npm run lint:check && npm run format:check` | n/a | ⬜ pending |
| 02-03-02 | 03 | 2 | CAM-05 | — | — | lint+build | `npm run lint:check && npm run format:check` | n/a | ⬜ pending |
| 02-03-03 | 03 | 2 | CAM-06 | — | — | build | `npm run build 2>&1 \| tail -5` | n/a | ⬜ pending |
| 02-03-04 | 03 | 2 | CAM-05, CAM-06 | — | — | checkpoint:human-verify | Human visual verification of full CRUD flow | n/a | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Camera/CameraCrudTest.php` — TDD test-first for Camera model, factory, validation, controller CRUD (Plan 01, Tasks 1-2)
- [ ] `tests/Feature/Camera/CameraStatusTest.php` — TDD test-first for HeartbeatHandler, OnlineOfflineHandler, CheckOfflineCamerasCommand (Plan 02, Tasks 1-2)
- [ ] `tests/Feature/Camera/CameraStatusBroadcastTest.php` — TDD test-first for broadcast dispatch/non-dispatch on state transitions (Plan 02, Task 1)

*Existing test infrastructure (Pest v4) covers all phase requirements.*

---

## Wave 2 Behavioral Coverage Note

Plan 02-03 (Wave 2) implements Vue frontend pages. Tasks 1-3 use lint/build/format checks as automated verification because:
- Vue page rendering requires a full browser context not available in Pest PHP tests
- Inertia page-render assertions for basic route-level rendering are covered in Plan 02-01 Task 2 (CameraCrudTest includes `it('can list cameras')`, `it('can view create form')`, `it('can view a camera')`, `it('can view edit form')` which assert Inertia page rendering returns 200)
- Task 4 (`checkpoint:human-verify`) provides the behavioral verification gate: human visually verifies the full camera CRUD flow including Mapbox rendering, form submission, real-time Echo updates, and delete confirmation

This satisfies Nyquist because: (1) backend route rendering is automated in Plan 01, (2) frontend compilation correctness is automated in Plan 03 Tasks 1-3, and (3) visual/interactive behavior is verified by the human checkpoint in Task 4.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Mapbox map renders with camera pin | CAM-01 | Requires browser with Mapbox GL JS rendering | Open camera create page, verify map loads, click to set coordinates |
| Real-time status badge update | CAM-05 | Requires live WebSocket connection | Open camera list, publish MQTT heartbeat, verify badge updates |
| Camera offline detection visual | CAM-04 | Requires waiting 90s timeout | Register camera, stop heartbeats, wait 90s, verify offline indicator |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 15s
- [x] `nyquist_compliant: true` set in frontmatter
- [x] Wave 2 behavioral coverage acknowledged via human checkpoint gate

**Approval:** pending

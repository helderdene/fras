---
phase: 2
slug: camera-management-liveness
status: draft
nyquist_compliant: false
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
| 02-01-01 | 01 | 1 | CAM-01 | — | N/A | feature | `php artisan test --compact --filter=CameraControllerTest` | ❌ W0 | ⬜ pending |
| 02-01-02 | 01 | 1 | CAM-02 | — | N/A | feature | `php artisan test --compact --filter=CameraControllerTest` | ❌ W0 | ⬜ pending |
| 02-02-01 | 02 | 1 | OPS-04 | — | N/A | feature | `php artisan test --compact --filter=OnlineOfflineHandlerTest` | ❌ W0 | ⬜ pending |
| 02-02-02 | 02 | 1 | OPS-05 | — | N/A | feature | `php artisan test --compact --filter=HeartbeatHandlerTest` | ❌ W0 | ⬜ pending |
| 02-02-03 | 02 | 1 | CAM-03 | — | N/A | feature | `php artisan test --compact --filter=DetectOfflineCamerasTest` | ❌ W0 | ⬜ pending |
| 02-02-04 | 02 | 1 | CAM-04 | — | N/A | feature | `php artisan test --compact --filter=DetectOfflineCamerasTest` | ❌ W0 | ⬜ pending |
| 02-03-01 | 03 | 2 | CAM-05 | — | N/A | feature | `php artisan test --compact --filter=CameraListPageTest` | ❌ W0 | ⬜ pending |
| 02-03-02 | 03 | 2 | CAM-06 | — | N/A | feature | `php artisan test --compact --filter=CameraDetailPageTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Camera/CameraControllerTest.php` — stubs for CAM-01, CAM-02
- [ ] `tests/Feature/Camera/OnlineOfflineHandlerTest.php` — stubs for OPS-04
- [ ] `tests/Feature/Camera/HeartbeatHandlerTest.php` — stubs for OPS-05
- [ ] `tests/Feature/Camera/DetectOfflineCamerasTest.php` — stubs for CAM-03, CAM-04
- [ ] `tests/Feature/Camera/CameraListPageTest.php` — stubs for CAM-05
- [ ] `tests/Feature/Camera/CameraDetailPageTest.php` — stubs for CAM-06

*Existing test infrastructure (Pest v4) covers all phase requirements.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Mapbox map renders with camera pin | CAM-01 | Requires browser with Mapbox GL JS rendering | Open camera create page, verify map loads, click to set coordinates |
| Real-time status badge update | CAM-05 | Requires live WebSocket connection | Open camera list, publish MQTT heartbeat, verify badge updates |
| Camera offline detection visual | CAM-04 | Requires waiting 90s timeout | Register camera, stop heartbeats, wait 90s, verify offline indicator |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

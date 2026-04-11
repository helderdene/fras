---
phase: 06
slug: dashboard-map
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-04-11
---

# Phase 06 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP) + vue-tsc (TypeScript) |
| **Config file** | `phpunit.xml` / `tsconfig.json` |
| **Quick run command** | `php artisan test --compact --filter=Dashboard` |
| **Full suite command** | `php artisan test --compact` |
| **TypeScript check** | `npx vue-tsc --noEmit` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Dashboard`
- **After every frontend task:** Run `npx vue-tsc --noEmit` to catch TypeScript errors
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green + `npx vue-tsc --noEmit` clean
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | DASH-04, DASH-05 | T-6-01 | Auth on queue-depth | feature (TDD) | `php artisan test --compact --filter=DashboardController` | Created by TDD RED phase | ⬜ pending |
| 06-01-02 | 01 | 1 | DASH-04, DASH-05 | — | N/A | ts-check + build | `npx vue-tsc --noEmit && npm run build` | N/A | ⬜ pending |
| 06-02-01 | 02 | 2 | DASH-01, DASH-02, DASH-03, DASH-06 | T-6-04 | setDOMContent (XSS) | ts-check + build | `npx vue-tsc --noEmit && npm run build` | N/A | ⬜ pending |
| 06-02-02 | 02 | 2 | DASH-01, DASH-06 | — | N/A | ts-check + build + suite | `npx vue-tsc --noEmit && npm run build && php artisan test --compact` | N/A | ⬜ pending |
| 06-03-01 | 03 | 3 | DASH-07, DASH-08 | — | N/A | ts-check + build + suite | `npx vue-tsc --noEmit && npm run build && php artisan test --compact` | N/A | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [x] `tests/Feature/DashboardControllerTest.php` — created by Plan 01 Task 1 TDD RED phase (test-first, then implementation). No separate Wave 0 plan needed because the task is `tdd="true"` and its `<behavior>` block defines all 9 test cases. The executor writes failing tests before any production code.
- [x] TypeScript type checking: `npx vue-tsc --noEmit` — included in verify commands for Plans 01 (Task 2), 02, and 03.

*Wave 0 is satisfied by Plan 01 Task 1 TDD workflow and vue-tsc in all plan verify commands.*

---

## Nyquist Sampling Continuity

3-consecutive-task rule check:

| Task Sequence | Automated Verify |
|---------------|-----------------|
| 06-01-01 (TDD) | `php artisan test --compact --filter=DashboardController` |
| 06-01-02 | `npx vue-tsc --noEmit && npm run build && php artisan test --compact --filter=DashboardController` |
| 06-02-01 | `npx vue-tsc --noEmit && npm run build && php artisan test --compact --filter=DashboardController` |
| 06-02-02 | `npx vue-tsc --noEmit && npm run build && php artisan test --compact` |
| 06-03-01 | `npx vue-tsc --noEmit && npm run build && php artisan test --compact` |

No 3 consecutive tasks without automated verify. Nyquist satisfied.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Camera markers at GPS coords | DASH-02 | Visual/map rendering | Open dashboard, verify markers match camera lat/lng |
| Pulse ring animation on recognition | DASH-03 | CSS animation timing | Trigger recognition event, observe 3s pulse ring |
| Marker popup with details | DASH-06 | Interactive UI element | Click camera marker, verify popup shows name/status/last event |
| Status bar indicators | DASH-04 | Real-time connection state | Check MQTT/Reverb/queue indicators show correct state |
| Dark/light map style toggle | DASH-07 | Mapbox style switching | Toggle theme, verify map style changes and markers persist |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references (TDD task creates test file in RED phase)
- [x] No watch-mode flags
- [x] Feedback latency < 15s
- [x] `nyquist_compliant: true` set in frontmatter
- [x] `npx vue-tsc --noEmit` included in Plans 01/02/03 verify commands

**Approval:** approved

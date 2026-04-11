---
phase: 06
slug: dashboard-map
status: draft
nyquist_compliant: false
wave_0_complete: false
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
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Dashboard`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | DASH-01 | — | N/A | feature | `php artisan test --compact --filter=DashboardController` | ❌ W0 | ⬜ pending |
| 06-01-02 | 01 | 1 | DASH-05 | — | N/A | feature | `php artisan test --compact --filter=DashboardController` | ❌ W0 | ⬜ pending |
| 06-02-01 | 02 | 1 | DASH-02 | — | N/A | manual | Visual check: markers at GPS coords | N/A | ⬜ pending |
| 06-02-02 | 02 | 1 | DASH-03 | — | N/A | manual | Visual check: pulse ring animation | N/A | ⬜ pending |
| 06-02-03 | 02 | 1 | DASH-06 | — | N/A | manual | Visual check: marker popup | N/A | ⬜ pending |
| 06-03-01 | 03 | 2 | DASH-04 | — | N/A | manual | Visual check: status bar indicators | N/A | ⬜ pending |
| 06-03-02 | 03 | 2 | DASH-07 | — | N/A | manual | Visual check: dark/light toggle | N/A | ⬜ pending |
| 06-03-03 | 03 | 2 | DASH-08 | — | N/A | feature | `php artisan test --compact --filter=DashboardController` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/DashboardControllerTest.php` — stubs for DASH-01, DASH-05, DASH-08
- [ ] TypeScript type checking: `npx vue-tsc --noEmit`

*Existing test infrastructure covers framework needs.*

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

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

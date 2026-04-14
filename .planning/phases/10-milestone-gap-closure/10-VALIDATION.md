---
phase: 10
slug: milestone-gap-closure
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-14
---

# Phase 10 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP) + vue-tsc (TypeScript) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter=` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter={relevant}`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 10-01-01 | 01 | 1 | CAM-03, CAM-05 | — | N/A | feature | `php artisan test --compact --filter=CameraStatusChanged` | ❌ W0 | ⬜ pending |
| 10-01-02 | 01 | 1 | INFRA-03 | — | N/A | manual | verify .env.example contents | ✅ | ⬜ pending |
| 10-01-03 | 01 | 1 | DASH-02, DASH-05, OPS-04, REC-13 | — | N/A | feature | `php artisan test --compact --filter=Alert` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Events/CameraStatusChangedTest.php` — verify broadcastAs returns short name
- [ ] `tests/Feature/Http/Controllers/AlertControllerTest.php` — verify acknowledged-by name in response

*Existing infrastructure covers framework and fixtures.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Camera status dots update in real time on Dashboard | DASH-02 | Requires WebSocket connection and browser | Open Dashboard, change camera status via MQTT, verify dot color changes within 5s |
| .env.example has correct vars | INFRA-03 | Static file check | `grep VITE_PUSHER .env.example` |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

---
phase: 5
slug: recognition-alerting
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-11
---

# Phase 5 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP) + Vitest (TypeScript — if frontend tests needed) |
| **Config file** | `phpunit.xml` (Pest uses PHPUnit config) |
| **Quick run command** | `php artisan test --compact --filter=Recognition` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Recognition`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 05-01-01 | 01 | 1 | REC-01 | — | N/A | unit | `php artisan test --compact --filter=RecognitionHandler` | ❌ W0 | ⬜ pending |
| 05-01-02 | 01 | 1 | REC-02 | — | N/A | unit | `php artisan test --compact --filter=AlertSeverity` | ❌ W0 | ⬜ pending |
| 05-01-03 | 01 | 1 | REC-03, REC-04 | — | N/A | feature | `php artisan test --compact --filter=RecognitionEvent` | ❌ W0 | ⬜ pending |
| 05-02-01 | 02 | 1 | REC-05 | — | N/A | feature | `php artisan test --compact --filter=RecognitionAlert` | ❌ W0 | ⬜ pending |
| 05-02-02 | 02 | 1 | REC-06, REC-07 | — | N/A | feature | `php artisan test --compact --filter=RecognitionController` | ❌ W0 | ⬜ pending |
| 05-03-01 | 03 | 2 | REC-08, REC-09 | — | N/A | manual | Browser visual check | — | ⬜ pending |
| 05-03-02 | 03 | 2 | REC-10, REC-11 | — | N/A | manual | Browser audio + modal check | — | ⬜ pending |
| 05-04-01 | 04 | 2 | REC-12, REC-13 | — | N/A | feature | `php artisan test --compact --filter=Acknowledge` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/RecognitionHandlerTest.php` — stubs for REC-01, REC-02, REC-03, REC-04
- [ ] `tests/Feature/RecognitionAlertTest.php` — stubs for REC-05, REC-06, REC-07
- [ ] `tests/Feature/RecognitionControllerTest.php` — stubs for REC-12, REC-13

*Existing test infrastructure (Pest v4) covers all framework needs.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Alert feed severity coloring | REC-09 | Visual CSS verification | Open feed, verify red/amber/green left borders |
| Audio chime on critical event | REC-10 | Browser audio API interaction | Trigger block-list event, verify sound plays |
| Detail modal with face/scene images | REC-08 | Visual layout verification | Click alert row, verify side-by-side modal |
| Real-time alert animation | REC-07 | WebSocket + animation timing | Watch feed during live RecPush, verify slide-in |

*All manual verifications have automated backend coverage; manual checks are for frontend visual/audio behavior only.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

---
phase: 7
slug: event-history-operations
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-11
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 with Laravel plugin |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter=EventHistory` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=EventHistory` or `--filter=CleanupRetention`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 07-01-01 | 01 | 1 | HIST-01 | — | Auth middleware on routes | Feature | `php artisan test --compact --filter=EventHistoryControllerTest` | ❌ W0 | ⬜ pending |
| 07-01-02 | 01 | 1 | HIST-02 | T-7-01 | Sort column whitelist, date validation | Feature | `php artisan test --compact --filter=EventHistoryControllerTest` | ❌ W0 | ⬜ pending |
| 07-01-03 | 01 | 1 | HIST-03 | — | N/A | Feature | `php artisan test --compact --filter=EventHistoryControllerTest` | ❌ W0 | ⬜ pending |
| 07-02-01 | 02 | 1 | OPS-01 | — | N/A | Feature | `php artisan test --compact --filter=CleanupRetentionImagesTest` | ❌ W0 | ⬜ pending |
| 07-02-02 | 02 | 1 | OPS-02 | — | N/A | Feature | `php artisan test --compact --filter=CleanupRetentionImagesTest` | ❌ W0 | ⬜ pending |
| 07-02-03 | 02 | 1 | OPS-03 | — | Config values used by job | Feature | `php artisan test --compact --filter=HdsConfigTest` | ✅ (partial) | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/EventHistory/EventHistoryControllerTest.php` — stubs for HIST-01, HIST-02, HIST-03
- [ ] `tests/Feature/Operations/CleanupRetentionImagesTest.php` — stubs for OPS-01, OPS-02

*Existing `tests/Feature/Infrastructure/HdsConfigTest.php` partially covers OPS-03 (retention config keys exist).*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Face crop thumbnail renders in table | HIST-03 | Visual rendering verification | Load history page with events that have face images; verify thumbnails display |
| AlertDetailModal opens from history row | HIST-01 | UI interaction flow | Click a row in event history table; verify modal opens with correct event data |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

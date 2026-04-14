---
phase: 4
slug: enrollment-sync
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-10
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4.4 with Laravel plugin |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter=Enrollment` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Enrollment`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 04-01-01 | 01 | 1 | ENRL-01 | T-4-01 / — | Enrollment routes behind auth+verified middleware | feature | `php artisan test --compact --filter=EnrollmentSyncTest` | ❌ W0 | ⬜ pending |
| 04-01-02 | 01 | 1 | ENRL-02 | — | N/A | unit | `php artisan test --compact --filter=CameraEnrollmentServiceTest` | ❌ W0 | ⬜ pending |
| 04-01-03 | 01 | 1 | ENRL-03 | — | N/A | feature | `php artisan test --compact --filter=EnrollPersonnelBatchTest` | ❌ W0 | ⬜ pending |
| 04-02-01 | 02 | 1 | ENRL-04 | T-4-02 / — | Cache correlation requires exact messageId | feature | `php artisan test --compact --filter=AckHandlerTest` | ❌ W0 | ⬜ pending |
| 04-02-02 | 02 | 1 | ENRL-05 | — | N/A | feature | `php artisan test --compact --filter=EnrollmentStatusTest` | ❌ W0 | ⬜ pending |
| 04-02-03 | 02 | 1 | ENRL-06 | — | N/A | unit | `php artisan test --compact --filter=CameraEnrollmentServiceTest` | ❌ W0 | ⬜ pending |
| 04-03-01 | 03 | 2 | ENRL-07 | T-4-03 / — | Retry POST routes protected by auth middleware | feature | `php artisan test --compact --filter=EnrollmentRetryTest` | ❌ W0 | ⬜ pending |
| 04-03-02 | 03 | 2 | ENRL-08 | — | N/A | feature | `php artisan test --compact --filter=EnrollmentResyncTest` | ❌ W0 | ⬜ pending |
| 04-03-03 | 03 | 2 | ENRL-09 | T-4-04 / — | N/A (fire-and-forget) | feature | `php artisan test --compact --filter=EnrollmentDeleteSyncTest` | ❌ W0 | ⬜ pending |
| 04-04-01 | 04 | 2 | ENRL-10 | — | N/A | feature | `php artisan test --compact --filter=EnrollmentSummaryTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Enrollment/EnrollmentSyncTest.php` — stubs for ENRL-01
- [ ] `tests/Feature/Enrollment/CameraEnrollmentServiceTest.php` — stubs for ENRL-02, ENRL-06
- [ ] `tests/Feature/Enrollment/EnrollPersonnelBatchTest.php` — stubs for ENRL-03
- [ ] `tests/Feature/Enrollment/AckHandlerTest.php` — stubs for ENRL-04
- [ ] `tests/Feature/Enrollment/EnrollmentStatusTest.php` — stubs for ENRL-05
- [ ] `tests/Feature/Enrollment/EnrollmentRetryTest.php` — stubs for ENRL-07
- [ ] `tests/Feature/Enrollment/EnrollmentResyncTest.php` — stubs for ENRL-08
- [ ] `tests/Feature/Enrollment/EnrollmentDeleteSyncTest.php` — stubs for ENRL-09
- [ ] `tests/Feature/Enrollment/EnrollmentSummaryTest.php` — stubs for ENRL-10

*Existing infrastructure covers test framework. All test files are new for this phase.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Real-time sidebar update via Reverb | ENRL-05 | Requires running Reverb server + browser with Echo listener | 1. Open personnel Show page 2. Trigger enrollment 3. Observe SyncStatusDot updates without page refresh |
| Bulk summary panel UI | ENRL-10 | Visual layout verification | 1. Open personnel Index page 2. Verify per-camera counts display 3. Click card navigates to camera detail |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

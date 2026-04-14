---
phase: 3
slug: personnel-management
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-10
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter=Personnel` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Personnel`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 01 | 1 | PERS-01 | T-3-01 / — | Validates required fields, rejects invalid input | feature | `php artisan test --compact --filter=PersonnelCreate` | ❌ W0 | ⬜ pending |
| 03-01-02 | 01 | 1 | PERS-02 | — | Validates photo replacement updates record | feature | `php artisan test --compact --filter=PersonnelUpdate` | ❌ W0 | ⬜ pending |
| 03-01-03 | 01 | 1 | PERS-03 | — | Delete removes record and cascades | feature | `php artisan test --compact --filter=PersonnelDelete` | ❌ W0 | ⬜ pending |
| 03-02-01 | 02 | 1 | PERS-07 | T-3-02 / — | Rejects oversized/non-image files | unit | `php artisan test --compact --filter=PhotoPreprocessing` | ❌ W0 | ⬜ pending |
| 03-02-02 | 02 | 1 | PERS-07 | — | Resizes to max 1080p, compresses JPEG <1MB | unit | `php artisan test --compact --filter=PhotoPreprocessing` | ❌ W0 | ⬜ pending |
| 03-03-01 | 03 | 2 | PERS-04 | — | List displays correct columns and data | feature | `php artisan test --compact --filter=PersonnelList` | ❌ W0 | ⬜ pending |
| 03-03-02 | 03 | 2 | PERS-05 | — | Detail page renders with enrollment sidebar | feature | `php artisan test --compact --filter=PersonnelShow` | ❌ W0 | ⬜ pending |
| 03-03-03 | 03 | 2 | PERS-06 | — | Dropzone validates client-side constraints | manual | N/A | N/A | ⬜ pending |
| 03-03-04 | 03 | 2 | PERS-08 | — | Sync status dot shows gray "Not synced" | feature | `php artisan test --compact --filter=PersonnelSyncStatus` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Personnel/StorePersonnelTest.php` — stubs for PERS-01, PERS-02, PERS-03
- [ ] `tests/Feature/Personnel/PhotoPreprocessingTest.php` — stubs for PERS-07
- [ ] `tests/Feature/Personnel/PersonnelListTest.php` — stubs for PERS-04, PERS-05, PERS-08

*Existing Pest infrastructure covers all phase requirements.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Dropzone drag-and-drop with client-side preview | PERS-06 | Browser-only interaction (drag events, FileReader API) | 1. Navigate to personnel create page 2. Drag image onto dropzone 3. Verify preview renders 4. Drag non-image file 5. Verify rejection message |
| Client-side file size validation | PERS-06 | JavaScript FileReader validation before form submit | 1. Select file >1MB 2. Verify inline error before submission |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

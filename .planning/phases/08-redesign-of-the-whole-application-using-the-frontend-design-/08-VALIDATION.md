---
phase: 8
slug: redesign-of-the-whole-application-using-the-frontend-design
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-11
---

# Phase 8 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP) + vue-tsc (TypeScript) |
| **Config file** | `phpunit.xml`, `tsconfig.json` |
| **Quick run command** | `php artisan test --compact --filter=DesignSystem` |
| **Full suite command** | `php artisan test --compact && npx vue-tsc --noEmit` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `npx vue-tsc --noEmit`
- **After every plan wave:** Run `php artisan test --compact && npx vue-tsc --noEmit`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 08-01-01 | 01 | 1 | D-07, D-08 | — | N/A | type-check | `npx vue-tsc --noEmit` | ✅ | ⬜ pending |
| 08-01-02 | 01 | 1 | D-13 | — | N/A | type-check | `npx vue-tsc --noEmit` | ✅ | ⬜ pending |
| 08-02-01 | 02 | 1 | D-05, D-10 | — | N/A | type-check | `npx vue-tsc --noEmit` | ✅ | ⬜ pending |
| 08-03-01 | 03 | 2 | D-01, D-12 | — | N/A | visual + type | `npx vue-tsc --noEmit` | ✅ | ⬜ pending |
| 08-04-01 | 04 | 2 | D-04, D-06 | — | N/A | visual + type | `npx vue-tsc --noEmit` | ✅ | ⬜ pending |
| 08-05-01 | 05 | 3 | D-09, D-11 | — | N/A | visual + type | `npx vue-tsc --noEmit` | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

Existing infrastructure covers all phase requirements. No new test framework or stubs needed — this phase is purely visual/structural frontend work validated by TypeScript compilation and existing feature tests.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Slate/steel blue palette renders correctly in dark mode | D-07, D-08 | Visual appearance cannot be tested programmatically | Open dashboard in dark mode, verify steel blue tones across backgrounds, borders, text |
| Glassmorphism effects render on auth cards | D-10 | Visual effect quality is subjective | Open login page, verify backdrop-blur and semi-transparent background on auth card |
| Severity glow effects visible peripherally | D-09 | Perceptual test requiring human observation | Create critical alert, verify red glow is noticeable from a distance |
| Dense data grid readability | D-12 | Readability is subjective | Open events page with 20+ rows, verify data is scannable without eye strain |
| Inter font renders at all sizes | D-13 | Font rendering varies by OS/browser | Check headings, body text, and table data across Chrome/Firefox |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

---
phase: 5
slug: recognition-alerting
status: draft
nyquist_compliant: true
wave_0_complete: true
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
| 05-01-01 | 01 | 1 | REC-01 | — | N/A | unit | `php artisan test --compact --filter=RecognitionHandler` | inline TDD | ⬜ pending |
| 05-01-02 | 01 | 1 | REC-02 | — | N/A | unit | `php artisan test --compact --filter=AlertSeverity` | inline TDD | ⬜ pending |
| 05-01-03 | 01 | 1 | REC-03, REC-04 | — | N/A | feature | `php artisan test --compact --filter=RecognitionEvent` | inline TDD | ⬜ pending |
| 05-02-01 | 02 | 2 | REC-01 to REC-04, REC-06 | T-5-04 to T-5-08 | payload validation, size limits, strict base64 | feature | `php artisan test --compact --filter=RecognitionHandlerTest` | inline TDD | ⬜ pending |
| 05-03-01 | 03 | 2 | REC-05, REC-07 | — | N/A | feature | `php artisan test --compact --filter=AlertController` | inline TDD | ⬜ pending |
| 05-04-01 | 04 | 3 | REC-08 to REC-13 | T-5-12 to T-5-15 | XSS via auto-escape, server-side URLs, feed cap | lint+typecheck | `npm run lint:check && npm run format:check && npx vue-tsc --noEmit` | N/A | ⬜ pending |
| 05-04-03 | 04 | 3 | REC-08 to REC-13 | — | N/A | manual | Browser visual + audio check | — | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Approach

Plans use **inline TDD** (`tdd="true"` on tasks): tests are created within the same task as the production code, following red-green-refactor. Every task with `tdd="true"` writes its test file before the implementation, so test stubs are created as part of each task's execution rather than in a separate Wave 0 plan.

This satisfies Nyquist compliance because:
- Every code-producing task has an `<automated>` verify command
- Tests are written before implementation (TDD behavior blocks)
- No 3 consecutive tasks exist without automated verification
- Frontend tasks use lint + typecheck as automated verification

Separate Wave 0 test stub files are NOT needed — the inline TDD approach creates them during task execution.

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

- [x] All tasks have `<automated>` verify or inline TDD
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covered via inline TDD approach (tests created within tasks)
- [x] No watch-mode flags
- [x] Feedback latency < 15s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** approved (inline TDD satisfies per-task automated sampling)

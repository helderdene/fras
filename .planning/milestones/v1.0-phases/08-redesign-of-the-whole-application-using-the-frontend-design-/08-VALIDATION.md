---
phase: 8
slug: redesign-of-the-whole-application-using-the-frontend-design
status: draft
nyquist_compliant: true
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
| 08-01-01 | 01 | 1 | D-07, D-08 | T-8-01 | N/A | type-check | `grep -c 'Inter' resources/css/app.css \| grep -q '[3-9]' && grep 'hsl(222 47% 6%)' resources/css/app.css && grep 'hsl(217 91% 60%)' resources/css/app.css && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-01-02 | 01 | 1 | D-13 | T-8-01 | N/A | type-check | `grep 'inter:400,600' resources/views/app.blade.php && grep 'hsl(222 47% 6%)' resources/views/app.blade.php && grep '#3B82F6' resources/js/app.ts && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-02-01 | 02 | 2 | D-09 | T-8-02 | N/A | grep | `grep 'shadow-\[0_0_12px' resources/js/components/SeverityBadge.vue && grep 'shadow-\[0_0_6px' resources/js/components/CameraStatusDot.vue && grep 'shadow-\[0_0_6px' resources/js/components/SyncStatusDot.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-02-02 | 02 | 2 | D-10, D-12 | T-8-02 | N/A | grep | `grep 'uppercase tracking-wider' resources/js/components/ui/table/TableHead.vue && grep 'odd:bg-muted/20' resources/js/components/ui/table/TableRow.vue && grep 'dark:hover:shadow' resources/js/components/ui/button/index.ts && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-02-03 | 02 | 2 | D-10, D-11 | T-8-02 | N/A | grep | `grep 'backdrop-blur' resources/js/components/StatusBar.vue && grep 'font-semibold' resources/js/components/Heading.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-03-01 | 03 | 2 | D-05, D-10 | T-8-03 | N/A | grep | `grep 'AuthCardLayout' resources/js/layouts/AuthLayout.vue && grep 'backdrop-blur-lg' resources/js/layouts/auth/AuthCardLayout.vue && grep 'bg-background' resources/js/layouts/auth/AuthCardLayout.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-03-02 | 03 | 2 | D-01, D-10 | T-8-03 | N/A | grep | `grep 'FRAS' resources/js/components/AppLogo.vue && grep 'backdrop-blur' resources/js/components/AppSidebar.vue && grep 'border-primary' resources/js/components/NavMain.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-04-01 | 04 | 3 | D-02, D-10 | T-8-04 | N/A | grep | `grep 'backdrop-blur' resources/js/components/DashboardAlertFeed.vue && grep 'backdrop-blur' resources/js/components/CameraRail.vue && grep 'font-mono' resources/js/components/CameraRailItem.vue && grep 'text-\[28px\]\|text-2xl' resources/js/components/TodayStats.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-04-02 | 04 | 3 | D-04, D-12 | T-8-04 | N/A | grep | `grep 'font-mono' resources/js/pages/cameras/Index.vue && grep 'font-mono' resources/js/pages/personnel/Index.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-05-01 | 05 | 3 | D-06 | T-8-05 | N/A | grep | `grep 'FRAS' resources/js/pages/Welcome.vue && ! grep 'rsms.me' resources/js/pages/Welcome.vue && ! grep -r 'font-medium' resources/js/pages/auth/ resources/js/pages/Welcome.vue && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-05-02 | 05 | 3 | D-09, D-12 | T-8-05 | N/A | grep | `grep 'font-mono' resources/js/components/EventHistoryTable.vue && grep 'sticky' resources/js/components/EventHistoryTable.vue && REMAINING=$(grep -r 'font-medium' resources/js/ 2>/dev/null \| wc -l \| tr -d ' ') && [ "$REMAINING" -eq "0" ] && echo "PASS" \|\| echo "FAIL"` | N/A | ⬜ pending |
| 08-05-03 | 05 | 3 | D-04 | T-8-05 | N/A | feature | `php artisan test --compact` | ✅ | ⬜ pending |

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

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 30s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

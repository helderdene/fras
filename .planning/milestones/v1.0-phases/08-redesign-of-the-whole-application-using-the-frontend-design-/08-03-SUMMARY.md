---
phase: 08-redesign-of-the-whole-application-using-the-frontend-design-
plan: 03
subsystem: ui
tags: [layout, glassmorphism, branding, auth-card, sidebar, navigation, fras]

# Dependency graph
requires:
  - phase: 08-redesign-of-the-whole-application-using-the-frontend-design-
    plan: 01
    provides: CSS custom properties (slate/steel blue palette, Inter font)
provides:
  - AuthCardLayout glassmorphism card for auth pages
  - Glassmorphism sidebar with accent active indicators
  - FRAS branding throughout navigation
  - Polished nav components with transitions
  - Transparent DashboardTopNav with blur
  - Settings layout accent active indicator
affects:
  - All auth pages (Login, Register, ForgotPassword, etc.) via AuthLayout import change
  - All app pages via sidebar glassmorphism
  - Dashboard via DashboardTopNav transparent blur
  - Settings pages via Layout.vue active state

# Tech stack
added: []
patterns:
  - Glassmorphism via dark:bg-*/opacity dark:backdrop-blur-* pattern
  - Active nav indicator via border-l-[3px] border-primary
  - Conditional class binding with ternary for active/inactive states
  - transition-colors duration-150 for hover polish

# Key files
created:
  - None
modified:
  - resources/js/layouts/AuthLayout.vue
  - resources/js/layouts/auth/AuthCardLayout.vue
  - resources/js/components/AppSidebar.vue
  - resources/js/components/AppLogo.vue
  - resources/js/components/NavMain.vue
  - resources/js/components/NavUser.vue
  - resources/js/components/NavFooter.vue
  - resources/js/components/DashboardTopNav.vue
  - resources/js/layouts/settings/Layout.vue

# Decisions
key-decisions:
  - "AuthCardLayout chosen over AuthSimpleLayout for ops portal glassmorphism aesthetic per D-05/D-06"
  - "font-medium systematically absent from all modified files (weight 500 removed per UI-SPEC)"
  - "DashboardTopNav uses bg-transparent with dark:bg-background/80 instead of bg-muted for blurred transparent header"

# Metrics
duration: 3min
completed: 2026-04-11
tasks: 2
files: 9
---

# Phase 08 Plan 03: Layout Rework & Branding Summary

Auth card glassmorphism layout with dark portal aesthetic, glassmorphism sidebar with left accent active indicators, FRAS branding replacing Laravel Starter Kit across all navigation

## Task Results

### Task 1: Switch auth layout and add glassmorphism card styling
**Commit:** e104d83

Changed AuthLayout.vue to import AuthCardLayout instead of AuthSimpleLayout. Updated AuthCardLayout.vue with glassmorphism treatment: dark:bg-card/75 dark:backdrop-blur-lg, border-border/50, bg-background outer container, max-w-[400px], px-8 padding, text-primary logo icon, removed font-medium.

**Files modified:**
- `resources/js/layouts/AuthLayout.vue` - Import switch to AuthCardLayout
- `resources/js/layouts/auth/AuthCardLayout.vue` - Glassmorphism card, padding, accent logo

### Task 2: Add glassmorphism sidebar, FRAS branding, and navigation polish
**Commit:** 67297a8

Applied glassmorphism to sidebar (dark:bg-sidebar/80 dark:backdrop-blur-xl). Changed AppLogo branding from "Laravel Starter Kit" to "FRAS". Fixed AppLogoIcon dark mode to dark:text-white. Added left accent border (border-l-[3px] border-primary) to NavMain active items. Added transition-colors duration-150 to NavMain, NavUser, NavFooter. Made DashboardTopNav transparent with blur and text-primary branding. Updated Settings Layout active link to text-primary font-semibold with inactive text-muted-foreground.

**Files modified:**
- `resources/js/components/AppSidebar.vue` - Glassmorphism classes
- `resources/js/components/AppLogo.vue` - FRAS branding, dark:text-white fix
- `resources/js/components/NavMain.vue` - Active border, transitions, hover states
- `resources/js/components/NavUser.vue` - Transition polish
- `resources/js/components/NavFooter.vue` - Transition polish
- `resources/js/components/DashboardTopNav.vue` - Transparent blur, text-primary branding
- `resources/js/layouts/settings/Layout.vue` - Active indicator, transitions

## Deviations from Plan

None - plan executed exactly as written.

## Verification Results

1. `grep 'AuthCardLayout' resources/js/layouts/AuthLayout.vue` - PASS
2. `grep 'backdrop-blur-lg' resources/js/layouts/auth/AuthCardLayout.vue` - PASS
3. `grep 'FRAS' resources/js/components/AppLogo.vue` - PASS
4. `grep 'backdrop-blur' resources/js/components/AppSidebar.vue` - PASS
5. `grep 'text-primary' resources/js/layouts/settings/Layout.vue` - PASS
6. No `font-medium` in any modified file - PASS
7. ESLint - PASS (no errors)
8. Prettier - PASS (all files formatted)
9. `php artisan test --compact` - PASS (281 tests, 1027 assertions)

## Self-Check: PASSED

All 9 modified files verified on disk. Both commit hashes (e104d83, 67297a8) found in git log.

---
phase: 08-redesign-of-the-whole-application-using-the-frontend-design-
plan: 02
subsystem: ui
tags: [dark-mode-glow, dense-data-grid, glassmorphism, severity-badges, table-components, shadcn-vue, tailwind]

# Dependency graph
requires:
  - phase: 08-redesign-of-the-whole-application-using-the-frontend-design-
    plan: 01
    provides: Slate/steel blue CSS custom property palette and Inter font
provides:
  - Severity badges with dark mode glow box-shadows
  - Camera/sync status dots with dark mode glow
  - Dense data grid table components (compact headers, alternating rows)
  - Destructive button dark mode hover glow
  - Dialog glassmorphism overlay
  - Card subtle dark mode border
  - StatusBar glassmorphism and monospace numerics
  - ConnectionBanner glow effect
  - font-semibold consistently across all modified components
affects: [08-03, 08-04, 08-05]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Dark mode glow via arbitrary shadow values: dark:shadow-[0_0_Npx_rgba(...)]"
    - "Dense data grid: compact h-8 headers, uppercase tracking-wider, alternating odd:bg-muted/20 rows"
    - "Glassmorphism: dark:backdrop-blur-sm with opacity background"
    - "font-semibold (weight 600) replacing font-medium (weight 500) per UI-SPEC"

key-files:
  created: []
  modified:
    - resources/js/components/SeverityBadge.vue
    - resources/js/components/CameraStatusDot.vue
    - resources/js/components/SyncStatusDot.vue
    - resources/js/components/ui/table/TableHead.vue
    - resources/js/components/ui/table/TableRow.vue
    - resources/js/components/ui/button/index.ts
    - resources/js/components/ui/badge/index.ts
    - resources/js/components/ui/dialog/DialogOverlay.vue
    - resources/js/components/ui/card/Card.vue
    - resources/js/components/StatusBar.vue
    - resources/js/components/ConnectionBanner.vue
    - resources/js/components/Heading.vue
    - resources/js/components/InputError.vue
    - resources/js/components/TextLink.vue

key-decisions:
  - "Glow intensities: critical 12px/0.4, warning 10px/0.35, info 8px/0.3 -- descending by severity"
  - "Status dots use 6px glow (smaller than badges) for visual hierarchy"
  - "Dialog overlay changed from bg-black/80 to bg-black/60 with backdrop-blur for glassmorphism"
  - "font-medium removed from all modified components in favor of font-semibold (600 weight only)"

patterns-established:
  - "Severity glow intensities scale with urgency (critical > warning > info)"
  - "Dense data grid: h-8 header height, text-xs uppercase tracking-wider, bg-muted/50 header bg"
  - "Alternating rows via odd:bg-muted/20 on TableRow (applies to all tables app-wide)"

requirements-completed: [UI-04, UI-05, UI-06]

# Metrics
duration: 4min
completed: 2026-04-11
---

# Phase 8 Plan 2: Component Polish Summary

**Dark mode glow effects on severity/status indicators, dense data grid table styling, glassmorphism overlays, and font-semibold weight consistency across 14 components**

## Performance

- **Duration:** 4 min
- **Started:** 2026-04-11T09:37:49Z
- **Completed:** 2026-04-11T09:42:29Z
- **Tasks:** 3
- **Files modified:** 14

## Accomplishments
- Added dark-mode-only glow box-shadows to SeverityBadge (3 severity levels), CameraStatusDot (online), and SyncStatusDot (synced/pending/failed)
- Applied dense data grid styling to TableHead (compact h-8, uppercase tracking-wider, muted background) and TableRow (alternating rows, accent hover, softer borders)
- Added destructive button dark mode hover glow and font-semibold to button/badge base CVA strings
- DialogOverlay: glassmorphism with backdrop-blur-sm and reduced opacity (bg-black/60)
- Card: subtle dark mode border opacity (dark:border-border/50)
- StatusBar: glassmorphism background, monospace font for queue depth number
- ConnectionBanner: red glow shadow for disconnected state, font-semibold text
- Heading: font-semibold on both default and small variants
- InputError: dark mode drop-shadow glow on validation error text
- TextLink: hover:text-primary accent color on hover

## Task Commits

Each task was committed atomically:

1. **Task 1: Add glow effects to severity and status indicator components** - `df82ac2` (feat)
2. **Task 2: Apply dense data grid styling and enhance shadcn-vue UI components** - `c48fd30` (feat)
3. **Task 3: Polish StatusBar, ConnectionBanner, Heading, InputError, TextLink** - `9f7afff` (feat)

## Files Created/Modified
- `resources/js/components/SeverityBadge.vue` - Red/amber/green glow, /90 opacity backgrounds, font-semibold
- `resources/js/components/CameraStatusDot.vue` - Green glow on online dot
- `resources/js/components/SyncStatusDot.vue` - Glow effects per sync status
- `resources/js/components/ui/table/TableHead.vue` - Dense h-8 header, uppercase, tracking-wider, muted bg
- `resources/js/components/ui/table/TableRow.vue` - Alternating rows, accent hover, softer border
- `resources/js/components/ui/button/index.ts` - Destructive hover glow, font-semibold base
- `resources/js/components/ui/badge/index.ts` - font-semibold base
- `resources/js/components/ui/dialog/DialogOverlay.vue` - Glassmorphism backdrop-blur
- `resources/js/components/ui/card/Card.vue` - Dark mode border opacity
- `resources/js/components/StatusBar.vue` - Glassmorphism, monospace queue depth
- `resources/js/components/ConnectionBanner.vue` - Red glow shadow, font-semibold
- `resources/js/components/Heading.vue` - font-semibold on both variants
- `resources/js/components/InputError.vue` - Dark mode drop-shadow glow
- `resources/js/components/TextLink.vue` - Accent hover color

## Decisions Made
- Glow intensities descend by severity: critical 12px/0.4, warning 10px/0.35, info 8px/0.3
- Status dots use smaller 6px glow for visual hierarchy distinction from badges
- Dialog overlay reduced from bg-black/80 to bg-black/60 with backdrop-blur for glassmorphism effect
- font-medium systematically replaced with font-semibold across all modified components

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - all changes are CSS utility class modifications to existing Vue components.

## Next Phase Readiness
- All 14 components now carry the ops center visual identity (glow effects, dense data grid, glassmorphism)
- Any page using these components (alerts, events, cameras, personnel, dashboard) automatically inherits the styling
- Plans 08-03 through 08-05 can apply page-level layout changes knowing component styling is complete
- All 281 existing tests pass (visual-only changes)
- ESLint, Prettier clean

---
*Phase: 08-redesign-of-the-whole-application-using-the-frontend-design-*
*Completed: 2026-04-11*

# Phase 8: Redesign of the Whole Application Using the Frontend Design Skill - Context

**Gathered:** 2026-04-11
**Status:** Ready for planning

<domain>
## Phase Boundary

Full visual redesign of all application pages (dashboard, admin CRUD, auth, settings, welcome) using the `frontend-design` skill. Transforms the current generic shadcn-vue appearance into a distinctive, production-grade security operations center aesthetic. No new features or backend changes — purely visual/structural frontend work.

</domain>

<decisions>
## Implementation Decisions

### Design Direction
- **D-01:** Security ops center visual identity across the entire application. Dense, data-rich, professional, always-on monitoring aesthetic.
- **D-02:** Gradient approach for visual intensity — Dashboard is full ops center treatment (darkest, densest, most dramatic). Admin pages (cameras, personnel, alerts, events) are slightly softer — still dark-first and professional but with more whitespace and standard form layouts. Two visual tiers, not uniform intensity.
- **D-03:** Primary visual reference: Genetec Security Center — industry-standard CCTV/access control platform with dark UI, camera grids, alert panels, map-based monitoring.

### Redesign Scope
- **D-04:** All 22 pages redesigned across all 6 sections: dashboard, admin (cameras, personnel, alerts, events), auth (7 pages), settings (3 pages), and welcome.
- **D-05:** Layouts are reworked structurally, not just restyled — AppSidebarLayout, AuthSimpleLayout, DashboardLayout, SettingsLayout all get structural AND visual changes.
- **D-06:** Welcome page gets ops-styled treatment — matches the app aesthetic as a dark, professional product login portal. Not a marketing landing page.

### Color & Branding
- **D-07:** Slate / steel blue palette — cool-toned grays with steel blue undertones as the base. Professional and modern without going full "hacker dark mode." Balanced between industrial and polished.
- **D-08:** Dark-first design — dark mode is the default experience. Light mode remains available but is secondary. Operators work in dimly lit command centers; dark UI reduces eye strain and reinforces the ops center feel.
- **D-09:** Severity colors (red/amber/green) refined with glow effects — same base colors but with subtle glow, gradient, or luminance effects in dark mode. Severity indicators pop more against dark backgrounds for a more dramatic ops center feel.

### Component Polish
- **D-10:** Deep customization of shadcn-vue components — rework component templates with custom animations, glow borders, gradient backgrounds, glassmorphism effects. Components should look distinctly FRAS-branded, not generic shadcn.
- **D-11:** Subtle transitions for motion — smooth page transitions, hover effects, fade-ins. Professional and restrained, appropriate for a monitoring context. No distracting or dramatic motion.
- **D-12:** Dense data grid style for tables (cameras list, personnel list, event history) — tight row height, monospace data columns, alternating row shading, fixed headers. Maximum data density for monitoring, inspired by log viewers and SIEM tools.
- **D-13:** Switch primary font from Instrument Sans to Inter — clean, highly legible at small sizes, excellent for data-dense UIs. Industry standard for dashboards and monitoring tools.

### Claude's Discretion
- Exact glassmorphism intensity and blur values
- Specific glow colors and intensities for severity indicators
- Card vs flat surface decisions per component
- Sidebar navigation rework structure
- Auth layout variant selection (keep AuthSimpleLayout or switch to AuthSplitLayout/AuthCardLayout)
- Skeleton loading patterns and empty state designs
- Specific Inter font weights to include
- CSS custom property value choices for the slate/steel blue theme
- Responsive breakpoint behavior adjustments
- Icon styling refinements (stroke width, size adjustments for the new density)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Existing Theme & Styling
- `resources/css/app.css` — Current Tailwind v4 theme with shadcn-vue CSS custom properties (light/dark). This is the primary file to modify for palette changes.
- `components.json` — shadcn-vue configuration (new-york-v4 style, neutral base, icon library). Controls component generation.

### Layout System
- `resources/js/layouts/app/AppSidebarLayout.vue` — Current main app layout (sidebar + content shell)
- `resources/js/layouts/auth/AuthSimpleLayout.vue` — Current auth page layout
- `resources/js/layouts/DashboardLayout.vue` — Dashboard-specific full-viewport layout
- `resources/js/layouts/settings/Layout.vue` — Settings page layout with sub-navigation
- `resources/js/app.ts` — Layout resolver switch statement (determines which layout per page prefix)

### Key Pages to Redesign
- `resources/js/pages/Dashboard.vue` — Command center (three-panel layout, map, alerts, camera rail)
- `resources/js/pages/cameras/Index.vue` — Camera list with status dots and real-time Echo updates
- `resources/js/pages/personnel/Index.vue` — Personnel list with sync status and search
- `resources/js/pages/alerts/Index.vue` — Alert feed with severity filtering and Echo listener
- `resources/js/pages/events/Index.vue` — Event history with filters, table, pagination
- `resources/js/pages/Welcome.vue` — Landing page (to become ops-styled portal)

### Key Components
- `resources/js/components/DashboardMap.vue` — Mapbox map with markers, pulse animations
- `resources/js/components/DashboardAlertFeed.vue` — Dashboard alert feed panel
- `resources/js/components/CameraRail.vue` — Camera list rail for dashboard
- `resources/js/components/StatusBar.vue` — System health status bar
- `resources/js/components/SeverityBadge.vue` — Severity indicator (red/amber/green) — target for glow effects
- `resources/js/components/AlertFeedItem.vue` — Alert row component
- `resources/js/components/EventHistoryTable.vue` — Event history data grid
- `resources/js/components/EventHistoryFilters.vue` — Event history filter controls

### Prior Phase Context
- `.planning/phases/06-dashboard-map/06-CONTEXT.md` — Dashboard layout decisions (three-panel, full-viewport, status bar, camera rail interactions). Foundation for dashboard refinement.
- `.planning/phases/05-recognition-alerting/05-CONTEXT.md` — Alert feed styling decisions, severity coloring system, alert sound composable.

### Codebase Maps
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns to follow
- `.planning/codebase/STRUCTURE.md` — Directory layout, where to add/modify files

### Project Planning
- `.planning/REQUIREMENTS.md` — Overall project requirements and constraints
- `.planning/ROADMAP.md` — Phase dependencies and success criteria

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `useAppearance.ts`: Dark/light toggle composable — extend to set dark as default, ensure slate/steel blue theme applies correctly
- `SeverityBadge.vue`: Current severity indicator — primary target for glow effect refinement
- `CameraStatusDot.vue`: Online/offline dot — consider glow treatment for consistency
- `MapboxMap.vue` / `DashboardMap.vue`: Map components already have dark/light style toggle — align with new palette
- `AlertFeedItem.vue`: Compact alert rows — redesign with dense data grid principles
- All 24 shadcn-vue UI component directories in `components/ui/` — deep customization targets

### Established Patterns
- CSS custom properties in `app.css` for theming (light/dark modes via `:root` and `.dark` selectors)
- `cn()` utility from `@/lib/utils` for conditional Tailwind class merging
- CVA (class-variance-authority) for component variant styling in shadcn-vue components
- Tailwind v4 with `@theme inline` block for design token mapping
- `tw-animate-css` for animation utilities

### Integration Points
- `resources/css/app.css` — Primary integration point for palette/theme changes
- `resources/views/app.blade.php` — Inline dark mode detection script, font loading
- `vite.config.ts` — May need font plugin adjustments for Inter
- `tailwind.config` (via app.css @theme) — Font family, color token definitions
- Every Vue page and component — visual updates throughout

</code_context>

<specifics>
## Specific Ideas

- Genetec Security Center as the primary visual reference — dark UI, camera grids, alert panels, map-based monitoring interface
- "Security operations center" feel should be visible at a glance — when an operator walks into the room, the screen should look like a monitoring station, not a web app
- Severity glow effects should make critical events visually pop against the dark background — an operator should notice a red glow peripherally
- Dense data grids inspired by log viewers and SIEM tools — event history and camera lists should feel like professional monitoring interfaces
- The gradient approach means admin forms (create/edit camera, create/edit personnel) can breathe a bit more — they don't need to be as dense as the dashboard or event tables

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 08-redesign-of-the-whole-application-using-the-frontend-design-*
*Context gathered: 2026-04-11*

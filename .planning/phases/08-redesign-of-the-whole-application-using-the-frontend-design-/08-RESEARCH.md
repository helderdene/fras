# Phase 8: Redesign of the Whole Application Using the Frontend Design Skill - Research

**Researched:** 2026-04-11
**Domain:** Frontend visual redesign (CSS, Vue components, Tailwind CSS v4, shadcn-vue theming)
**Confidence:** HIGH

## Summary

Phase 8 is a purely visual/structural frontend redesign with no backend changes, no new features, and no new dependencies. The scope is 22 pages across 6 sections, transforming a generic shadcn-vue neutral theme into a security operations center aesthetic with a slate/steel blue palette, glassmorphism effects, glow severity indicators, dense data grids, and Inter font. The UI-SPEC (08-UI-SPEC.md) provides an extremely detailed design contract with exact HSL values, spacing tokens, animation timings, component-level specifications, and CSS custom property mappings.

The technical approach is straightforward: the existing shadcn-vue theming system uses CSS custom properties in `resources/css/app.css` that cascade through all components automatically. The palette change is the foundation -- updating `:root` and `.dark` custom property values propagates the new colors through every shadcn-vue component without touching individual component files. Beyond the palette, targeted component modifications add glow effects, glassmorphism, dense data grid styling, and structural layout changes. The font swap (Instrument Sans to Inter) requires changes in `app.blade.php` (CDN link) and `app.css` (font-family property).

**Primary recommendation:** Execute in a layered sequence: (1) foundation changes (font, palette, app.blade.php background), (2) layout structural rework (AuthCardLayout switch, sidebar glassmorphism, dashboard panel glassmorphism), (3) component-level visual polish (severity glow, dense data grids, status dots), (4) page-by-page styling updates across all 22 pages. This ordering minimizes rework since each layer cascades into subsequent layers.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Security ops center visual identity across the entire application
- **D-02:** Gradient approach for visual intensity -- Dashboard fullest ops treatment, admin pages softer, two visual tiers
- **D-03:** Primary visual reference: Genetec Security Center
- **D-04:** All 22 pages redesigned across all 6 sections
- **D-05:** Layouts reworked structurally, not just restyled
- **D-06:** Welcome page gets ops-styled treatment as dark professional product login portal
- **D-07:** Slate / steel blue palette
- **D-08:** Dark-first design -- dark mode is default experience
- **D-09:** Severity colors with glow effects in dark mode
- **D-10:** Deep customization of shadcn-vue components
- **D-11:** Subtle transitions for motion -- professional, restrained
- **D-12:** Dense data grid style for tables
- **D-13:** Switch primary font from Instrument Sans to Inter

### Claude's Discretion
- Exact glassmorphism intensity and blur values
- Specific glow colors and intensities for severity indicators
- Card vs flat surface decisions per component
- Sidebar navigation rework structure
- Auth layout variant selection (decided: AuthCardLayout)
- Skeleton loading patterns and empty state designs
- Specific Inter font weights to include
- CSS custom property value choices for the slate/steel blue theme
- Responsive breakpoint behavior adjustments
- Icon styling refinements

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

## Project Constraints (from CLAUDE.md)

- **Tailwind CSS v4** -- use `@theme inline` block for design tokens, CSS-first configuration, no `tailwind.config.js` [VERIFIED: app.css uses `@theme inline`]
- **shadcn-vue new-york-v4** with reka-ui primitives -- components in `resources/js/components/ui/` are auto-generated, ESLint-ignored [VERIFIED: components.json]
- **CVA (class-variance-authority)** for component variant styling [VERIFIED: button/index.ts, badge/index.ts]
- **`cn()` utility** from `@/lib/utils` for conditional class merging [VERIFIED: all component files]
- **PHP formatting:** Run `vendor/bin/pint --dirty --format agent` -- not applicable (no PHP changes in this phase)
- **ESLint + Prettier:** Run `npm run lint` and `npm run format` for frontend changes [VERIFIED: eslint.config.js, .prettierrc]
- **Test enforcement:** Every change must be programmatically tested -- visual-only changes need smoke tests [VERIFIED: CLAUDE.md tests rules]
- **Do not create documentation files** unless explicitly requested [VERIFIED: CLAUDE.md]
- **Check sibling files** for structure and naming before creating/editing [VERIFIED: CLAUDE.md conventions]
- **Inter font via Bunny Fonts CDN** -- `fonts.bunny.net/css?family=inter:400,500,600` [VERIFIED: WebFetch confirmed valid CSS response]
- **Skills activation:** `tailwindcss-development` skill MUST be activated for this phase [VERIFIED: CLAUDE.md skill definitions]
- **Inertia SSR enabled** -- dark mode inline script in `app.blade.php` must be preserved for FOUC prevention [VERIFIED: app.blade.php]

## Standard Stack

### Core (already installed -- no new dependencies)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| tailwindcss | v4.1.1 | Utility-first CSS framework | Already installed, CSS-first configuration via `@theme inline` [VERIFIED: package.json] |
| shadcn-vue | new-york-v4 | Component library with CSS variable theming | Already installed, 24 component directories in `components/ui/` [VERIFIED: components.json] |
| reka-ui | v2.6.1 | Headless UI primitives underlying shadcn-vue | Already installed [VERIFIED: package.json] |
| class-variance-authority | v0.7.1 | Component variant management (CVA) | Already installed, used in Button and Badge [VERIFIED: button/index.ts] |
| clsx + tailwind-merge | v2.1.1 + v3.2.0 | Conditional class merging via `cn()` | Already installed [VERIFIED: lib/utils] |
| tw-animate-css | v1.2.5 | Tailwind animation utilities | Already installed [VERIFIED: app.css imports] |
| lucide-vue-next | v0.468.0 | Icon library | Already installed [VERIFIED: component imports] |
| vue-sonner | v2.0.0 | Toast notifications | Already installed [VERIFIED: Toaster usage] |

### Supporting (no additions needed)

No new packages are required. This phase works entirely within the existing stack. The redesign uses CSS custom properties, Tailwind utilities, and Vue template modifications.

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| CSS custom property palette | Tailwind color plugin | Custom properties already cascade through all shadcn-vue components -- using the existing system is zero-friction |
| Glassmorphism via utility classes | CSS-in-JS / separate CSS file | Tailwind's `backdrop-blur-*` and `bg-*/opacity` utilities handle glassmorphism natively |
| Custom transition system | Motion libraries (vue-motion, GSAP) | Overkill for subtle CSS transitions; `transition-*` Tailwind utilities + Vue `<Transition>` are sufficient |

## Architecture Patterns

### File Organization

No new directories are created. All changes happen within existing files.

```
resources/
├── css/
│   └── app.css                    # PRIMARY: palette, font, custom properties, glow effects
├── views/
│   └── app.blade.php              # Font CDN link, inline background colors
└── js/
    ├── app.ts                     # Layout resolver (AuthLayout -> AuthCardLayout)
    ├── layouts/
    │   ├── AuthLayout.vue         # Switch import from AuthSimpleLayout to AuthCardLayout
    │   ├── DashboardLayout.vue    # Minimal (already lean, may not need changes)
    │   ├── app/
    │   │   └── AppSidebarLayout.vue  # Minimal wrapper (changes flow through AppSidebar)
    │   ├── auth/
    │   │   └── AuthCardLayout.vue    # Glassmorphism card treatment
    │   └── settings/
    │       └── Layout.vue            # Settings sidebar nav styling
    ├── components/
    │   ├── SeverityBadge.vue         # Glow effects
    │   ├── CameraStatusDot.vue       # Glow effects
    │   ├── SyncStatusDot.vue         # Glow effects
    │   ├── StatusBar.vue             # Glassmorphism, monospace
    │   ├── AlertFeedItem.vue         # Dense layout, severity glow
    │   ├── EventHistoryTable.vue     # Dense data grid
    │   ├── DashboardAlertFeed.vue    # Glassmorphism panel
    │   ├── CameraRail.vue            # Glassmorphism panel
    │   ├── CameraRailItem.vue        # Dense layout
    │   ├── TodayStats.vue            # Display numbers, accent
    │   ├── DashboardTopNav.vue       # Transparent blur, branding
    │   ├── AppSidebar.vue            # Glassmorphism, nav group labels
    │   ├── AppLogo.vue               # FRAS branding (currently "Laravel Starter Kit")
    │   ├── AppLogoIcon.vue           # Contrast adjustments
    │   ├── Heading.vue               # Font size/weight per spec
    │   ├── ConnectionBanner.vue      # Glow border animation
    │   ├── NavMain.vue               # Active/hover states
    │   ├── NavUser.vue               # Avatar ring, dropdown
    │   └── ... (other components with light touch)
    ├── components/ui/
    │   ├── table/TableHead.vue       # Dense grid header style
    │   ├── table/TableRow.vue        # Dense grid row + alternating
    │   ├── button/index.ts           # Destructive hover glow variant
    │   ├── card/Card.vue             # Subtle border opacity
    │   ├── dialog/DialogOverlay.vue  # Glassmorphism overlay
    │   ├── skeleton/Skeleton.vue     # Ensure bg-muted usage
    │   └── badge/index.ts            # Glow variant support
    └── pages/
        ├── Dashboard.vue             # Tier 1 ops center styling
        ├── Welcome.vue               # Tier 3 dark portal
        ├── cameras/*.vue             # Tier 2 admin
        ├── personnel/*.vue           # Tier 2 admin
        ├── alerts/Index.vue          # Tier 2 admin
        ├── events/Index.vue          # Tier 2 admin
        ├── auth/*.vue                # Tier 3 auth
        └── settings/*.vue            # Tier 4 settings
```

### Pattern 1: CSS Custom Property Cascade for Palette

**What:** Update CSS custom property values in `app.css` `:root` and `.dark` selectors. All shadcn-vue components automatically inherit the new palette through the `var()` references in the `@theme inline` block.

**When to use:** For any palette-wide color change. This is the primary mechanism for theming shadcn-vue.

**Example:**
```css
/* Source: resources/css/app.css (current) -> (new values from UI-SPEC) */
.dark {
    --background: hsl(222 47% 6%);       /* was: hsl(0 0% 3.9%) */
    --foreground: hsl(210 20% 93%);      /* was: hsl(0 0% 98%) */
    --card: hsl(222 47% 9%);             /* was: hsl(0 0% 3.9%) */
    --primary: hsl(217 91% 60%);         /* was: hsl(0 0% 98%) -- BIG change: accent blue */
    --primary-foreground: hsl(0 0% 100%); /* was: hsl(0 0% 9%) */
    /* ... etc. Full mapping in UI-SPEC */
}
```

**Critical consideration:** The `--primary` change from neutral to blue is a significant shift. Currently `--primary` is near-white in dark mode (`hsl(0 0% 98%)`), used for all "default" variant buttons. After the change, `--primary` becomes blue (`hsl(217 91% 60%)`). This means ALL default buttons, badges, and elements using `bg-primary` will become blue. This is intentional per the accent color spec, but every page must be visually verified. [VERIFIED: current values in app.css]

### Pattern 2: Dark-Mode-Only Glow Effects

**What:** Apply `box-shadow` glow effects exclusively within `.dark` selector scope. Use Tailwind's arbitrary shadow values or CSS custom properties.

**When to use:** For SeverityBadge, CameraStatusDot, SyncStatusDot, destructive button hover.

**Example:**
```vue
<!-- Source: UI-SPEC SeverityBadge Glow Enhancement -->
<span
    :class="[
        'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
        severity === 'critical' && 'bg-red-500/90 text-white dark:shadow-[0_0_12px_rgba(239,68,68,0.4)]',
        severity === 'warning' && 'bg-amber-500/90 text-white dark:shadow-[0_0_10px_rgba(245,158,11,0.35)]',
        severity === 'info' && 'bg-emerald-500/90 text-white dark:shadow-[0_0_8px_rgba(16,185,129,0.3)]',
    ]"
>
```

### Pattern 3: Glassmorphism via Tailwind Utilities

**What:** Combine `backdrop-blur-*`, background opacity, and border opacity for frosted glass effect.

**When to use:** Dashboard panels (CameraRail, AlertFeed), sidebar, modals, status bar, auth card.

**Example:**
```vue
<!-- Sidebar glassmorphism (dark mode only) -->
<Sidebar
    class="dark:bg-sidebar/80 dark:backdrop-blur-xl"
    collapsible="icon"
    variant="inset"
>
```

### Pattern 4: Dense Data Grid Table Styling

**What:** Override default shadcn-vue table styles for monitoring-grade density: shorter row height, smaller font, monospace numerics, sticky header, alternating rows.

**When to use:** EventHistoryTable, camera list table, personnel list table.

**Example:**
```vue
<!-- Source: UI-SPEC Dense Data Grid Style -->
<Table class="data-grid">
    <TableHeader class="sticky top-0 z-10 bg-card">
        <TableRow>
            <TableHead class="h-8 px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Column
            </TableHead>
        </TableRow>
    </TableHeader>
    <TableBody>
        <TableRow class="h-10 odd:bg-muted/20 dark:odd:bg-muted/20">
            <TableCell class="px-2 py-1 text-xs">Data</TableCell>
        </TableRow>
    </TableBody>
</Table>
```

### Pattern 5: AuthLayout Variant Switch

**What:** Change the AuthLayout.vue import from AuthSimpleLayout to AuthCardLayout. The existing AuthCardLayout.vue already exists and has the card structure.

**When to use:** One-time layout switch.

**Example:**
```typescript
// resources/js/layouts/AuthLayout.vue
// Change: import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
// To:     import AuthLayout from '@/layouts/auth/AuthCardLayout.vue';
```

### Anti-Patterns to Avoid

- **DO NOT modify shadcn-vue component files unnecessarily:** The palette cascade handles 80% of the work. Only modify individual component files when adding specific behaviors (glow variants, dense grid classes, glassmorphism) that cannot be achieved via CSS custom properties alone. [VERIFIED: shadcn-vue components use `var()` references through `@theme inline`]
- **DO NOT add new CSS files:** All custom CSS belongs in `app.css`. The project has a single CSS entry point. [VERIFIED: vite.config.ts]
- **DO NOT use inline styles for theme values:** Use CSS custom properties or Tailwind utilities. Inline styles bypass the theming system.
- **DO NOT remove existing dark/light mode toggle functionality:** Dark-first does not mean dark-only. Light mode must remain functional. [VERIFIED: UI-SPEC provides both light and dark mode custom properties]
- **DO NOT hardcode HSL values in Vue templates when a CSS custom property exists:** Use `text-primary`, `bg-card`, etc. Only use hardcoded values for one-off effects like glow shadows that have no corresponding custom property.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Color palette | Manual color calculations | UI-SPEC exact HSL values | Pre-calculated, tested, consistent |
| Component variants | Custom CSS classes | CVA (class-variance-authority) | Already used in all shadcn-vue components, provides type-safe variants |
| Class merging | String concatenation | `cn()` utility (clsx + tailwind-merge) | Handles conflicts correctly, already standard in codebase |
| Animations | Custom JS animation logic | Tailwind transition utilities + `tw-animate-css` | Zero-runtime-cost CSS transitions |
| Glassmorphism | Custom CSS blur filter | Tailwind `backdrop-blur-*` utilities | Composable with other utilities |
| Icon sizing | Manual width/height | Tailwind `size-[Npx]` utility | Consistent with codebase pattern |

## Common Pitfalls

### Pitfall 1: Primary Color Semantic Shift Breaks Button Contrast

**What goes wrong:** The `--primary` color changes from near-white to blue in dark mode. Any component that relied on `bg-primary` being near-white (like outlined text that expects white-on-dark) will look wrong.
**Why it happens:** In the current neutral theme, `--primary` in dark mode is `hsl(0 0% 98%)` (white). After the change, it becomes `hsl(217 91% 60%)` (blue). The `--primary-foreground` also changes from near-black to white.
**How to avoid:** After updating palette, visually audit every component that uses `bg-primary`, `text-primary`, `text-primary-foreground`. The default button variant `bg-primary text-primary-foreground` changes from "white button with black text" to "blue button with white text" -- this is intentional and correct per spec.
**Warning signs:** Buttons or text that are invisible or have poor contrast after palette change.

### Pitfall 2: Glassmorphism Performance on Low-End Monitors

**What goes wrong:** `backdrop-blur` is GPU-intensive. Multiple stacked blur layers (sidebar + dashboard panels + modal) can cause jank on older hardware.
**Why it happens:** CSS `backdrop-filter: blur()` requires compositing and can be expensive, especially on the always-on dashboard with real-time updates.
**How to avoid:** Limit the number of simultaneous blurred layers. The UI-SPEC already specifies different blur intensities (sm=4px, md=12px, lg=16px, xl=24px) -- use the minimal necessary. Test on target command center hardware.
**Warning signs:** Choppy scrolling, delayed hover effects, high GPU utilization on the dashboard page.

### Pitfall 3: Glow Box-Shadow Rendering in Different Browsers

**What goes wrong:** Box-shadow glow effects can render slightly differently across browsers (Safari vs Chrome vs Firefox), particularly with `rgba()` transparency.
**Why it happens:** Browser rendering engines handle sub-pixel shadow rendering differently.
**How to avoid:** The glow effects use standard `box-shadow` CSS, which has excellent cross-browser support. Test in target browser (likely Chrome on the command center). The arbitrary value syntax `shadow-[0_0_12px_rgba(239,68,68,0.4)]` is well-supported in Tailwind v4. [ASSUMED: target browser is Chrome-based]
**Warning signs:** Glow appears too bright or invisible in specific browsers.

### Pitfall 4: Font Weight 500 Removal Breaking Existing `font-medium` Usage

**What goes wrong:** The UI-SPEC specifies removing weight 500 (medium) from loading, keeping only 400 and 600. Existing `font-medium` Tailwind classes throughout the codebase will fall back to the browser's font synthesis, which may look different.
**Why it happens:** `font-medium` maps to `font-weight: 500`. Without the 500 weight loaded, the browser synthesizes it by thickening the 400 weight or thinning the 600 weight.
**How to avoid:** Search and replace all `font-medium` occurrences. Per UI-SPEC: remap to `font-semibold` (600) where emphasis is needed, or `font-normal` (400) where it served as body weight. Do this systematically before removing weight 500 from the font load.
**Warning signs:** Text that looks subtly wrong or "fuzzy" after font switch -- sign of synthetic weight.

### Pitfall 5: Welcome Page Has Separate Font Loading

**What goes wrong:** The Welcome page has its own `<link rel="preconnect" href="https://rsms.me/">` and `<link rel="stylesheet" href="https://rsms.me/inter/inter.css">` in its `<Head>` section. If not removed, it loads Inter from two different CDNs.
**Why it happens:** The Welcome page was originally a Laravel starter kit page with its own Inter font loading from rsms.me.
**How to avoid:** Remove the Welcome page's `rsms.me` preconnect and stylesheet link from the `<Head>` section. The font will be loaded globally from `app.blade.php` via Bunny CDN. [VERIFIED: Welcome.vue lines 17-19]
**Warning signs:** Duplicate font loading, FOUT (Flash of Unstyled Text), or network requests to both rsms.me and fonts.bunny.net.

### Pitfall 6: AppLogo Still Says "Laravel Starter Kit"

**What goes wrong:** The sidebar logo text reads "Laravel Starter Kit" -- this should be updated to "FRAS" or "HDS-FRAS" for branding.
**Why it happens:** It was never changed from the starter kit default.
**How to avoid:** Update `AppLogo.vue` to show the correct product name and apply steel blue accent treatment per UI-SPEC. [VERIFIED: AppLogo.vue line 10]
**Warning signs:** Sidebar header showing starter kit branding instead of FRAS.

### Pitfall 7: app.blade.php Inline Background Uses oklch, UI-SPEC Uses hsl

**What goes wrong:** The current `app.blade.php` inline style uses `oklch(1 0 0)` and `oklch(0.145 0 0)` for background colors. The UI-SPEC specifies HSL values: `hsl(220 20% 97%)` and `hsl(222 47% 6%)`.
**Why it happens:** The original starter kit used oklch; the UI-SPEC specifies hsl for consistency with the CSS custom properties.
**How to avoid:** Replace the oklch values in `app.blade.php` with the exact hsl values from the UI-SPEC. Both formats have good browser support, but consistency matters for visual correctness (the inline background should match the `--background` custom property exactly). [VERIFIED: app.blade.php lines 23-28]
**Warning signs:** Brief flash of mismatched background color on page load.

### Pitfall 8: Sidebar `--sidebar` Property Needs Updating Too

**What goes wrong:** The `app.css` has both `--sidebar-background` and `--sidebar` custom properties. Only updating one leaves inconsistency.
**Why it happens:** shadcn-vue sidebar uses `--sidebar` (mapped via `@theme inline` to `--color-sidebar`), while other sidebar properties use `--sidebar-background`, `--sidebar-foreground`, etc.
**How to avoid:** Update ALL sidebar-related custom properties. Note the current CSS has `--sidebar: hsl(240 5.9% 10%)` which is separate from `--sidebar-background: hsl(0 0% 7%)`. Both must be updated to the new values. [VERIFIED: app.css lines 153-163]
**Warning signs:** Sidebar background color doesn't match specification.

## Code Examples

### Font Swap in app.blade.php

```html
<!-- Source: UI-SPEC Font Loading section -->
<!-- BEFORE: -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

<!-- AFTER: -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,600" rel="stylesheet" />
```

Note: Only weights 400 and 600 per UI-SPEC. Weight 500 is intentionally excluded.

### Font Stack in app.css

```css
/* Source: UI-SPEC Font Stack Update section */
@theme inline {
    --font-sans:
        Inter, ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
        'Noto Color Emoji';
}

@layer utilities {
    body, html {
        --font-sans:
            'Inter', ui-sans-serif, system-ui, sans-serif,
            'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
            'Noto Color Emoji';
    }
}
```

### Inline Background in app.blade.php

```html
<!-- Source: UI-SPEC CSS Custom Property Mapping section -->
<style>
    html { background-color: hsl(220 20% 97%); }
    html.dark { background-color: hsl(222 47% 6%); }
</style>
```

### AuthLayout Switch

```vue
<!-- Source: AuthLayout.vue (one-line change) -->
<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthCardLayout.vue';
// was: import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
```

### AuthCardLayout Glassmorphism Enhancement

```vue
<!-- Source: UI-SPEC Auth Layout Decision section -->
<div
    class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
>
    <div class="flex w-full max-w-[400px] flex-col gap-6">
        <!-- Logo above card -->
        <Link :href="home()" class="flex items-center gap-2 self-center">
            <AppLogoIcon class="size-9 fill-current text-primary" />
        </Link>

        <Card class="rounded-xl border-border/50 dark:bg-card/75 dark:backdrop-blur-lg">
            <CardHeader class="px-8 pt-8 pb-0 text-center">
                <CardTitle class="text-xl">{{ title }}</CardTitle>
                <CardDescription>{{ description }}</CardDescription>
            </CardHeader>
            <CardContent class="px-8 py-8">
                <slot />
            </CardContent>
        </Card>
    </div>
</div>
```

### Dense Data Grid Table Head

```vue
<!-- Source: UI-SPEC Dense Data Grid Style -->
<th
    data-slot="table-head"
    :class="cn(
        'h-8 px-2 text-left align-middle text-xs font-semibold uppercase tracking-wider text-muted-foreground whitespace-nowrap bg-muted/50 dark:bg-muted/30 [&:has([role=checkbox])]:pr-0',
        props.class
    )"
>
    <slot />
</th>
```

### SeverityBadge with Glow

```vue
<!-- Source: UI-SPEC SeverityBadge Glow Enhancement -->
<script setup lang="ts">
import { computed } from 'vue';
import type { AlertSeverity } from '@/types';

const props = defineProps<{ severity: AlertSeverity }>();

const classes = computed(() => {
    switch (props.severity) {
        case 'critical':
            return 'bg-red-500/90 text-white dark:shadow-[0_0_12px_rgba(239,68,68,0.4)]';
        case 'warning':
            return 'bg-amber-500/90 text-white dark:shadow-[0_0_10px_rgba(245,158,11,0.35)]';
        case 'info':
            return 'bg-emerald-500/90 text-white dark:shadow-[0_0_8px_rgba(16,185,129,0.3)]';
        default:
            return 'bg-muted text-muted-foreground';
    }
});
</script>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Neutral gray palette (`hsl(0 0% X%)`) | Slate/steel blue palette (`hsl(222 47% X%)`) | This phase | All pages change from flat gray to cool-toned steel blue |
| `font-medium` (weight 500) | `font-semibold` (600) or `font-normal` (400) | This phase | Sharper weight contrast, better hierarchy |
| Instrument Sans font | Inter font | This phase | Better small-size legibility for data-dense UIs |
| Flat severity badges | Glow severity badges (dark mode) | This phase | Critical events visually pop against dark background |
| Standard table styling | Dense data grid styling | This phase | Professional monitoring UI, maximum data density |
| AuthSimpleLayout | AuthCardLayout with glassmorphism | This phase | Secure terminal portal feel |

## Assumptions Log

> List of all claims tagged [ASSUMED] in this research.

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Target browser is Chrome-based on command center monitors | Pitfall 3 | Glow effects might render differently; need to test in actual target browser |

## Open Questions

1. **AppLogo branding text**
   - What we know: Currently says "Laravel Starter Kit" in AppLogo.vue
   - What's unclear: Should it say "FRAS", "HDS-FRAS", or something else?
   - Recommendation: Use "FRAS" (consistent with DashboardTopNav which already shows "FRAS")

2. **Inertia progress bar color**
   - What we know: Currently `#4B5563` (gray-600) in `app.ts` progress config
   - What's unclear: Should it match the new accent blue (`hsl(217 91% 60%)` = approximately `#3B82F6`)?
   - Recommendation: Update to accent blue for consistency with new palette

3. **Welcome page complete rewrite vs restyle**
   - What we know: The Welcome page is the default Laravel starter kit page with hardcoded colors, starter kit documentation links, and separate font loading. It needs to become an ops portal.
   - What's unclear: How much of the existing content to preserve vs replace
   - Recommendation: Complete visual rewrite. Remove starter kit content, create dark portal with FRAS branding and login/register navigation per D-06

4. **Camera marker dark mode border color**
   - What we know: Currently uses hardcoded `#171717` (neutral-900) for dark mode marker border
   - What's unclear: Should this use the new palette's dark background or stay as-is?
   - Recommendation: Update to match new palette's border color (`hsl(222 20% 16%)`)

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest v4 (PHP feature tests for page rendering) |
| Config file | `phpunit.xml` / `tests/Pest.php` |
| Quick run command | `php artisan test --compact --filter=TestName` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements -> Test Map

This phase has no specific requirement IDs (TBD). The primary validation is:

| Behavior | Test Type | Automated Command | File Exists? |
|----------|-----------|-------------------|-------------|
| All 22 pages render without errors | smoke | `php artisan test --compact --filter=smoke` | Needs Wave 0 |
| Auth pages render with new layout | feature | `php artisan test --compact --filter=Auth` | Existing (7 tests) |
| Settings pages render | feature | `php artisan test --compact --filter=Settings` | Existing (2 tests) |
| Dashboard renders | feature | `php artisan test --compact --filter=Dashboard` | Existing (1 test) |
| Camera pages render | feature | `php artisan test --compact --filter=Camera` | Existing |
| ESLint passes | lint | `npm run lint:check` | Config exists |
| TypeScript compiles | type-check | `npx vue-tsc --noEmit` | tsconfig exists |
| Prettier passes | format | `npm run format:check` | Config exists |

### Sampling Rate
- **Per task commit:** `php artisan test --compact` + `npm run lint:check` + `npm run format:check`
- **Per wave merge:** Full suite: `php artisan test --compact` + `npx vue-tsc --noEmit`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] Smoke test for all 22 pages rendering without errors (optional -- existing feature tests cover page rendering)
- Framework install: Not needed -- Pest already installed and configured

## Security Domain

This phase involves only visual/structural frontend changes. No new data flows, no new API endpoints, no authentication changes, no input handling changes.

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | No | No auth logic changes |
| V3 Session Management | No | No session changes |
| V4 Access Control | No | No access control changes |
| V5 Input Validation | No | No new inputs |
| V6 Cryptography | No | No crypto changes |

### Known Threat Patterns

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via style injection | Tampering | All styling uses Tailwind utilities and CSS custom properties, no dynamic style binding from user input |

No security concerns for this phase. All changes are visual/presentational.

## Sources

### Primary (HIGH confidence)
- `resources/css/app.css` -- Verified current CSS custom property values, font-family declarations, theme structure
- `resources/views/app.blade.php` -- Verified font CDN link, inline dark mode script, background colors
- `components.json` -- Verified shadcn-vue configuration (new-york-v4, neutral, lucide icons)
- `resources/js/layouts/AuthLayout.vue` -- Verified current AuthSimpleLayout import
- `resources/js/layouts/auth/AuthCardLayout.vue` -- Verified existing CardLayout with card structure
- All shadcn-vue UI component files -- Verified CSS custom property usage via `cn()` and CVA
- All page and component Vue files -- Verified current class usage, structure, patterns
- `.planning/phases/08-redesign-of-the-whole-application-using-the-frontend-design-/08-UI-SPEC.md` -- Design contract with exact values
- `.planning/phases/08-redesign-of-the-whole-application-using-the-frontend-design-/08-CONTEXT.md` -- User decisions and canonical references
- Bunny Fonts CDN -- Verified `inter:400,500,600` returns valid CSS via WebFetch

### Secondary (MEDIUM confidence)
- Tailwind CSS v4 documentation patterns -- `@theme inline`, `@custom-variant dark`, utility classes [Referenced via CLAUDE.md tailwindcss-development skill]

### Tertiary (LOW confidence)
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- no new dependencies, all tools verified in codebase
- Architecture: HIGH -- patterns directly observed in existing code, UI-SPEC provides exact specifications
- Pitfalls: HIGH -- identified through codebase audit (font loading, palette semantics, component files)

**Research date:** 2026-04-11
**Valid until:** 2026-05-11 (stable -- no moving targets, purely frontend styling)

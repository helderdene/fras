# Phase 3: Personnel Management - Context

**Gathered:** 2026-04-10
**Status:** Ready for planning

<domain>
## Phase Boundary

Admin can manage a personnel roster with photos that are automatically preprocessed to meet camera enrollment constraints. Delivers Personnel CRUD (create, read, update, delete), photo upload with dropzone and client-side preview, server-side photo preprocessing (resize, compress, MD5 hash), personnel list page, and personnel detail page with enrollment sidebar placeholder. Enrollment sync itself is Phase 4.

</domain>

<decisions>
## Implementation Decisions

### Personnel form fields
- **D-01:** Full form exposing all migration columns: name, custom_id, person_type (allow/block), photo, gender, birthday, id_card, phone, address. Personnel records serve as a complete roster.
- **D-02:** Form organized into grouped sections with labeled headers and visual separators (e.g., "Identity" for name/custom_id/type, "Contact" for phone/address, "Photo" for dropzone). Not a single flat column.

### Photo upload experience
- **D-03:** Drag-and-drop dropzone with dashed border drop area, click-to-browse fallback, image thumbnail preview after selection, and inline help text showing constraints (max 1MB, JPEG/PNG accepted). Matches PERS-06 requirement. This is a new component — no existing dropzone in the codebase.
- **D-04:** On edit, the existing photo displays as the dropzone thumbnail with a "Replace" overlay or small remove button. Dropping/selecting a new file replaces the current photo.
- **D-05:** Client-side validation with instant feedback: reject non-image files and oversized files before form submission. Inline error displayed within the dropzone area. Server validates too, but client catches obvious issues early.

### Personnel list presentation
- **D-06:** Table layout matching the camera list pattern. Columns: avatar thumbnail, name, custom ID, person type (allow/block badge), sync status dot. Consistent with existing camera Index page.
- **D-07:** Client-side search input above the table — filter-as-you-type by name or custom ID. No server round-trip needed for ~200 records.
- **D-08:** Sync status dot shows gray with "Not synced" before Phase 4 enrollment exists. Becomes functional (green/amber/red) when Phase 4 adds enrollment records.

### Detail page layout
- **D-09:** Separate show and edit pages following the camera pattern (Index, Show, Create, Edit). Show page displays read-only info + enrollment sidebar. Edit button navigates to dedicated edit page.
- **D-10:** Enrollment sidebar on show page lists all registered cameras with gray "not synced" status dots beside each. Previews the structure Phase 4 will populate with real enrollment status.
- **D-11:** Large prominent photo display (~200px) at the top of the info section on the show page. This is a face recognition system — the photo is the primary identifier.

### Claude's Discretion
- Form section grouping labels and exact field ordering within sections
- Dropzone component internal implementation (vanilla JS vs lightweight library)
- Photo preprocessing pipeline implementation (Intervention Image v3 on server — resize, compress, MD5)
- Table column widths, responsive behavior, and empty avatar placeholder
- Person type badge styling (colors for allow vs block)
- Delete confirmation dialog text and consequences warning
- PersonnelController structure and Form Request organization
- Factory states and seeder data for development

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — EditPersonsNew payload schema (all personnel fields), photo constraints, custom_id format, person_type values, enrollment protocol that Phase 4 builds on

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 3 requirements: PERS-01 through PERS-08
- `.planning/ROADMAP.md` — Phase 3 success criteria (5 criteria that must be TRUE)

### Configuration
- `config/hds.php` — Photo constraints already defined: `photo.max_dimension` (1080), `photo.max_size_bytes` (1MB), `photo.jpeg_quality` (85)

### Prior Phase Context
- `.planning/phases/01-infrastructure-mqtt-foundation/01-CONTEXT.md` — D-01 MySQL everywhere, D-15 unified config/hds.php
- `.planning/phases/02-camera-management-liveness/02-CONTEXT.md` — Camera CRUD pattern (D-01 through D-04), camera list table layout (D-09 through D-12), camera detail two-column layout (D-13 through D-16)

### Existing Code (patterns to follow)
- `app/Http/Controllers/CameraController.php` — Resource controller pattern to mirror for PersonnelController
- `app/Http/Requests/Camera/` — Form Request pattern to mirror for personnel validation
- `resources/js/pages/cameras/Index.vue` — Table layout with status dots, empty state, real-time updates
- `resources/js/pages/cameras/Show.vue` — Two-column detail page pattern
- `database/migrations/2026_04_10_000002_create_personnel_table.php` — Personnel schema (already migrated)
- `database/migrations/2026_04_10_000004_create_camera_enrollments_table.php` — Enrollment schema referenced by sidebar placeholder

### Codebase Maps
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns
- `.planning/codebase/STRUCTURE.md` — Where to add new code (controllers, pages, tests)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `CameraController.php`: Resource controller pattern — PersonnelController should mirror this structure
- `StoreCameraRequest.php` / `UpdateCameraRequest.php`: Form Request pattern to follow for personnel validation
- `resources/js/components/CameraStatusDot.vue`: Status dot component — can be generalized or a similar `SyncStatusDot` created
- `resources/js/components/ui/avatar/`: Avatar component for personnel photos in list and detail
- `resources/js/components/ui/badge/`: Badge component for allow/block person type indicators
- `resources/js/components/ui/card/`: Card component for form sections and info groups
- `resources/js/components/ui/dialog/`: Confirmation dialog for delete action
- `resources/js/components/ui/select/`: Select component for person_type and gender dropdowns
- `resources/js/components/Heading.vue`: Page heading component used across all pages
- Intervention Image v3: Installed in Phase 1, available for server-side photo preprocessing

### Established Patterns
- Resource routes: `Route::resource('cameras', CameraController::class)` — same for personnel
- Inertia flash toasts: `Inertia::flash('toast', [...])` after mutations
- Wayfinder route generation: Auto-generates typed TS route functions after adding routes
- Page layout auto-assignment: `pages/personnel/*` will get `AppLayout` by default
- Form Requests compose validation from Concerns traits
- `setLayoutProps` for dynamic breadcrumbs referencing props (Phase 2 pattern)

### Integration Points
- `routes/web.php` — Add personnel resource routes
- Sidebar navigation — Add "Personnel" nav item alongside "Cameras"
- `resources/js/types/` — Add Personnel TypeScript type
- `storage/app/` — Personnel photos stored here, served via public URL for camera enrollment (Phase 4)

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches following existing camera CRUD patterns and Laravel conventions.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-personnel-management*
*Context gathered: 2026-04-10*

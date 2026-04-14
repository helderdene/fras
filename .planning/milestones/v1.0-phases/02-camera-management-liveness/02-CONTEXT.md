# Phase 2: Camera Management & Liveness - Context

**Gathered:** 2026-04-10
**Status:** Ready for planning

<domain>
## Phase Boundary

This phase delivers camera CRUD (create, read, update, delete) with a Mapbox-assisted GPS input, implements the MQTT heartbeat and online/offline handler stubs from Phase 1, adds scheduled offline detection, broadcasts camera status changes via Reverb, and builds camera list and detail pages with real-time status updates.

</domain>

<decisions>
## Implementation Decisions

### Camera CRUD Flow
- **D-01:** Dedicated pages for camera create (`/cameras/create`) and edit (`/cameras/{id}/edit`) with breadcrumb navigation back to the camera list. Not modals or sheets.
- **D-02:** GPS coordinate input uses both manual lat/lng decimal fields AND an interactive Mapbox map preview. The map updates as coordinates are typed, and clicking the map sets the coordinate fields. Reusable map component for the detail page.
- **D-03:** Device ID accepts any value — no validation against live cameras. Camera shows as "offline" until the physical device connects and sends heartbeats.
- **D-04:** No extra fields beyond the existing migration schema: device_id, name, location_label, latitude, longitude. Additional metadata (IP, firmware) may come from heartbeat data in future phases.

### Liveness & Offline Detection
- **D-05:** Scheduled Laravel command runs every 30-60 seconds, queries cameras where `last_seen_at` is older than 90 seconds, and marks them `is_online = false`. Uses the Laravel scheduler.
- **D-06:** Camera status changes (online/offline) are broadcast to browsers via Reverb on the `fras.alerts` channel using a `CameraStatusChanged` event. Dashboard and camera list update instantly.
- **D-07:** OnlineOfflineHandler trusts Online messages — immediately marks `is_online = true` and updates `last_seen_at`. Offline messages immediately mark `is_online = false`.
- **D-08:** HeartbeatHandler only updates `last_seen_at` timestamp. Heartbeat is a liveness signal, not a telemetry source. No additional payload extraction.

### Camera List Page
- **D-09:** Table layout with columns: name, device ID, location, status (online/offline badge), last seen. Standard admin data table pattern.
- **D-10:** Real-time updates via Laravel Echo listener — listens for `CameraStatusChanged` events to update status badge and last-seen timestamp without page reload.
- **D-11:** No filtering or search — with at most 8 cameras, filtering is unnecessary complexity.
- **D-12:** "Add camera" primary button positioned top-right in the page header area, next to the page title.

### Camera Detail Page
- **D-13:** Two-column layout: camera info on the left (name, device ID, location, GPS, status, last seen, edit/delete actions), enrolled personnel list on the right.
- **D-14:** Small Mapbox map preview in the info section showing the camera's GPS pin. Reuses the same Mapbox component from the camera form.
- **D-15:** Camera deletion uses a confirmation dialog (existing Dialog UI component) explaining consequences, then redirects to camera list on confirm.
- **D-16:** Enrolled personnel section shows a placeholder empty state message until Phase 4 builds enrollment: "No personnel enrolled on this camera yet."

### Claude's Discretion
- Camera model factory states and seeder data for development
- Route naming conventions for camera routes (follow existing `profile.edit`, `profile.update` patterns)
- Scheduled command frequency within the 30-60 second range
- Table component choice — whether to use an existing UI component or build a simple table
- Mapbox component API design (props, events) for reuse across form and detail page
- Camera form validation rules (required fields, coordinate ranges, device ID format)
- Navigation integration — where cameras appear in the sidebar

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — MQTT topic patterns for heartbeat (`mqtt/face/+/basic`), Online/Offline (`mqtt/face/+/basic`), HeartBeat payload schema, camera device ID format, enrollment protocol references

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 2 requirements: CAM-01 through CAM-06, OPS-04, OPS-05
- `.planning/ROADMAP.md` — Phase 2 success criteria (5 criteria that must be TRUE)

### Prior Phase Context
- `.planning/phases/01-infrastructure-mqtt-foundation/01-CONTEXT.md` — Phase 1 decisions: MQTT architecture (D-05 through D-08), Reverb broadcasting (D-09 through D-11), config/hds.php structure (D-15)

### Existing Codebase
- `.planning/codebase/STRUCTURE.md` — Directory layout and where to add new code
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Mqtt/Handlers/HeartbeatHandler.php` — Stub handler, needs implementation to update camera `last_seen_at`
- `app/Mqtt/Handlers/OnlineOfflineHandler.php` — Stub handler, needs implementation to update camera `is_online`
- `app/Mqtt/TopicRouter.php` — Already routes MQTT messages to handler classes by topic pattern
- `resources/js/components/ui/` — Available UI components: card, button, dialog, input, label, badge, select, separator, skeleton, avatar, alert, breadcrumb, sheet, tooltip
- `resources/js/components/ui/badge/` — For online/offline status indicators
- `resources/js/components/ui/dialog/` — For delete confirmation
- `resources/js/components/ui/card/` — For camera info and empty state sections

### Established Patterns
- Controllers in `app/Http/Controllers/<Domain>/` — camera controller goes in `app/Http/Controllers/Camera/` or `app/Http/Controllers/`
- Form Requests in `app/Http/Requests/<Domain>/` — for camera create/update validation
- Pages in `resources/js/pages/<feature>/` — camera pages in `resources/js/pages/cameras/`
- Layout auto-assignment in `app.ts` — camera pages get `AppLayout` by default
- Inertia flash toasts for success feedback after mutations

### Integration Points
- `routes/web.php` — Add camera resource routes
- `resources/js/app.ts` — Layout resolver may need a `cameras/*` case if nested layouts needed
- `config/hds.php` — Offline threshold (90 seconds) already configured
- `app/Console/Kernel.php` or `routes/console.php` — Register scheduled offline check command
- Reverb broadcasting — New `CameraStatusChanged` event class
- Laravel Echo — Frontend listener for camera status updates

</code_context>

<specifics>
## Specific Ideas

- Mapbox map component should be designed for reuse: used in camera create/edit form (interactive, clickable) and camera detail page (read-only pin display). Same component with different props.
- The Mapbox map in the form should use HelderDene's custom dark/light styles (matching the user's appearance preference), consistent with the future dashboard map in Phase 6.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-camera-management-liveness*
*Context gathered: 2026-04-10*

# Phase 2: Camera Management & Liveness - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-10
**Phase:** 02-camera-management-liveness
**Areas discussed:** Camera CRUD flow, Liveness & offline detection, Camera list page, Camera detail page

---

## Camera CRUD Flow

| Option | Description | Selected |
|--------|-------------|----------|
| Manual lat/lng fields | Two decimal input fields for latitude and longitude | |
| Map pin drop | Click on a Mapbox map to place a pin | |
| Both options | Manual fields with map preview that updates as typed, and allows clicking | ✓ |

**User's choice:** Both options
**Notes:** Map preview updates as coordinates are typed, clicking map sets the coordinate fields.

| Option | Description | Selected |
|--------|-------------|----------|
| Dedicated page | Full page at /cameras/create and /cameras/{id}/edit with breadcrumb navigation | ✓ |
| Dialog/modal overlay | Open a dialog over the camera list page | |
| Slide-over sheet | Sheet slides in from the right over the camera list | |

**User's choice:** Dedicated page
**Notes:** Consistent with settings pages pattern.

| Option | Description | Selected |
|--------|-------------|----------|
| Accept any ID | Store whatever admin types, camera shows offline until device connects | ✓ |
| Warn but allow | Accept ID but warn if no heartbeat received within X seconds | |
| You decide | Claude picks based on MQTT protocol constraints | |

**User's choice:** Accept any ID
**Notes:** No validation against live cameras.

| Option | Description | Selected |
|--------|-------------|----------|
| No extra fields | Keep minimal: device_id, name, location_label, latitude, longitude | ✓ |
| Add notes/description field | Optional free-text notes for installation details | |
| You decide | Claude picks for 8-camera deployment | |

**User's choice:** No extra fields
**Notes:** Additional metadata can come from heartbeat data later.

---

## Liveness & Offline Detection

| Option | Description | Selected |
|--------|-------------|----------|
| Scheduled check | Laravel command runs every 30-60s, marks offline when last_seen_at > 90s | ✓ |
| Event-driven timeout | Schedule delayed job on heartbeat, fires if no new heartbeat | |
| You decide | Claude picks best approach for Laravel scheduler | |

**User's choice:** Scheduled check
**Notes:** Simple, reliable, uses existing scheduler.

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, via Reverb | Broadcast CameraStatusChanged event when is_online changes | ✓ |
| No, poll on page load | Status refreshes only on navigation | |
| You decide | Claude decides based on Reverb setup | |

**User's choice:** Yes, via Reverb
**Notes:** Dashboard and camera list update instantly without page reload.

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, trust Online message | Mark is_online=true and update last_seen_at immediately | ✓ |
| Wait for first heartbeat | Only mark online after Online message AND subsequent heartbeat | |

**User's choice:** Yes, trust Online message
**Notes:** Simple, consistent with camera firmware behavior.

| Option | Description | Selected |
|--------|-------------|----------|
| Only update last_seen_at | Heartbeat just updates timestamp, minimal approach | ✓ |
| Extract available metadata | Parse heartbeat for IP, channel count, etc. | |
| You decide | Claude checks spec for useful fields | |

**User's choice:** Only update last_seen_at
**Notes:** Heartbeat is a liveness signal, not telemetry. Additional metadata can be added later.

---

## Camera List Page

| Option | Description | Selected |
|--------|-------------|----------|
| Table | Data table with columns: name, device ID, location, status badge, last seen | ✓ |
| Card grid | Cards with camera info and small map thumbnail | |
| You decide | Claude picks for 8-camera admin | |

**User's choice:** Table
**Notes:** Standard admin CRUD pattern, sortable columns.

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, via Echo listener | Listen for CameraStatusChanged events, update without reload | ✓ |
| Inertia polling | Refresh page data every 30 seconds | |
| You decide | Claude picks based on Reverb/Echo setup | |

**User's choice:** Yes, via Echo listener
**Notes:** Leverages the Reverb broadcast decided in liveness area.

| Option | Description | Selected |
|--------|-------------|----------|
| No filtering | With 8 cameras, filtering is unnecessary | ✓ |
| Status filter only | Toggle All / Online / Offline | |
| You decide | Claude decides for 8-camera constraint | |

**User's choice:** No filtering
**Notes:** Add filtering later if deployment grows.

| Option | Description | Selected |
|--------|-------------|----------|
| Top-right of page | Primary button in page header area, next to title | ✓ |
| Above table | Button directly above table, aligned right | |
| You decide | Claude follows existing button patterns | |

**User's choice:** Top-right of page
**Notes:** Standard admin pattern.

---

## Camera Detail Page

| Option | Description | Selected |
|--------|-------------|----------|
| Two-column: info left, enrollments right | Camera properties on left, enrolled personnel on right | ✓ |
| Single column with sections | Full-width stacked sections | |
| You decide | Claude matches existing settings page patterns | |

**User's choice:** Two-column
**Notes:** Matches the personnel detail page layout planned for Phase 3 (PERS-05).

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, small map preview | Compact Mapbox map showing camera pin | ✓ |
| No map on detail | Just show GPS as text, dashboard handles spatial visualization | |
| You decide | Claude decides based on Mapbox component reuse | |

**User's choice:** Yes, small map preview
**Notes:** Reuses same Mapbox setup from the camera form. Gives spatial context.

| Option | Description | Selected |
|--------|-------------|----------|
| Confirm dialog | Click Delete → confirmation dialog → redirect to list | ✓ |
| Type camera name to confirm | Destructive confirmation requiring name input | |
| You decide | Claude follows existing delete patterns | |

**User's choice:** Confirm dialog
**Notes:** Uses existing Dialog UI component.

| Option | Description | Selected |
|--------|-------------|----------|
| Placeholder message | Empty state card with informative message | ✓ |
| Hide section entirely | Don't show enrollments until Phase 4 | |
| You decide | Claude decides for two-column balance | |

**User's choice:** Placeholder message
**Notes:** "No personnel enrolled on this camera yet." Keeps two-column layout balanced.

---

## Claude's Discretion

- Camera model factory states and seeder data
- Route naming conventions
- Scheduled command frequency within 30-60s range
- Table component choice
- Mapbox component API design
- Camera form validation rules
- Navigation integration

## Deferred Ideas

None — discussion stayed within phase scope

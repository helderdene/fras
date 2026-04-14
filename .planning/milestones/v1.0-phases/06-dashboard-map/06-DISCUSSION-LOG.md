# Phase 6: Dashboard & Map - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-11
**Phase:** 06-dashboard-map
**Areas discussed:** Three-panel layout, Camera marker interactions, Status bar & system health, Left rail content & stats

---

## Three-Panel Layout

| Option | Description | Selected |
|--------|-------------|----------|
| Fixed sidebar widths | Left ~280px, right ~360px, map fills center. Toggle open/close. | ✓ |
| Resizable with drag handles | Drag borders to resize. Persist in localStorage. | |
| Map-dominant with overlays | Full-viewport map with floating overlay panels. | |

**User's choice:** Fixed sidebar widths
**Notes:** Clean command center feel, consistent sizing.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Own full-viewport layout | Dedicated layout without app sidebar. Max screen real estate. | ✓ |
| Inside AppLayout sidebar | Render inside existing AppLayout. Consistent nav but less room. | |

**User's choice:** Own full-viewport layout
**Notes:** Maximum screen real estate for command center.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, make it the home page | Replace Dashboard.vue placeholder. Core value of the system. | ✓ |
| Separate route | Dashboard at /command-center. Keep current Dashboard.vue. | |

**User's choice:** Yes, make it the home page
**Notes:** Operators land directly on command center after login.

---

## Camera Marker Interactions

| Option | Description | Selected |
|--------|-------------|----------|
| Popup with camera summary | Mapbox popup with name, status, last seen, recognition count. | ✓ |
| Highlight in left rail | Scroll and highlight camera in left rail list. | |
| Navigate to camera detail | Leave command center, go to /cameras/{id}. | |

**User's choice:** Popup with camera summary

---

| Option | Description | Selected |
|--------|-------------|----------|
| Expanding ring that fades | Red circle expands outward, fades over ~3s. Radar/sonar effect. | ✓ |
| Marker glow and bounce | Marker glows red and bounces briefly. Simpler. | |
| Ripple rings (multiple) | 2-3 concentric rings expand in sequence. More dramatic. | |

**User's choice:** Expanding ring that fades

---

| Option | Description | Selected |
|--------|-------------|----------|
| Online/offline only | Green = online, gray = offline. Pulse handles severity. | ✓ |
| Severity-tinted after events | Marker temporarily tints by last event severity. | |

**User's choice:** Online/offline only

---

## Status Bar & System Health

| Option | Description | Selected |
|--------|-------------|----------|
| Top bar, next to settings | Sun/moon icon in top nav. Switches map + app theme together. | ✓ |
| Floating on the map | Small toggle on map corner. Map-only control. | |
| Status bar | Toggle in bottom status bar. | |

**User's choice:** Top bar, next to settings

---

| Option | Description | Selected |
|--------|-------------|----------|
| Status bar dots + subtle banner | Green/red dots + amber banner on disconnect. Auto-dismiss on reconnect. | ✓ |
| Status bar dots only | Dots only, no banner. | |
| Modal alert on disconnect | Full modal warning. Attention-grabbing but disruptive. | |

**User's choice:** Status bar dots + subtle banner

---

| Option | Description | Selected |
|--------|-------------|----------|
| MQTT + Reverb + queue depth | Three indicators per DASH-05. Minimal and functional. | ✓ |
| Add camera online count | Same + "4/6 cameras online" counter. | |

**User's choice:** MQTT + Reverb + queue depth

---

## Left Rail Content & Stats

| Option | Description | Selected |
|--------|-------------|----------|
| Compact rows with status dot | Status dot, name, recognition count badge. Click pans map. | ✓ |
| Cards with thumbnails | Small cards with mini preview, name, status, counts. | |
| Grouped by status | Two sections: Online and Offline. | |

**User's choice:** Compact rows with status dot

---

| Option | Description | Selected |
|--------|-------------|----------|
| 4 key metrics | Total, critical, warnings, enrolled. 2x2 grid. | ✓ |
| Expanded with trends | 4 metrics + sparkline chart. More complex. | |
| Minimal: just critical count | Single prominent number. | |

**User's choice:** 4 key metrics

---

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, filter alert feed | Click camera filters alert feed to that camera. Click again to clear. | ✓ |
| No, keep feed global | Alert feed always shows all cameras. | |

**User's choice:** Yes, filter alert feed

## Claude's Discretion

- Mapbox popup HTML structure and styling
- Expanding ring animation implementation approach
- Status bar component structure and health check mechanism
- Queue depth API endpoint design
- "Today" stats query optimization
- Left rail scroll behavior
- Map resize handling on panel toggle

## Deferred Ideas

None

# Phase 7: Event History & Operations - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-11
**Phase:** 07-event-history-operations
**Areas discussed:** Event history table design, Filter & search UX, Retention job behavior

---

## Event History Table Design

| Option | Description | Selected |
|--------|-------------|----------|
| Data table with columns | Standard table with sortable columns: face crop thumbnail, person name, camera, severity badge, similarity %, timestamp | ✓ |
| Compact card rows | Reuse AlertFeedItem pattern from Phase 5 alert feed | |
| Hybrid — table with expandable rows | Table for scanning, clicking a row expands inline | |

**User's choice:** Data table with columns
**Notes:** Familiar pattern for searching through records, good for scanning large datasets.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Standard page pagination | Classic numbered pagination (25-50 per page), URL-bookmarkable | ✓ |
| Infinite scroll with load more | Scroll to bottom loads next batch | |
| Cursor-based with date jumping | Load by pages with date picker jump | |

**User's choice:** Standard page pagination
**Notes:** Works naturally with server-side filtering.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Reuse AlertDetailModal | Same modal with face crop, scene image overlay, metadata, ack/dismiss buttons | ✓ |
| Inline row expansion | Expand table row to show images and metadata without modal | |
| Dedicated detail page | Navigate to full page for each event | |

**User's choice:** Reuse AlertDetailModal
**Notes:** Consistent experience across alerts and history.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Subtle visual marker | Show replay events with small 'replay' badge or muted styling | ✓ |
| Mix in without distinction | Show all events identically | |
| Separate tab or toggle | Default view shows only real-time; toggle reveals replay events | |

**User's choice:** Subtle visual marker
**Notes:** Operators can distinguish real-time from replay during investigation.

---

## Filter & Search UX

| Option | Description | Selected |
|--------|-------------|----------|
| Horizontal filter bar above table | Inline row of filter controls above the table | ✓ |
| Collapsible sidebar filters | Left sidebar with stacked filter sections | |
| Popover filter panel | Single 'Filters' button opening a popover | |

**User's choice:** Horizontal filter bar above table
**Notes:** Compact, always visible, matches alert feed filter pill pattern.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Server-side with Inertia visits | Filters update URL query params, trigger Inertia page visits | ✓ |
| Client-side filtering | Load large batch and filter in-browser | |

**User's choice:** Server-side with Inertia visits
**Notes:** Scales with large datasets, bookmarkable URLs.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Today | Default to today's events | ✓ |
| Last 7 days | Wider default window for investigation | |
| No default — show all | Load most recent without date constraint | |

**User's choice:** Today
**Notes:** Most common use case for operators checking recent activity.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Both name and custom ID | Single search field matches against both | ✓ |
| Name only | Search only by person name | |
| Separate fields | Separate search inputs for name and custom ID | |

**User's choice:** Both name and custom ID
**Notes:** Operators may know either identifier.

---

## Retention Job Behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Daily at 2 AM | Run once daily during low-activity hours | ✓ |
| Hourly | Run every hour for gradual cleanup | |
| Weekly | Run once a week | |

**User's choice:** Daily at 2 AM
**Notes:** Simple, predictable, minimal impact on operations.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Set to null | Update image path columns to null after file deletion | ✓ |
| Keep original path | Leave path string, image serving returns 404 | |
| Add a 'purged' flag column | Boolean flag marking purged events | |

**User's choice:** Set to null
**Notes:** Existing image URL accessors already return null for null paths — UI gracefully shows placeholder.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Log summary only | Log summary line to Laravel log | ✓ |
| Log + flash toast | Log and store flash notification for operator | |
| Silent — no output | Delete files quietly with no audit trail | |

**User's choice:** Log summary only
**Notes:** Reviewable via Pail or log files. No operator notification needed.

---

## Claude's Discretion

- Data table component choice (shadcn-vue Table or custom)
- Date range picker implementation
- Camera dropdown population
- Pagination count per page
- Table column sorting
- Retention job chunking strategy
- Image file deletion approach
- Empty state design

## Deferred Ideas

None — discussion stayed within phase scope

---
status: complete
phase: 07-event-history-operations
source: [07-01-SUMMARY.md, 07-02-SUMMARY.md, 07-VERIFICATION.md]
started: 2026-04-11T12:30:00Z
updated: 2026-04-11T12:30:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Event History Page Loads
expected: Navigate to /events (or click "Event History" in sidebar). The page loads showing a data table of recognition events with columns: face crop thumbnail, person name, camera, severity badge, similarity %, and timestamp. Default date filter is "Today". Pagination shows at the bottom.
result: pass

### 2. Navigation Placement
expected: "Event History" appears in both the sidebar and header navigation, positioned after "Live Alerts".
result: pass

### 3. Filter Bar — Date Range
expected: The filter bar above the table has date range inputs. Changing the date range triggers an Inertia visit — URL updates with query params and the table shows only events within that range.
result: pass

### 4. Filter Bar — Camera Dropdown
expected: A camera dropdown in the filter bar lists all cameras. Selecting a camera filters the table to only events from that camera.
result: pass

### 5. Filter Bar — Person Search
expected: Typing in the person search field (after a brief debounce) filters events matching the person's name or custom ID. The search fires a single request after you stop typing, not per keystroke.
result: pass

### 6. Filter Bar — Severity Pills
expected: Severity pills (All | Critical | Warning | Info) filter events by severity. Clicking a pill highlights it and narrows results. "All" shows everything.
result: pass

### 7. Table Sorting
expected: Clicking the "Time", "Similarity", or "Severity" column headers sorts the table. An arrow indicator shows sort direction. Clicking again reverses direction.
result: pass

### 8. Row Click Opens Detail Modal
expected: Clicking any event row opens the AlertDetailModal showing the face crop, scene image with bounding box overlay, full metadata, and acknowledge/dismiss buttons.
result: pass

### 9. Replay Event Styling
expected: Replay events (PushType=2) appear in the table with a muted/faded row and a "Replay" badge, visually distinct from real-time events.
result: pass

### 10. Pagination
expected: If more than 25 events exist, numbered pagination appears at the bottom. Clicking a page number loads that page via Inertia visit — URL updates and table content changes.
result: pass

### 11. Retention Command Runs
expected: Running `php artisan fras:cleanup-retention` in the terminal executes the retention cleanup command. It logs a summary like "Retention cleanup: deleted X scene images, Y face crops".
result: pass

### 12. Empty State
expected: With no events matching filters, the table shows "No matching events" (if filters are active) or "No recognition events yet" (if no events exist at all).
result: pass

## Summary

total: 12
passed: 12
issues: 0
pending: 0
skipped: 0
blocked: 0

## Gaps

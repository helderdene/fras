---
status: complete
phase: 06-dashboard-map
source: [06-01-SUMMARY.md, 06-02-SUMMARY.md, 06-03-SUMMARY.md]
started: 2026-04-11T05:50:00Z
updated: 2026-04-11T06:23:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Dashboard loads with full-viewport command center layout
expected: Navigate to /dashboard. The page should display a full-viewport three-panel layout WITHOUT the sidebar navigation from other pages. You should see: a top navigation bar with the FRAS logo, panel toggle buttons, theme toggle, settings link, and user menu dropdown. A status bar at the bottom showing MQTT/Reverb connection dots and queue depth.
result: pass

### 2. Camera markers appear on Mapbox map at correct positions
expected: The center panel shows a Mapbox map. Each registered camera appears as a colored circle marker at its GPS coordinates. Online cameras show green markers, offline cameras show gray markers. The map auto-fits to show all cameras with padding.
result: pass

### 3. Camera marker popup shows details on click
expected: Click a camera marker on the map. A popup appears showing: camera name, online/offline status with dot, last seen timestamp, today's recognition count, and a "View Details" link to the camera detail page.
result: pass

### 4. Theme toggle switches app and map style simultaneously
expected: Click the sun/moon icon in the top nav. The app switches between dark and light mode, AND the Mapbox map simultaneously switches between dark and light map styles. Camera markers remain visible after the style change.
result: pass

### 5. Left rail shows Today statistics panel
expected: The left panel (280px wide) shows a "Today" section at the top with a 2x2 grid displaying: Recognitions count, Critical count (red), Warnings count (amber), and Enrolled count.
result: pass

### 6. Left rail shows camera list with status and counts
expected: Below the Today stats, the left rail shows a scrollable list of cameras. Each camera row has: a status dot (green=online, gray=offline), camera name, and a recognition count badge (if > 0). There is an "All Cameras" option at the top.
result: pass

### 7. Camera click in left rail pans map and filters alert feed
expected: Click a camera in the left rail. The map pans/flies to that camera's location and opens its popup. The right alert feed filters to show only that camera's events, with a "Showing: {camera name}" chip with an X button to clear the filter. Clicking the same camera again deselects it and clears the filter.
result: pass

### 8. Right alert feed shows events with severity filter pills
expected: The right panel (360px wide) shows "Live Alerts" header and filter pills: All, Critical, Warning, Info. Each pill shows a count. Clicking a pill filters the displayed alerts by severity. Alert items show severity coloring (red/amber/green).
result: pass

### 9. Alert detail modal opens on alert click
expected: Click an alert item in the right feed. A detail modal opens showing the face crop image, scene image, person name, camera name, severity, similarity score, and timestamp. Acknowledge and Dismiss buttons are available.
result: pass

### 10. Panel toggle buttons show/hide side panels
expected: Click the left panel toggle button (PanelLeft icon) in the top nav. The left camera rail hides. Click again to show it. Same for the right panel toggle (PanelRight icon). The map resizes to fill available space when panels are toggled.
result: pass

### 11. Connection banner appears on Reverb disconnect
expected: If the Reverb WebSocket disconnects (you can test by stopping Reverb), an amber banner appears below the top nav saying "Real-time connection lost. Alerts may be delayed." It auto-dismisses when the connection is restored.
result: pass

### 12. Pulse ring animation on recognition event
expected: When a recognition event fires, the corresponding camera marker on the map shows 3 staggered red expanding ring animations (1.5s apart, ~4.5s total). Critical events play a chime on each pulse. Multiple events on the same camera produce overlapping rings.
result: pass

## Summary

total: 12
passed: 12
issues: 0
pending: 0
skipped: 0
blocked: 0

## Gaps

[none]

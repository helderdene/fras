---
status: complete
phase: 02-camera-management-liveness
source: [02-01-SUMMARY.md, 02-02-SUMMARY.md, 02-03-SUMMARY.md]
started: 2026-04-10T10:00:00.000Z
updated: 2026-04-10T10:30:00.000Z
---

## Current Test

[testing complete]

## Tests

### 1. Camera List Page
expected: Navigate to /cameras. You should see a table with columns: Name, Device ID, Location, Status, Last Seen. If seeder ran, 4 Butuan City cameras appear. An "Add Camera" button is in the top-right header area. Cameras link appears in the sidebar navigation.
result: pass

### 2. Create Camera with Map
expected: Click "Add Camera". The create form at /cameras/create shows fields: Device ID, Name, Location, Latitude, Longitude. Below the coordinate fields, a Mapbox map renders. Typing coordinates updates the map pin. Clicking the map updates the coordinate fields. Submit creates the camera and redirects to /cameras with a success toast.
result: pass

### 3. Edit Camera
expected: From the camera list, click a camera name to go to its detail page, then click "Edit". The edit form at /cameras/{id}/edit is pre-populated with existing values. The map shows the current pin location. Changing fields and submitting updates the camera with a success toast.
result: pass

### 4. Camera Detail Page
expected: Navigate to a camera's detail page (/cameras/{id}). Two-column layout: left side shows Camera Information card (name, device ID, location, GPS, status, last seen) with a small read-only Mapbox map and Edit/Delete buttons. Right side shows "Enrolled Personnel" card with placeholder message "No personnel enrolled".
result: pass

### 5. Delete Camera
expected: On a camera detail page, click "Delete". A confirmation dialog appears asking to confirm deletion. Clicking "Delete camera" removes the camera and redirects to /cameras with a success toast. Clicking "Keep Camera" dismisses the dialog.
result: pass

### 6. Real-time Camera Status
expected: With the camera list page open, have a camera send an MQTT heartbeat or Online/Offline message. The status dot and "Last Seen" column should update in real time without page reload (via WebSocket/Echo).
result: pass

### 7. Camera Offline Detection
expected: Register a camera and have it send heartbeats. Stop sending heartbeats. After ~90 seconds, the camera should automatically show as "Offline" on the camera list page (the scheduled command marks it offline).
result: pass

## Summary

total: 7
passed: 7
issues: 0
pending: 0
skipped: 0
blocked: 0

## Gaps

[none]

# Requirements: HDS-FRAS

**Defined:** 2026-04-10
**Core Value:** Operators see every matched-face recognition event in real time on a map-based dashboard with severity-classified alerts, so critical events are never missed.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Infrastructure

- [x] **INFRA-01**: Application uses MySQL database for all FRAS data (cameras, personnel, events, enrollments)
- [x] **INFRA-02**: MQTT broker (Mosquitto) is accessible from Laravel and camera subnet
- [x] **INFRA-03**: Laravel Reverb WebSocket server runs and broadcasts events to connected browsers
- [x] **INFRA-04**: Long-running processes (MQTT listener, Reverb, queue worker) are managed by Supervisor with autostart/autorestart
- [x] **INFRA-05**: Development environment orchestrates all processes via concurrently

### Camera Management

- [x] **CAM-01**: Admin can register a camera with device ID, name, location label, and GPS coordinates
- [x] **CAM-02**: Admin can edit and delete camera records
- [x] **CAM-03**: System tracks camera online/offline state via MQTT heartbeat messages
- [x] **CAM-04**: System marks camera offline when heartbeat absent for more than 90 seconds
- [x] **CAM-05**: Camera list page shows all cameras with online/offline state and last seen time
- [x] **CAM-06**: Camera detail page shows camera configuration and list of enrolled personnel

### Map & Dashboard

- [ ] **DASH-01**: Dashboard displays all cameras as markers on a Mapbox GL JS map with GPS positioning
- [ ] **DASH-02**: Camera markers are colored by status: green for online, gray for offline
- [ ] **DASH-03**: When a recognition event fires, the corresponding camera marker pulses with a red expanding ring animation for ~3 seconds
- [ ] **DASH-04**: Dashboard has three-panel layout: camera list rail (left), map (center), alert feed (right)
- [ ] **DASH-05**: Status bar shows MQTT connection status, Reverb WebSocket status, and queue depth
- [ ] **DASH-06**: Map supports toggle between dark and light custom Mapbox Studio styles
- [ ] **DASH-07**: Left rail shows camera list with online/offline indicators and per-camera recognition counts
- [ ] **DASH-08**: Left rail includes "Today" statistics panel (total recognitions, critical events, warnings, enrolled personnel count)

### Personnel Management

- [x] **PERS-01**: Admin can create a personnel record with name, custom ID, person type (allow/block), and photo
- [x] **PERS-02**: Admin can edit personnel details and replace photo
- [x] **PERS-03**: Admin can delete a personnel record (propagates delete to all cameras)
- [x] **PERS-04**: Personnel list page shows all personnel with avatar, name, custom ID, list type, and sync status dot
- [x] **PERS-05**: Personnel detail page shows edit form (left) and per-camera enrollment status sidebar (right)
- [x] **PERS-06**: Photo upload uses a dropzone with client-side preview and displays size constraints as help text
- [x] **PERS-07**: System preprocesses photos: resize to max 1080p, compress to JPEG <1MB, compute MD5 hash
- [x] **PERS-08**: Sync status dot on personnel list summarizes camera enrollment: green (all enrolled), amber (pending), red (failed)

### Enrollment Sync

- [x] **ENRL-01**: Saving a personnel record dispatches enrollment jobs to all cameras via MQTT EditPersonsNew
- [x] **ENRL-02**: Enrollment batches are limited to 1000 entries; larger sets are chunked
- [x] **ENRL-03**: Only one enrollment batch may be in-flight per camera at a time (WithoutOverlapping middleware)
- [x] **ENRL-04**: System correlates EditPersonsNew-Ack responses to pending enrollments via cached message IDs
- [x] **ENRL-05**: Per-camera enrollment status shows enrolled/pending/failed state with last sync time or error message
- [x] **ENRL-06**: Failed enrollments show translated operator-friendly error messages (from camera error codes)
- [x] **ENRL-07**: Admin can retry failed enrollments per camera with a single click
- [x] **ENRL-08**: "Re-sync all" button on personnel detail page forces re-push to all cameras without editing fields
- [x] **ENRL-09**: Deleting a personnel record sends MQTT DeletePersons to all cameras
- [ ] **ENRL-10**: Bulk enrollment status dashboard shows per-camera enrollment counts (X/Y enrolled, Z failed)

### Recognition & Alerting

- [ ] **REC-01**: MQTT listener subscribes to camera recognition topics (mqtt/face/+/Rec) and processes RecPush events
- [ ] **REC-02**: Handler parses RecPush payload with firmware quirk handling (personName/persionName fallback, string-to-int casting, empty customId)
- [ ] **REC-03**: Handler decodes and saves base64 face crop image to storage; scene image saved if present (nullable)
- [ ] **REC-04**: Handler inserts recognition_events row with all fields and full raw payload for forensics
- [ ] **REC-05**: Events are classified into three severity levels: critical (block-list match), warning (refused), info (allowed)
- [ ] **REC-06**: Manual replay events (PushType=2) are stored but not surfaced as alerts
- [ ] **REC-07**: Recognition events are broadcast in real time via Laravel Reverb WebSocket to all connected browsers
- [ ] **REC-08**: Live alert feed shows reverse-chronological events with avatar, person name, camera, severity tag, similarity score, and timestamp
- [ ] **REC-09**: Critical alerts have red left border and subtle red background; warnings use amber; info uses green
- [ ] **REC-10**: Clicking an alert opens a detail modal with face crop, scene image with bounding box overlay, and full event metadata
- [ ] **REC-11**: Critical (block-list) events trigger an audible alert sound in the browser
- [ ] **REC-12**: Each alert displays the confidence/similarity score from the camera
- [ ] **REC-13**: Operator can acknowledge or dismiss an alert, recording who handled it and when

### Event History

- [ ] **HIST-01**: Event history page shows a searchable, filterable log of all recognition events
- [ ] **HIST-02**: Filters include date range, camera, person, severity level
- [ ] **HIST-03**: Each event row shows face crop thumbnail, person name, camera, severity, similarity, and timestamp

### Operations

- [ ] **OPS-01**: Scheduled job deletes scene images older than 30 days while keeping recognition_events row and face crop
- [ ] **OPS-02**: Scheduled job deletes face crops older than 90 days while keeping recognition_events row
- [ ] **OPS-03**: Retention windows are configurable in config/hds.php
- [x] **OPS-04**: MQTT listener handles Online/Offline messages to update camera is_online state
- [x] **OPS-05**: MQTT listener handles HeartBeat messages to update camera last_seen_at

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Operational Hardening (v1.1)

- **HARD-01**: Continuous transmission ACK loop (PushAck) for reliable event delivery during listener downtime
- **HARD-02**: Stranger detection alerts on Snap topic with admin-approved enrollment workflow
- **HARD-03**: Bulk personnel import via CSV/Excel with per-row validation preview
- **HARD-04**: Audit log of admin actions
- **HARD-05**: Recognition event grouping/dedup within configurable time window
- **HARD-06**: Offline camera email/push notification after configurable threshold
- **HARD-07**: Quick person search navigation from alert detail to personnel profile

### Multi-Site (v2)

- **SITE-01**: Multi-site support with site-scoped cameras and personnel
- **SITE-02**: Per-site operator accounts and role-based access control
- **SITE-03**: Camera grouping for selective personnel enrollment
- **SITE-04**: Temporary visitor passes with auto-expiry

### Expanded AI Events (v2+)

- **AI-01**: FireSmokeSnapPush -- smoke and fire detection
- **AI-02**: ClothHelmetSnapPush -- PPE compliance monitoring
- **AI-03**: BehaviorSnapPush -- tripwire and area intrusion
- **AI-04**: CatAttrSnapPush -- vehicle attribute capture

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Live video streaming | Different infrastructure (RTSP/WebRTC), massive bandwidth, cameras communicate via MQTT not video |
| Server-side face recognition | System leverages on-camera AI; server-side processing would change architecture fundamentally |
| Automated response actions (door locks) | Safety liability; false positive lockouts are dangerous; stay in alerting lane |
| Demographic analytics (age/gender) | Legal minefield (GDPR, Philippine Data Privacy Act); unreliable algorithms with documented bias |
| Multi-factor biometric auth | System is surveillance/alerting, not access control; different hardware |
| Real-time video forensic search | Requires video storage/indexing and server-side recognition; different architecture |
| Mobile native application | Web is mobile-responsive; native build is scope expansion for v1 |
| MQTT TLS (mqtts://) | Plain MQTT on internal trusted network only for v1 |
| Multiple user roles | Single admin user sufficient for single-site v1 deployment |
| Encrypted at-rest storage | Not required for internal network v1 deployment |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| INFRA-01 | Phase 1 | Complete |
| INFRA-02 | Phase 1 | Complete |
| INFRA-03 | Phase 1 | Complete |
| INFRA-04 | Phase 1 | Complete |
| INFRA-05 | Phase 1 | Complete |
| CAM-01 | Phase 2 | Complete |
| CAM-02 | Phase 2 | Complete |
| CAM-03 | Phase 2 | Complete |
| CAM-04 | Phase 2 | Complete |
| CAM-05 | Phase 2 | Complete |
| CAM-06 | Phase 2 | Complete |
| DASH-01 | Phase 6 | Pending |
| DASH-02 | Phase 6 | Pending |
| DASH-03 | Phase 6 | Pending |
| DASH-04 | Phase 6 | Pending |
| DASH-05 | Phase 6 | Pending |
| DASH-06 | Phase 6 | Pending |
| DASH-07 | Phase 6 | Pending |
| DASH-08 | Phase 6 | Pending |
| PERS-01 | Phase 3 | Complete |
| PERS-02 | Phase 3 | Complete |
| PERS-03 | Phase 3 | Complete |
| PERS-04 | Phase 3 | Complete |
| PERS-05 | Phase 3 | Complete |
| PERS-06 | Phase 3 | Complete |
| PERS-07 | Phase 3 | Complete |
| PERS-08 | Phase 3 | Complete |
| ENRL-01 | Phase 4 | Complete |
| ENRL-02 | Phase 4 | Complete |
| ENRL-03 | Phase 4 | Complete |
| ENRL-04 | Phase 4 | Complete |
| ENRL-05 | Phase 4 | Complete |
| ENRL-06 | Phase 4 | Complete |
| ENRL-07 | Phase 4 | Complete |
| ENRL-08 | Phase 4 | Complete |
| ENRL-09 | Phase 4 | Complete |
| ENRL-10 | Phase 4 | Pending |
| REC-01 | Phase 5 | Pending |
| REC-02 | Phase 5 | Pending |
| REC-03 | Phase 5 | Pending |
| REC-04 | Phase 5 | Pending |
| REC-05 | Phase 5 | Pending |
| REC-06 | Phase 5 | Pending |
| REC-07 | Phase 5 | Pending |
| REC-08 | Phase 5 | Pending |
| REC-09 | Phase 5 | Pending |
| REC-10 | Phase 5 | Pending |
| REC-11 | Phase 5 | Pending |
| REC-12 | Phase 5 | Pending |
| REC-13 | Phase 5 | Pending |
| HIST-01 | Phase 7 | Pending |
| HIST-02 | Phase 7 | Pending |
| HIST-03 | Phase 7 | Pending |
| OPS-01 | Phase 7 | Pending |
| OPS-02 | Phase 7 | Pending |
| OPS-03 | Phase 7 | Pending |
| OPS-04 | Phase 2 | Complete |
| OPS-05 | Phase 2 | Complete |

**Coverage:**
- v1 requirements: 58 total
- Mapped to phases: 58
- Unmapped: 0

---
*Requirements defined: 2026-04-10*
*Last updated: 2026-04-10 after roadmap creation*

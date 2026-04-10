# Roadmap: HDS-FRAS

## Overview

This roadmap delivers a Face Recognition Alert System from infrastructure through real-time operations. The build order follows the data flow: infrastructure and MQTT foundation first, then camera management (the physical endpoints), personnel data (the subjects), enrollment sync (connecting subjects to endpoints), recognition processing (the core event pipeline), the real-time dashboard (the operator interface and core value), and finally event history with operational maintenance. Each phase delivers a complete, testable capability that the next phase builds on.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Infrastructure & MQTT Foundation** - MySQL, MQTT broker, Reverb, Supervisor, and dev orchestration (completed 2026-04-10)
- [x] **Phase 2: Camera Management & Liveness** - Camera CRUD, MQTT listener with heartbeat/online-offline handlers, camera pages (completed 2026-04-10)
- [ ] **Phase 3: Personnel Management** - Personnel CRUD with photo upload, preprocessing, list and detail pages
- [ ] **Phase 4: Enrollment Sync** - Push personnel to cameras via MQTT, ACK correlation, retry, delete sync
- [ ] **Phase 5: Recognition & Alerting** - RecPush processing, event classification, real-time broadcast, alert feed UI
- [ ] **Phase 6: Dashboard & Map** - Mapbox map with camera markers, three-panel layout, status bar, live animations
- [ ] **Phase 7: Event History & Operations** - Searchable event log, storage retention cleanup, configurable retention

## Phase Details

### Phase 1: Infrastructure & MQTT Foundation
**Goal**: All foundational services (MySQL, MQTT, Reverb, queues) are running and the development environment can orchestrate them with a single command
**Depends on**: Nothing (first phase)
**Requirements**: INFRA-01, INFRA-02, INFRA-03, INFRA-04, INFRA-05
**Success Criteria** (what must be TRUE):
  1. Application connects to MySQL and all FRAS migrations run successfully (cameras, personnel, enrollments, recognition_events tables exist)
  2. MQTT broker accepts connections from the Laravel server and test publishes/subscribes work
  3. Laravel Reverb WebSocket server starts and a browser client can connect and receive a test broadcast
  4. Supervisor config starts and auto-restarts MQTT listener, Reverb, and queue worker processes
  5. Running the dev command starts all processes (Vite, queue, Reverb, MQTT listener) concurrently
**Plans**: 3 plans

Plans:
- [x] 01-01-PLAN.md -- Packages, MySQL switch, 4 FRAS migrations, config/hds.php
- [x] 01-02-PLAN.md -- MQTT listener command, topic router, handlers, Supervisor configs, dev script
- [x] 01-03-PLAN.md -- Reverb broadcasting, Echo client, fras.alerts channel, broadcast test

### Phase 2: Camera Management & Liveness
**Goal**: Admin can register and manage cameras, and the system tracks each camera's online/offline state in real time via MQTT
**Depends on**: Phase 1
**Requirements**: CAM-01, CAM-02, CAM-03, CAM-04, CAM-05, CAM-06, OPS-04, OPS-05
**Success Criteria** (what must be TRUE):
  1. Admin can create, edit, and delete cameras with device ID, name, location, and GPS coordinates
  2. Camera list page shows all cameras with online/offline indicator and last-seen timestamp
  3. When a camera sends an MQTT heartbeat, its last_seen_at updates within seconds; when heartbeat is absent for >90 seconds, the camera shows as offline
  4. Camera detail page displays camera configuration and a list of personnel enrolled on that camera
  5. MQTT listener processes Online/Offline messages and HeartBeat messages to maintain camera state
**Plans**: 3 plans
**UI hint**: yes

Plans:
- [x] 02-01-PLAN.md -- Camera model, factory, seeder, CameraController CRUD, form requests, routes, CameraStatusChanged event
- [x] 02-02-PLAN.md -- HeartbeatHandler, OnlineOfflineHandler implementations, CheckOfflineCamerasCommand, scheduler
- [x] 02-03-PLAN.md -- Mapbox GL JS, TypeScript types, MapboxMap component, 4 camera pages, sidebar nav, real-time Echo

### Phase 3: Personnel Management
**Goal**: Admin can manage a personnel roster with photos that are automatically preprocessed to meet camera enrollment constraints
**Depends on**: Phase 1
**Requirements**: PERS-01, PERS-02, PERS-03, PERS-04, PERS-05, PERS-06, PERS-07, PERS-08
**Success Criteria** (what must be TRUE):
  1. Admin can create, edit, and delete personnel records with name, custom ID, person type (allow/block), and photo
  2. Photo upload uses a dropzone with client-side preview and displays size constraint help text
  3. Uploaded photos are automatically resized to max 1080p, compressed to JPEG under 1MB, and have MD5 hash computed
  4. Personnel list shows avatar, name, custom ID, list type, and sync status dot (green/amber/red)
  5. Personnel detail page shows edit form on the left and per-camera enrollment status sidebar on the right
**Plans**: 3 plans
**UI hint**: yes

Plans:
- [x] 03-01-PLAN.md -- Personnel model, factory, seeder, PersonnelController CRUD, form requests, PhotoProcessor service, routes, tests
- [x] 03-02-PLAN.md -- SyncStatusDot component, sidebar nav update, Index and Show Vue pages
- [ ] 03-03-PLAN.md -- PhotoDropzone component, Create and Edit Vue pages with grouped form sections

### Phase 4: Enrollment Sync
**Goal**: Personnel records are automatically pushed to all cameras via MQTT with reliable ACK tracking, retry capability, and delete propagation
**Depends on**: Phase 2, Phase 3
**Requirements**: ENRL-01, ENRL-02, ENRL-03, ENRL-04, ENRL-05, ENRL-06, ENRL-07, ENRL-08, ENRL-09, ENRL-10
**Success Criteria** (what must be TRUE):
  1. Saving a personnel record dispatches enrollment to all cameras; enrollment status transitions from pending to enrolled on ACK success
  2. Only one enrollment batch is in-flight per camera, and batches larger than 1000 entries are chunked
  3. Failed enrollments display operator-friendly error messages and admin can retry with a single click
  4. Deleting a personnel record sends MQTT DeletePersons to all cameras and removes per-camera enrollment records
  5. Bulk enrollment status dashboard shows per-camera counts (X/Y enrolled, Z failed) and admin can force re-sync all
**Plans**: TBD
**UI hint**: yes

Plans:
- [ ] 04-01: TBD
- [ ] 04-02: TBD
- [ ] 04-03: TBD
- [ ] 04-04: TBD

### Phase 5: Recognition & Alerting
**Goal**: The system processes face recognition events from cameras in real time, classifies them by severity, broadcasts them to browsers, and presents a live alert feed with audio notifications for critical events
**Depends on**: Phase 1, Phase 2
**Requirements**: REC-01, REC-02, REC-03, REC-04, REC-05, REC-06, REC-07, REC-08, REC-09, REC-10, REC-11, REC-12, REC-13
**Success Criteria** (what must be TRUE):
  1. MQTT listener receives RecPush events, parses them with firmware quirk handling, saves face/scene images, and inserts recognition_events rows
  2. Events are classified as critical (block-list), warning (refused), or info (allowed), and manual replay events are stored but not surfaced as alerts
  3. Recognition events broadcast via Reverb WebSocket and appear in the browser alert feed within seconds of camera capture
  4. Alert feed shows reverse-chronological events with severity coloring (red/amber/green), and clicking an alert opens a detail modal with face crop, scene image, and full metadata
  5. Critical (block-list) events trigger an audible browser alert sound, and operators can acknowledge or dismiss alerts
**Plans**: TBD
**UI hint**: yes

Plans:
- [ ] 05-01: TBD
- [ ] 05-02: TBD
- [ ] 05-03: TBD
- [ ] 05-04: TBD

### Phase 6: Dashboard & Map
**Goal**: Operators have a full-viewport command center with a live map showing camera positions, real-time marker animations on recognition events, and at-a-glance system status
**Depends on**: Phase 2, Phase 5
**Requirements**: DASH-01, DASH-02, DASH-03, DASH-04, DASH-05, DASH-06, DASH-07, DASH-08
**Success Criteria** (what must be TRUE):
  1. Dashboard displays a three-panel layout: camera list rail (left), Mapbox GL JS map (center), live alert feed (right)
  2. Camera markers are positioned by GPS coordinates and colored by status (green online, gray offline)
  3. When a recognition event fires, the corresponding camera marker pulses with a red expanding ring animation for approximately 3 seconds
  4. Status bar shows MQTT connection status, Reverb WebSocket status, and queue depth; map toggle switches between dark and light Mapbox Studio styles
  5. Left rail shows camera list with online/offline indicators, per-camera recognition counts, and a "Today" statistics panel
**Plans**: TBD
**UI hint**: yes

Plans:
- [ ] 06-01: TBD
- [ ] 06-02: TBD
- [ ] 06-03: TBD

### Phase 7: Event History & Operations
**Goal**: Operators can search and filter past recognition events, and the system automatically manages storage growth through scheduled retention cleanup
**Depends on**: Phase 5
**Requirements**: HIST-01, HIST-02, HIST-03, OPS-01, OPS-02, OPS-03
**Success Criteria** (what must be TRUE):
  1. Event history page shows a searchable log of all recognition events with face crop thumbnail, person name, camera, severity, similarity, and timestamp
  2. Filters for date range, camera, person, and severity level narrow results correctly
  3. Scheduled job deletes scene images older than 30 days and face crops older than 90 days while preserving recognition_events rows
  4. Retention windows are configurable in config/hds.php and changes take effect on the next scheduled run
**Plans**: TBD
**UI hint**: yes

Plans:
- [ ] 07-01: TBD
- [ ] 07-02: TBD
- [ ] 07-03: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7
Note: Phases 2 and 3 share only a Phase 1 dependency and could theoretically overlap, but sequential execution is simpler for a solo workflow.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Infrastructure & MQTT Foundation | 3/3 | Complete | 2026-04-10 |
| 2. Camera Management & Liveness | 3/3 | Complete | 2026-04-10 |
| 3. Personnel Management | 0/3 | Not started | - |
| 4. Enrollment Sync | 0/4 | Not started | - |
| 5. Recognition & Alerting | 0/4 | Not started | - |
| 6. Dashboard & Map | 0/3 | Not started | - |
| 7. Event History & Operations | 0/3 | Not started | - |

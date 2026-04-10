# Feature Research

**Domain:** Face Recognition Alert/Monitoring System with MQTT Camera Integration
**Researched:** 2026-04-10
**Confidence:** HIGH

## Feature Landscape

### Table Stakes (Users Expect These)

Features operators assume exist. Missing these means the system is unusable for its core purpose: monitoring a facility and never missing a critical recognition event.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Camera registration and management | Operators need to add/remove cameras, assign names and locations. Without this there is nothing to monitor. | LOW | CRUD for cameras with device ID, name, GPS coords. Already scoped in PROJECT.md. |
| Camera liveness/health status | Operators must know instantly if a camera goes offline. A dead camera with no indicator is a silent security gap. | MEDIUM | Heartbeat monitoring via MQTT, 90-second timeout threshold. Already scoped. |
| Map-based camera view | Spatial awareness is fundamental for facility monitoring. Operators think in physical space, not lists. | MEDIUM | Mapbox GL JS with camera pins, status coloring (online/offline/alerting). Already scoped. |
| Personnel CRUD with photo enrollment | The core database of known individuals. Without this, there is nothing to recognize. | MEDIUM | Create/edit/delete with photo upload, custom ID, person type (allow/block). Already scoped. |
| Enrollment sync to cameras | Personnel must be pushed to camera firmware for on-device recognition. The system is useless if cameras do not have the face database. | HIGH | MQTT EditPersonsNew with per-camera ACK tracking. Batch limit of 1000. One batch in-flight per camera. Most complex table-stakes feature. Already scoped. |
| Per-camera enrollment status | Operators must know if a person is actually enrolled on each camera, not just "sent." Failed enrollments mean unrecognized personnel. | MEDIUM | Track success/failure per camera_enrollment row, show dots in personnel admin. Already scoped. |
| Retry failed enrollments | Enrollment failures happen (network issues, camera restarts). Operators need a one-click retry, not a workaround. | LOW | Re-queue EditPersonsNew for failed camera_enrollments. Already scoped. |
| Real-time recognition event feed | The primary value of the system. Events must appear within seconds of occurrence. Delays defeat the purpose. | HIGH | MQTT listener -> RecPush parsing -> Laravel Reverb broadcast -> Vue live feed. Already scoped. |
| Alert severity classification | Not all events are equal. A blocked person entering is critical; a recognized employee is informational. Operators need visual triage. | LOW | Three tiers: critical (block-list), warning (refused), info (normal allowed). Already scoped. |
| Audio alert for critical events | Operators may not be staring at the screen. Audible notification for block-list matches prevents missed critical events. Every surveillance system has this. | LOW | Play sound on critical severity. Already scoped. |
| Alert detail view | Operators need to see who was recognized, where, when, with the face crop and scene image. This is how they decide to act. | MEDIUM | Modal with face crop, scene image, person metadata, camera info, timestamp. Already scoped. |
| Recognition event history | Operators and investigators need to search past events. "Who was seen at Camera 3 yesterday?" is a daily question. | MEDIUM | Searchable, filterable log page with date range, camera, person, severity filters. Already scoped. |
| Photo preprocessing | Camera firmware rejects photos exceeding 1MB or 1080p. Preprocessing is invisible to the operator but essential for enrollment to work. | LOW | Resize to 1080p max, compress to <1MB JPEG, MD5 hash. Already scoped. |
| Storage retention policy | Without cleanup, disk fills in weeks with scene/face images. Automated retention is expected infrastructure. | LOW | Scheduled job: scene images after 30 days, face crops after 90 days. Already scoped. |
| Camera offline detection | Operators need proactive notification when a camera drops, not just a stale status indicator. | LOW | Mark offline when heartbeat absent >90 seconds. Already scoped. |

### Differentiators (Competitive Advantage)

Features that elevate this from "basic MQTT camera viewer" to a genuinely useful command center. Not required for v1 launch, but high value.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Map marker animation on recognition | Camera pins pulse/flash when a recognition event fires at that location. Gives operators instant spatial awareness without reading the feed. | LOW | CSS animation on map marker triggered by WebSocket event. Already scoped. Cheap to build, high perceived value. |
| Dashboard three-panel layout (rail + map + feed) | Full-viewport command-center layout keeps all critical info visible simultaneously. Most competitor systems make you switch between views. | MEDIUM | Camera list rail, map center, alert feed rail, status bar. Already scoped. Needs careful responsive design. |
| Dark/light map style toggle | Operators working night shifts or in dark rooms need a dark UI. Map style must match app theme. Reduces eye strain during long monitoring sessions. | LOW | Mapbox style switching tied to existing dark/light theme. Already scoped (custom HelderDene styles exist). |
| Person type classification (allow/block) | Distinct from just "enrolled" -- enables the critical/warning/info alert tiers that make the system actionable rather than just a log viewer. | LOW | Already covered in personnel CRUD. The classification drives alert severity. |
| Alert acknowledge/dismiss workflow | Operators mark events as "seen" or "false alarm" so the next shift knows what has been handled. Prevents duplicate response. | MEDIUM | Not in current scope. Adds acknowledged_at, acknowledged_by, dismissed_as columns to recognition_events. Requires simple UI controls on alert cards. Recommend for v1. |
| Bulk enrollment status dashboard | A single view showing "Camera X: 195/200 enrolled, 5 failed" across all cameras. Lets operators verify the system is fully operational at a glance. | LOW | Aggregate query on camera_enrollments table. Not currently scoped but trivial to add. Recommend for v1. |
| Recognition event grouping/dedup | Same person walking past a camera generates multiple events in seconds. Grouping consecutive events for the same person on the same camera reduces noise. | MEDIUM | Group events within a configurable time window (e.g., 30 seconds). Not currently scoped. Recommend for v1.1 as it requires careful threshold tuning. |
| Threshold/confidence display | Show the match confidence score from the camera alongside each recognition event. Helps operators assess reliability of matches. | LOW | Camera already sends similarity score in RecPush. Just display it. Not currently scoped. Recommend for v1. |
| Offline camera alert escalation | Beyond just marking cameras offline: send push notification or email when a camera stays offline beyond a configurable threshold. | LOW | Scheduled check + notification. Not currently scoped. Useful for unattended monitoring. |
| Quick person search from alert | When an alert fires, operator can click the person to see their full profile, enrollment status across cameras, and recent sighting history. | LOW | Link from alert detail modal to personnel detail page with pre-filtered event history. Not currently scoped. Natural navigation flow. |

### Anti-Features (Commonly Requested, Often Problematic)

Features that seem useful but create complexity, legal exposure, or operational problems disproportionate to their value -- especially for a v1 single-site system.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Stranger/unknown person detection | "Alert me when someone not in the database enters." Sounds like complete security. | Generates massive alert volume. Every delivery driver, visitor, passerby triggers an alert. Operators get alert fatigue within hours and start ignoring everything, including real threats. Camera firmware Snap topic events are noisy. | Keep as out-of-scope (v1.1). When added, require explicit "stranger detection zones" limited to specific cameras, with aggressive deduplication. |
| Live video streaming in dashboard | "I want to see the camera feed in the browser." Natural request for a surveillance system. | Completely different infrastructure (RTSP->WebRTC/HLS transcoding), massive bandwidth, latency challenges, and the cameras communicate via MQTT not video streaming protocols. The cameras do face recognition on-device; the system's value is the alert layer, not being a VMS. | Show face crop and scene snapshot from recognition events. For live video, operators use the camera vendor's native viewer or a dedicated VMS. |
| Automated response actions | "Block the door when a blocked person is detected." Attractive for access control. | Safety liability is extreme. False positive locks out legitimate personnel. Requires hardware integration (door controllers) outside MQTT camera scope. One wrong lock-out in a fire scenario is catastrophic. | Alert the human operator who makes the decision. Provide clear severity indicators and audio alerts so response is fast but human-verified. |
| Demographic analytics (age, gender) | "Tell me the demographics of people passing through." Marketing teams request this. | Legal minefield (GDPR, Philippine Data Privacy Act). Biometric demographic profiling without consent is increasingly regulated. Also unreliable -- algorithms have documented bias by race and gender. | If business intelligence is needed later, aggregate anonymized foot-traffic counts, not biometric profiling. |
| Multi-factor biometric auth | "Combine face with fingerprint/card for higher security." Standard in access control. | This system is surveillance/alerting, not access control. Cameras do recognition, not authentication. Adding fingerprint readers changes the entire hardware architecture. | Stay in the alerting lane. If access control is needed, integrate with a dedicated access control system that receives alerts from this system. |
| Real-time video forensic search | "Search all camera footage for a specific face." Powerful investigative tool. | Requires storing and indexing all video (enormous storage), server-side face recognition (not just camera-side), and complex search infrastructure. Fundamentally different architecture. | Provide recognition event history search (already scoped). For forensic video search, use dedicated NVR/VMS tools. |
| Complex role-based access control | "Different operators see different cameras, different alert levels." Enterprise feature. | Massive complexity for a single-site, single-admin v1. Permission matrices multiply testing surface. Every feature needs permission checks. | Single admin for v1 (already decided). Add RBAC in v2 when multi-site is introduced and the permission model is actually needed. |
| Bulk CSV/Excel personnel import | "Upload 200 people from a spreadsheet." Sounds like a time-saver. | Photo handling is the hard part -- CSV cannot embed photos usefully. Operators still need to upload photos individually. Partial import failures create confusing state. Error handling for 200 rows is complex. | Defer to v1.1 (already decided). For v1, the personnel list is <200 people, manageable through the UI. When added, use a wizard with per-row validation preview. |

## Feature Dependencies

```
[Camera Registration]
    |
    +--requires--> [Camera Liveness/Heartbeat Monitoring]
    |                  |
    |                  +--enables--> [Camera Offline Detection & Alerts]
    |
    +--requires--> [Map-Based Camera View]
    |                  |
    |                  +--enhanced-by--> [Map Marker Animation on Events]
    |
    +--requires--> [MQTT Listener Service]
                       |
                       +--enables--> [RecPush Event Processing]
                       |                 |
                       |                 +--enables--> [Alert Classification]
                       |                 |                 |
                       |                 |                 +--enables--> [Real-Time Broadcast to Browser]
                       |                 |                                   |
                       |                 |                                   +--enables--> [Live Alert Feed]
                       |                 |                                   |                 |
                       |                 |                                   |                 +--enhanced-by--> [Audio Alerts]
                       |                 |                                   |                 +--enhanced-by--> [Alert Acknowledge/Dismiss]
                       |                 |                                   |
                       |                 |                                   +--enables--> [Map Marker Animation]
                       |                 |
                       |                 +--enables--> [Recognition Event History]
                       |
                       +--enables--> [Enrollment ACK Handling]

[Personnel CRUD]
    |
    +--requires--> [Photo Preprocessing]
    |
    +--enables--> [Enrollment Sync to Cameras]
    |                 |
    |                 +--requires--> [MQTT Listener] (for ACK handling)
    |                 |
    |                 +--enables--> [Per-Camera Enrollment Status]
    |                 |                 |
    |                 |                 +--enables--> [Retry Failed Enrollments]
    |                 |                 +--enables--> [Bulk Enrollment Status Dashboard]
    |                 |
    |                 +--enables--> [Delete Sync]
    |
    +--enhanced-by--> [Quick Person Search from Alert]

[Storage Retention Policy] -- independent, runs on schedule
[Dashboard Layout] -- requires [Map View] + [Alert Feed] + [Camera List]
```

### Dependency Notes

- **Camera Registration required first:** Everything depends on cameras being registered -- you cannot monitor, receive events, or enroll personnel without cameras.
- **MQTT Listener is the backbone:** The long-running artisan command that subscribes to MQTT topics enables both event processing AND enrollment ACK handling. Must be rock-solid and supervised.
- **Personnel CRUD before Enrollment:** Cannot sync faces to cameras without the personnel database and photos.
- **Photo Preprocessing before Enrollment Sync:** Camera firmware rejects photos exceeding size limits. Preprocessing must happen transparently during photo upload.
- **Real-Time Broadcast requires both MQTT Listener and Reverb:** Events flow MQTT -> Laravel -> Reverb -> Browser. Both infrastructure pieces must be running.
- **Alert Classification enables everything downstream:** The severity tier determines audio alerts, feed coloring, and future acknowledge/dismiss workflows.
- **Storage Retention is independent:** Runs as a scheduled job regardless of other features. But must be in place before going live or disk fills.

## MVP Definition

### Launch With (v1)

Minimum viable product -- the system must deliver its core value: "operators see every matched-face recognition event in real time on a map-based dashboard with severity-classified alerts."

- [ ] Camera registration with device ID, name, location, GPS coordinates -- foundation for everything
- [ ] Camera liveness via MQTT heartbeat with offline detection at 90 seconds -- operators must trust camera status
- [ ] Map view with camera pins and online/offline/alerting status indicators -- spatial awareness is the primary interface
- [ ] Personnel CRUD with photo upload, custom ID, allow/block classification -- the face database
- [ ] Photo preprocessing (resize, compress, hash) -- invisible but required for enrollment to work
- [ ] Enrollment sync via MQTT EditPersonsNew with per-camera ACK tracking -- faces must reach cameras
- [ ] Per-camera enrollment status with retry for failures -- operators need enrollment confidence
- [ ] MQTT listener service (long-running, supervised artisan command) -- the event backbone
- [ ] RecPush event processing with face/scene image storage -- turning MQTT messages into usable data
- [ ] Alert classification (critical/warning/info) -- triage is the core value proposition
- [ ] Real-time broadcast via Laravel Reverb WebSocket -- events must reach browsers in seconds
- [ ] Dashboard layout (camera rail + map + alert feed + status bar) -- the command center
- [ ] Map marker animation on recognition events -- high perceived value, low cost
- [ ] Live alert feed with severity coloring and detail modal -- how operators consume events
- [ ] Audio alert on critical (block-list) events -- prevents missed critical events
- [ ] Recognition event history with search and filters -- investigation support
- [ ] Delete sync via MQTT DeletePersons -- personnel removal must propagate to cameras
- [ ] Storage retention (scene 30 days, face crops 90 days) -- operational necessity
- [ ] Dark/light map style toggle -- already have custom styles, trivial to wire up

### Add After Validation (v1.1)

Features to add once the core system is proven in operation.

- [ ] Alert acknowledge/dismiss workflow -- trigger: operators asking "did anyone handle this alert?"
- [ ] Bulk enrollment status dashboard -- trigger: operators wanting a single "system health" view
- [ ] Confidence score display on events -- trigger: operators questioning match reliability
- [ ] Recognition event grouping/dedup -- trigger: operators complaining about duplicate alerts for same person walking past
- [ ] Stranger detection (Snap topic) -- trigger: security team wants unknown person alerts after validating that alert volume is manageable in controlled zones
- [ ] Bulk personnel import via CSV -- trigger: onboarding a large batch of new personnel
- [ ] Audit logs of admin actions -- trigger: accountability requirements emerge
- [ ] Offline camera email/push notification -- trigger: facility has unattended monitoring periods
- [ ] Quick person search from alert detail -- trigger: operators frequently navigating between alerts and personnel profiles

### Future Consideration (v2+)

Features to defer until single-site is validated and multi-site or advanced use cases are needed.

- [ ] Multi-site / multi-tenant deployment -- requires architectural changes to scoping and data isolation
- [ ] Role-based access control -- needed when multiple operators with different responsibilities exist
- [ ] Behavioral analytics (tripwire, area intrusion, smoke/fire, PPE) -- different camera event types, different UI
- [ ] Temporary visitor passes with auto-expiry -- access control feature, not surveillance
- [ ] VMS integration (Genetec, Milestone) -- needed when system must work alongside dedicated video management
- [ ] Mobile native application -- web is mobile-responsive for v1; native app for push notifications and offline access later
- [ ] API for third-party integrations -- when other systems need to consume recognition events programmatically

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Camera registration + management | HIGH | LOW | P1 |
| Camera liveness/heartbeat | HIGH | MEDIUM | P1 |
| Map-based camera view | HIGH | MEDIUM | P1 |
| Personnel CRUD with photos | HIGH | MEDIUM | P1 |
| Photo preprocessing | HIGH (invisible) | LOW | P1 |
| Enrollment sync + ACK handling | HIGH | HIGH | P1 |
| Per-camera enrollment status + retry | HIGH | MEDIUM | P1 |
| MQTT listener service | HIGH | HIGH | P1 |
| RecPush event processing | HIGH | MEDIUM | P1 |
| Alert classification | HIGH | LOW | P1 |
| Real-time broadcast (Reverb) | HIGH | MEDIUM | P1 |
| Dashboard three-panel layout | HIGH | MEDIUM | P1 |
| Map marker animation | MEDIUM | LOW | P1 |
| Live alert feed + detail modal | HIGH | MEDIUM | P1 |
| Audio alert (critical) | HIGH | LOW | P1 |
| Event history + search/filter | HIGH | MEDIUM | P1 |
| Delete sync | HIGH | LOW | P1 |
| Storage retention | MEDIUM | LOW | P1 |
| Dark/light map toggle | MEDIUM | LOW | P1 |
| Alert acknowledge/dismiss | MEDIUM | MEDIUM | P2 |
| Bulk enrollment dashboard | MEDIUM | LOW | P2 |
| Confidence score display | LOW | LOW | P2 |
| Event grouping/dedup | MEDIUM | MEDIUM | P2 |
| Stranger detection (Snap) | MEDIUM | HIGH | P2 |
| Bulk CSV import | LOW | MEDIUM | P3 |
| RBAC | LOW (v1) | HIGH | P3 |
| Multi-site | LOW (v1) | HIGH | P3 |
| Behavioral analytics | LOW (v1) | HIGH | P3 |

**Priority key:**
- P1: Must have for launch -- the system does not deliver its core value without these
- P2: Should have, add in v1.1 -- improves operator experience once core is working
- P3: Nice to have, future consideration -- requires validated need or architectural expansion

## Competitor Feature Analysis

| Feature | SmartFace (Innovatrics) | FaceMe (CyberLink) | EyesOnIt | HDS-FRAS (Our Approach) |
|---------|------------------------|---------------------|----------|------------------------|
| Face recognition engine | Server-side + edge sync | Server-side SDK | Server-side | On-camera (firmware). No server-side processing needed. Simpler architecture. |
| Watchlist/personnel management | REST API + web UI, multi-watchlist | SDK API, VMS integration | Gallery import, group assignment | Web UI with per-camera enrollment tracking via MQTT. Direct visibility into sync status. |
| Camera integration | RTSP streams + edge MQTT sync | VMS plugin (Genetec, Milestone) | VMS plugin (Genetec) | Native MQTT protocol direct to AI cameras. No VMS middleware required. |
| Real-time alerting | Webhook + gRPC notifications | VMS-integrated alerts | VMS-integrated alerts | WebSocket (Reverb) push to browser with severity tiers and audio. Lower latency path. |
| Map view | Third-party VMS maps | VMS maps | VMS maps | Built-in Mapbox GL JS with custom styles, animated markers. First-class map experience. |
| Event history | REST API query | VMS event log | VMS forensic search | In-app searchable/filterable log with face crops. Self-contained, no VMS dependency. |
| Deployment complexity | Docker + DB + MQTT broker | SDK integration into custom app | VMS plugin install | Single Laravel server + Mosquitto + MySQL + Reverb. Simplest deployment of all. |
| Video streaming | Yes (RTSP processing) | Yes (via VMS) | Yes (via VMS) | No (deliberate). Face crops + scene images from events only. Stay in alerting lane. |
| Pricing model | Per-camera license | Per-camera SDK license | Enterprise license | Self-hosted, no per-camera fees. Cost advantage for small deployments. |

**Key competitive insight:** Most competitors are either (a) full server-side recognition platforms that process video streams, or (b) plugins for enterprise VMS systems. HDS-FRAS occupies a different niche: a lightweight web dashboard that leverages on-camera AI via MQTT. This means simpler deployment, lower infrastructure cost, and no per-camera licensing -- but it depends entirely on the camera firmware's recognition capability and cannot add server-side recognition features.

## Sources

- [SmartFace Watchlist Documentation](https://developers.innovatrics.com/smartface/docs/manuals/smartface-platform/watchlists/) - MEDIUM confidence (official vendor docs)
- [SmartFace Enrollment Guide](https://developers.innovatrics.com/smartface/docs/guides/enrollment/) - MEDIUM confidence (official vendor docs)
- [EyesOnIt Face Recognition for Surveillance](https://eyesonit.us/eyesonit-face-recognition-for-surveillance/) - MEDIUM confidence (vendor marketing)
- [FaceMe Security Overview](https://www.cyberlink.com/faceme/solution/security/overview) - MEDIUM confidence (vendor marketing)
- [Synology Face Recognition](https://www.synology.com/en-global/vms/solution/surveillance_face_recognition) - MEDIUM confidence (vendor docs)
- [AvidBeam Face Recognition Technology Guide](https://www.avidbeam.com/face-recognition-technology-ultimate-guide/) - LOW confidence (industry overview)
- [Facial Recognition Challenges - AIMultiple](https://research.aimultiple.com/facial-recognition-challenges/) - MEDIUM confidence (industry analysis)
- [SureView Command Center Map Interface](https://facilityexecutive.com/sureview-command-center-map-interface/) - LOW confidence (product marketing)
- [Coram AI Retail Facial Recognition](https://www.coram.ai/post/retail-facial-recognition-security-systems) - MEDIUM confidence (practical implementation guide)

---
*Feature research for: Face Recognition Alert System with MQTT Camera Integration*
*Researched: 2026-04-10*

# Phase 5: Recognition & Alerting - Context

**Gathered:** 2026-04-11
**Status:** Ready for planning

<domain>
## Phase Boundary

The system processes face recognition events (RecPush) from cameras in real time via MQTT, classifies them by severity, saves face/scene images, broadcasts them to browsers via Laravel Reverb, and presents a live alert feed with severity coloring, detail modals, audio notifications for critical events, and operator acknowledge/dismiss capability.

</domain>

<decisions>
## Implementation Decisions

### Alert Feed Design
- **D-01:** Compact list rows — dense rows with small face crop thumbnail, person name, camera name, severity tag, similarity score, and relative timestamp. Left border colored by severity (red/amber/green). Fits maximum alerts on screen for command center monitoring.
- **D-02:** New alerts slide in from the top, pushing existing alerts down with a brief highlight flash on the new row.
- **D-03:** Feed capped at ~50 most recent alerts. Older alerts are only accessible via Event History (Phase 7). Keeps the feed performant and focused on current activity.
- **D-04:** Severity toggle filter pills at the top of the feed: All | Critical | Warning | Info. Quick filtering to focus during busy periods.

### Detail Modal & Images
- **D-05:** Side-by-side layout in a wide modal — face crop on the left (~150px), scene image on the right (with bounding box overlay drawn from target_bbox coordinates). Metadata below both images (person name, custom ID, camera, similarity, person type, captured timestamp). Acknowledge/Dismiss buttons at the bottom.
- **D-06:** Date-partitioned image storage: `storage/app/recognition/{YYYY-MM-DD}/faces/` and `storage/app/recognition/{YYYY-MM-DD}/scenes/`. Files named by event ID. Supports Phase 7 retention cleanup by date.
- **D-07:** When scene image is missing (firmware quirk — some cameras don't send it), show a gray placeholder box with "Scene image not available" text. Face crop still displays normally. Modal layout stays the same.

### Audio & Acknowledgment
- **D-08:** Single chime per critical event — short, distinct alert sound (~1-2 seconds) plays once per critical (block-list) event. Multiple rapid events each play their chime. Mute toggle available.
- **D-09:** Explicit "Enable Alert Sound" button (bell icon) in the feed header or status bar. Clicking triggers a user gesture to unlock browser audio. Operator knows audio is active. Reliable approach for browser autoplay restrictions.
- **D-10:** Inline Ack/Dismiss icon buttons appear on hover/focus of each alert row in the feed. Acknowledge records who handled it (current user) and when (timestamp). Dismiss fades the row but keeps it in the feed and DB. Single-click actions for rapid response.

### Event Classification
- **D-11:** Spec-based severity mapping via PHP enum `AlertSeverity`:
  - **Critical** = Block-list match (person_type=1, verify_status=0) — "Known threat recognized"
  - **Warning** = Entry refused (verify_status=2, any person_type) — "Access denied at gate"
  - **Info** = Allow-list match (person_type=0, verify_status=0) — "Known person recognized"
  - **Ignored** = Stranger (verify_status=3) or No-match (verify_status=1) — stored in DB, NOT surfaced in alert feed or broadcast
- **D-12:** PHP enum `AlertSeverity` with a static `fromEvent()` method encapsulating the mapping logic. Type-safe, testable, single source of truth.
- **D-13:** Manual replay events (PushType=2, is_real_time=false) are completely invisible in the live alert feed. Stored in DB but never surfaced as alerts and never trigger audio. Only visible in Event History (Phase 7).

### Claude's Discretion
- RecPush payload parsing implementation details and firmware quirk handling (personName/persionName fallback, string-to-int casting, empty customId)
- Base64 face crop decoding implementation and scene image saving
- RecognitionEvent model relationships, factory, and seeder
- RecognitionAlert broadcast event structure and payload shape
- Alert chime sound file selection and format (MP3/WAV/OGG)
- Bounding box overlay rendering approach on scene images (CSS overlay vs canvas)
- Feed row component structure and hover interaction implementation
- Personnel lookup strategy (match by custom_id to personnel record)
- RecognitionHandler implementation structure (direct vs job dispatch)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — RecPush payload schema, verify_status values (0-3), person_type values (0/1), PushType field (1=real-time, 2=replay), base64 image encoding, target_bbox format, personName/persionName firmware quirk (Appendix C), facesluice_id, similarity field

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 5 requirements: REC-01 through REC-13
- `.planning/ROADMAP.md` — Phase 5 success criteria (5 criteria that must be TRUE)

### Configuration
- `config/hds.php` — Retention windows (scene 30 days, face crops 90 days), alert thresholds, MQTT connection

### Prior Phase Context
- `.planning/phases/01-infrastructure-mqtt-foundation/01-CONTEXT.md` — MQTT architecture (D-05 through D-08), fras.alerts channel (D-09), Echo/Pusher adapter (D-11)
- `.planning/phases/02-camera-management-liveness/02-CONTEXT.md` — CameraStatusChanged broadcast pattern (D-06), OnlineOfflineHandler (D-07)
- `.planning/phases/04-enrollment-sync/04-CONTEXT.md` — EnrollmentStatusChanged broadcast pattern (D-09), AckHandler implementation (D-04)

### Existing Code (patterns to follow and extend)
- `app/Mqtt/Handlers/RecognitionHandler.php` — Stub handler, needs full implementation for RecPush processing
- `app/Mqtt/TopicRouter.php` — Already routes `mqtt/face/+/Rec` to RecognitionHandler
- `app/Events/CameraStatusChanged.php` — Broadcast event pattern to follow for RecognitionAlert
- `app/Events/EnrollmentStatusChanged.php` — Another broadcast pattern reference
- `database/migrations/2026_04_10_000003_create_recognition_events_table.php` — Table schema already migrated
- `app/Models/Camera.php` — Camera model for relationship
- `app/Models/Personnel.php` — Personnel model for matching by custom_id
- `routes/channels.php` — fras.alerts channel authorization
- `resources/js/pages/cameras/Index.vue` — Echo listener pattern for real-time updates
- `resources/js/pages/personnel/Show.vue` — Echo listener pattern for enrollment status

### Codebase Maps
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns
- `.planning/codebase/STRUCTURE.md` — Where to add new code
- `.planning/codebase/ARCHITECTURE.md` — Overall application architecture

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `RecognitionHandler`: Stub handler registered in TopicRouter for `mqtt/face/+/Rec` — needs full implementation
- `CameraStatusChanged` event: Broadcast pattern on `fras.alerts` — follow same structure for `RecognitionAlert`
- `EnrollmentStatusChanged` event: Another broadcast pattern reference
- `resources/js/components/ui/dialog/`: Dialog component for alert detail modal
- `resources/js/components/ui/badge/`: Badge component for severity tags
- `resources/js/components/ui/avatar/`: Avatar component for face crop thumbnails in feed
- `resources/js/components/SyncStatusDot.vue`: Status dot pattern — reference for severity indicator design
- Laravel Echo already configured in frontend — listeners pattern established in camera and personnel pages

### Established Patterns
- MQTT handlers implement `MqttHandler` interface with `handle(string $topic, string $message): void`
- TopicRouter dispatches by topic pattern matching
- Broadcast events implement `ShouldBroadcast`, use `PrivateChannel('fras.alerts')`, define `broadcastWith()`
- Inertia flash toasts for success feedback: `Inertia::flash('toast', [...])`
- Echo listeners on Vue pages: `.private('fras.alerts').listen('.EventName', callback)`
- Date-partitioned storage aligns with retention cleanup pattern

### Integration Points
- `app/Mqtt/Handlers/RecognitionHandler.php` — Full implementation of RecPush processing
- `app/Models/` — New RecognitionEvent model with Camera and Personnel relationships
- `app/Events/` — New RecognitionAlert broadcast event
- `app/Enums/` — New AlertSeverity PHP enum
- `routes/web.php` — New routes for alert feed page, acknowledge/dismiss endpoints
- `resources/js/pages/` — New alert feed page component(s)
- `resources/js/components/` — New AlertFeedItem, AlertDetailModal components
- `storage/app/recognition/` — Date-partitioned image storage
- Migration needed for acknowledge/dismiss fields (acknowledged_by, acknowledged_at, dismissed_at) on recognition_events

</code_context>

<specifics>
## Specific Ideas

- Compact list rows should feel like a security monitoring feed — dense, scannable, severity-first visual hierarchy
- Severity coloring: red left border + subtle red background for critical, amber for warning, green for info (per REC-09)
- Alert sound should be a short, professional chime — not an alarm siren. Think security operations center, not fire alarm.
- Bounding box overlay on scene images uses the target_bbox [x1,y1,x2,y2] coordinates from the camera payload

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 05-recognition-alerting*
*Context gathered: 2026-04-11*

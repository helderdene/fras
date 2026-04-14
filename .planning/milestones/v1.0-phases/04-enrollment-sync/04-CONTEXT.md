# Phase 4: Enrollment Sync - Context

**Gathered:** 2026-04-10
**Status:** Ready for planning

<domain>
## Phase Boundary

Personnel records are automatically pushed to all cameras via MQTT EditPersonsNew with reliable ACK tracking, retry capability, and delete propagation. Delivers: enrollment job dispatching on personnel save, batch chunking, WithoutOverlapping concurrency control, AckHandler implementation with cache-based message ID correlation, timeout detection, per-camera retry, re-sync all, delete sync via MQTT DeletePersons, real-time enrollment status updates via Reverb, and a bulk enrollment summary panel on the personnel index page.

</domain>

<decisions>
## Implementation Decisions

### Enrollment Trigger
- **D-01:** Auto-enroll on every save — creating or updating a personnel record always dispatches enrollment to all online cameras. EditPersonsNew is idempotent (upsert semantics), so redundant pushes are safe.
- **D-02:** Auto-enroll all on new camera — when a new camera is registered, dispatch enrollment jobs for all existing personnel to that camera automatically. Operator doesn't need to remember to sync.
- **D-03:** Skip offline cameras at dispatch time — don't send MQTT to cameras marked `is_online = false`. Create `camera_enrollment` rows with pending status. When camera comes back online (via OnlineOfflineHandler), auto-dispatch pending enrollments.

### ACK & Timeout Handling
- **D-04:** Cache-based message ID correlation — generate a unique message ID per enrollment batch, store in Laravel cache keyed by camera+messageId with TTL matching `config('hds.enrollment.ack_timeout_minutes')`. AckHandler looks up the cache entry to find which enrollment records to update.
- **D-05:** Timeout detection via scheduled command — a scheduled command checks for pending enrollments older than `ack_timeout_minutes`. Marks them as failed with "ACK timeout" error message. No auto-retry on timeout; admin retries manually.
- **D-06:** ACK success transitions status from pending to enrolled, sets `enrolled_at` timestamp. ACK failure transitions to failed with camera error code translated to operator-friendly message.

### Retry & Re-sync UX
- **D-07:** Per-camera retry re-pushes single personnel — retry button on the personnel Show page enrollment sidebar re-dispatches enrollment for just that one personnel to that one camera. Targeted, minimal traffic.
- **D-08:** "Re-sync all" forces re-push to all cameras — resets all enrollment statuses to pending and re-dispatches to all online cameras regardless of current status (enrolled, pending, failed). Ensures camera-side data matches server.
- **D-09:** Real-time sidebar updates via Reverb — broadcast enrollment status changes on the `fras.alerts` channel. Personnel Show page listens via Echo and updates SyncStatusDot instantly when ACK arrives. Consistent with camera liveness real-time pattern.

### Bulk Status Dashboard
- **D-10:** Summary panel on personnel Index page — per-camera enrollment counts (X/Y enrolled, Z failed) displayed at the top of the existing personnel list. Clickable cards navigate to camera detail for specifics.
- **D-11:** View-only counts, no bulk actions — the summary panel is informational. Retry actions are per-personnel on the Show page. No global "sync all" button from the index.

### Delete Sync
- **D-12:** Deleting a personnel record sends MQTT DeletePersons to all cameras where the personnel was enrolled. Fire-and-forget — no ACK tracking for deletes. Camera enrollment records are cascade-deleted by the foreign key constraint.

### Claude's Discretion
- Enrollment job class structure (single job per camera vs one job that loops cameras)
- WithoutOverlapping lock key naming convention
- Cache key format and TTL implementation details
- Enrollment status enum/constants (pending, enrolled, failed)
- camera_enrollments migration update — add `status` column if needed (current schema lacks explicit status)
- Error code mapping implementation (config array, enum, or translation file)
- Bulk summary panel component design and layout
- EnrollmentStatusChanged event structure for Reverb broadcast
- OnlineOfflineHandler integration — how to trigger pending enrollment dispatch when camera comes online
- Scheduled timeout check command frequency

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — EditPersonsNew MQTT payload schema, EditPersonsNew-Ack response schema, DeletePersons payload, error codes (Appendix), message ID format, batch constraints (max 1000), picURI requirements

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 4 requirements: ENRL-01 through ENRL-10
- `.planning/ROADMAP.md` — Phase 4 success criteria (5 criteria that must be TRUE)

### Configuration
- `config/hds.php` — `enrollment.batch_size` (1000), `enrollment.ack_timeout_minutes` (5), `photo.max_dimension`, `photo.max_size_bytes`

### Prior Phase Context
- `.planning/phases/01-infrastructure-mqtt-foundation/01-CONTEXT.md` — MQTT architecture (D-05 through D-08), fras.alerts channel (D-09), config/hds.php structure (D-15)
- `.planning/phases/02-camera-management-liveness/02-CONTEXT.md` — Camera CRUD pattern, OnlineOfflineHandler (D-07), CameraStatusChanged broadcast (D-06)
- `.planning/phases/03-personnel-management/03-CONTEXT.md` — Personnel CRUD pattern, SyncStatusDot component (D-08), enrollment sidebar placeholder (D-10)

### Existing Code (patterns to follow and extend)
- `app/Mqtt/Handlers/AckHandler.php` — Stub handler, needs full implementation for ACK correlation
- `app/Mqtt/Handlers/OnlineOfflineHandler.php` — Extend to dispatch pending enrollments when camera comes online
- `app/Mqtt/TopicRouter.php` — Routes MQTT messages to handlers by topic pattern
- `app/Mqtt/Contracts/MqttHandler.php` — Handler interface
- `app/Http/Controllers/PersonnelController.php` — store/update/destroy methods need enrollment dispatching hooks
- `app/Models/Personnel.php` — Needs camera enrollment relationship
- `app/Models/Camera.php` — Needs personnel enrollment relationship
- `app/Services/PhotoProcessor.php` — Photo processing already in place
- `database/migrations/2026_04_10_000004_create_camera_enrollments_table.php` — Existing schema (camera_id, personnel_id, enrolled_at, photo_hash, last_error)
- `resources/js/pages/personnel/Show.vue` — Enrollment sidebar with hardcoded SyncStatusDot, needs live status
- `resources/js/pages/personnel/Index.vue` — Needs bulk enrollment summary panel
- `resources/js/components/SyncStatusDot.vue` — Status dot component, needs real status prop binding

### Codebase Maps
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns
- `.planning/codebase/STRUCTURE.md` — Where to add new code

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AckHandler`: Stub ready for full implementation — already registered in TopicRouter for `mqtt/face/+/Ack`
- `OnlineOfflineHandler`: Already handles camera state transitions — extend to trigger pending enrollment dispatch
- `SyncStatusDot` component: Accepts a `status` prop — currently hardcoded to "not-synced", needs real enrollment data
- `PersonnelController`: Resource controller with store/update/destroy — add enrollment dispatch hooks
- `CameraStatusChanged` event: Pattern for broadcasting camera state — use similar pattern for `EnrollmentStatusChanged`
- `fras.alerts` private channel: Already configured for real-time broadcasts

### Established Patterns
- MQTT handlers implement `MqttHandler` interface with `handle(string $topic, string $message): void`
- TopicRouter dispatches by topic pattern matching
- Inertia flash toasts for success feedback: `Inertia::flash('toast', [...])`
- Laravel Echo listeners on Vue pages for real-time updates (used in camera list and detail)
- WithoutOverlapping middleware available via Laravel's job middleware
- Cache-based state management using `Cache::put()` with TTL

### Integration Points
- `PersonnelController::store()` and `update()` — dispatch enrollment after personnel save
- `PersonnelController::destroy()` — dispatch delete sync before/after personnel delete
- `CameraController::store()` — dispatch bulk enrollment for new cameras
- `OnlineOfflineHandler::handle()` — trigger pending enrollment dispatch on camera online
- `routes/web.php` — Add retry and re-sync routes (POST endpoints)
- `resources/js/pages/personnel/Show.vue` — Wire enrollment sidebar to real data + Echo listener
- `resources/js/pages/personnel/Index.vue` — Add bulk enrollment summary panel

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches following existing patterns and the FRAS spec.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 04-enrollment-sync*
*Context gathered: 2026-04-10*

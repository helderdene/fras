# Phase 4: Enrollment Sync - Research

**Researched:** 2026-04-10
**Domain:** MQTT enrollment sync (EditPersonsNew/DeletePersons), Laravel queue jobs, cache-based ACK correlation, real-time UI updates
**Confidence:** HIGH

## Summary

Phase 4 implements the core enrollment pipeline: personnel records are automatically pushed to all cameras via MQTT `EditPersonsNew` with reliable ACK tracking, retry capability, and delete propagation via `DeletePersons`. The phase requires a new `CameraEnrollmentService` that builds MQTT payloads per the FRAS spec, a queued `EnrollPersonnelBatch` job gated by `WithoutOverlapping` middleware, a full `AckHandler` implementation that correlates ACK responses via cached message IDs, a scheduled command to detect ACK timeouts, and frontend updates to wire the enrollment sidebar with real status data and Echo-driven live updates.

The existing codebase provides strong foundations: the MQTT listener is running and routes to stub handlers, the `AckHandler` stub is registered in `TopicRouter`, the `fras.alerts` private broadcast channel is configured, `SyncStatusDot` is built with the correct status variants, and the `PersonnelController` resource routes are in place. The `camera_enrollments` table exists but lacks a `status` column -- it needs a migration to add explicit enrollment status tracking.

**Primary recommendation:** Build the enrollment pipeline as a service layer (`CameraEnrollmentService`) called from the job, keeping the job thin. Use cache-based message ID correlation with a structured cache key format. Add a `status` enum column to `camera_enrollments` via migration. Extend `OnlineOfflineHandler` to dispatch pending enrollments on camera online transition. Use the existing `useEcho` + `fras.alerts` pattern for real-time enrollment status updates.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Auto-enroll on every save -- creating or updating a personnel record always dispatches enrollment to all online cameras. EditPersonsNew is idempotent (upsert semantics), so redundant pushes are safe.
- **D-02:** Auto-enroll all on new camera -- when a new camera is registered, dispatch enrollment jobs for all existing personnel to that camera automatically.
- **D-03:** Skip offline cameras at dispatch time -- don't send MQTT to cameras marked `is_online = false`. Create `camera_enrollment` rows with pending status. When camera comes back online (via OnlineOfflineHandler), auto-dispatch pending enrollments.
- **D-04:** Cache-based message ID correlation -- generate a unique message ID per enrollment batch, store in Laravel cache keyed by camera+messageId with TTL matching `config('hds.enrollment.ack_timeout_minutes')`. AckHandler looks up the cache entry to find which enrollment records to update.
- **D-05:** Timeout detection via scheduled command -- a scheduled command checks for pending enrollments older than `ack_timeout_minutes`. Marks them as failed with "ACK timeout" error message. No auto-retry on timeout; admin retries manually.
- **D-06:** ACK success transitions status from pending to enrolled, sets `enrolled_at` timestamp. ACK failure transitions to failed with camera error code translated to operator-friendly message.
- **D-07:** Per-camera retry re-pushes single personnel -- retry button on the personnel Show page enrollment sidebar re-dispatches enrollment for just that one personnel to that one camera.
- **D-08:** "Re-sync all" forces re-push to all cameras -- resets all enrollment statuses to pending and re-dispatches to all online cameras regardless of current status.
- **D-09:** Real-time sidebar updates via Reverb -- broadcast enrollment status changes on the `fras.alerts` channel. Personnel Show page listens via Echo and updates SyncStatusDot instantly when ACK arrives.
- **D-10:** Summary panel on personnel Index page -- per-camera enrollment counts (X/Y enrolled, Z failed) displayed at the top of the existing personnel list. Clickable cards navigate to camera detail for specifics.
- **D-11:** View-only counts, no bulk actions -- the summary panel is informational.
- **D-12:** Deleting a personnel record sends MQTT DeletePersons to all cameras where the personnel was enrolled. Fire-and-forget -- no ACK tracking for deletes. Camera enrollment records are cascade-deleted by the foreign key constraint.

### Claude's Discretion
- Enrollment job class structure (single job per camera vs one job that loops cameras)
- WithoutOverlapping lock key naming convention
- Cache key format and TTL implementation details
- Enrollment status enum/constants (pending, enrolled, failed)
- camera_enrollments migration update -- add `status` column if needed
- Error code mapping implementation (config array, enum, or translation file)
- Bulk summary panel component design and layout
- EnrollmentStatusChanged event structure for Reverb broadcast
- OnlineOfflineHandler integration -- how to trigger pending enrollment dispatch when camera comes online
- Scheduled timeout check command frequency

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| ENRL-01 | Saving a personnel record dispatches enrollment jobs to all cameras via MQTT EditPersonsNew | CameraEnrollmentService builds EditPersonsNew payload per spec section 3.5; PersonnelController hooks dispatch after store/update |
| ENRL-02 | Enrollment batches are limited to 1000 entries; larger sets are chunked | `config('hds.enrollment.batch_size')` already set to 1000; CameraEnrollmentService chunks with `array_chunk()` |
| ENRL-03 | Only one enrollment batch may be in-flight per camera at a time (WithoutOverlapping middleware) | Laravel `WithoutOverlapping` middleware on EnrollPersonnelBatch job with per-camera lock key |
| ENRL-04 | System correlates EditPersonsNew-Ack responses to pending enrollments via cached message IDs | AckHandler reads `messageId` from ACK, looks up cache entry mapping to camera_id + personnel_ids |
| ENRL-05 | Per-camera enrollment status shows enrolled/pending/failed state with last sync time or error message | New `status` column on camera_enrollments; SyncStatusDot wired to real data |
| ENRL-06 | Failed enrollments show translated operator-friendly error messages | Error code map from spec Appendix (461-478); config array or enum translating to human messages |
| ENRL-07 | Admin can retry failed enrollments per camera with a single click | New POST route; dispatches EnrollPersonnelBatch for single personnel + single camera |
| ENRL-08 | "Re-sync all" button forces re-push to all cameras | New POST route; resets all enrollment statuses to pending, dispatches to all online cameras |
| ENRL-09 | Deleting a personnel record sends MQTT DeletePersons to all cameras | PersonnelController::destroy dispatches DeletePersons MQTT messages; fire-and-forget |
| ENRL-10 | Bulk enrollment status dashboard shows per-camera enrollment counts | Summary panel on personnel Index page with per-camera aggregated counts |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| php-mqtt/laravel-client | v1.8.0 | MQTT publish to cameras | Already installed; `MQTT::publish()` facade for sending EditPersonsNew/DeletePersons [VERIFIED: composer show] |
| Laravel Queue (database driver) | v13.4.0 | Job dispatching for enrollment batches | Already configured with `QUEUE_CONNECTION=database` [VERIFIED: .env + config/queue.php] |
| Laravel Cache (database driver) | v13.4.0 | Message ID correlation for ACK tracking | Already configured; TTL-based storage for pending enrollment lookups [VERIFIED: config/cache.php default] |
| Laravel Reverb | v1.x | Real-time enrollment status broadcasts | Already configured; `fras.alerts` private channel in use [VERIFIED: routes/channels.php] |
| @laravel/echo-vue | installed | Frontend real-time event listening | Already in use on cameras/Index.vue via `useEcho` composable [VERIFIED: existing codebase] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| WithoutOverlapping middleware | Laravel built-in | Concurrency control per camera | On EnrollPersonnelBatch job to prevent multiple in-flight batches [VERIFIED: vendor source] |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Cache-based ACK correlation | Database-based pending_enrollments table | Cache is simpler and ephemeral (matches TTL semantics); DB table adds permanent state overhead for transient data |
| Single job per camera | One job per personnel that loops cameras | Per-camera job is better: WithoutOverlapping lock is naturally per-camera; allows parallel enrollment to different cameras |

## Architecture Patterns

### Recommended Project Structure
```
app/
├── Console/Commands/
│   └── CheckEnrollmentTimeoutsCommand.php    # Scheduled timeout detector
├── Events/
│   └── EnrollmentStatusChanged.php            # Reverb broadcast event
├── Http/Controllers/
│   ├── PersonnelController.php                # Modified: dispatch enrollment on store/update/destroy
│   ├── CameraController.php                   # Modified: dispatch bulk enrollment on new camera
│   └── EnrollmentController.php               # New: retry + re-sync endpoints
├── Jobs/
│   └── EnrollPersonnelBatch.php               # Queued enrollment job
├── Models/
│   └── CameraEnrollment.php                   # New pivot model
├── Mqtt/Handlers/
│   ├── AckHandler.php                         # Modified: full ACK correlation
│   └── OnlineOfflineHandler.php               # Modified: dispatch pending enrollments on online
├── Services/
│   └── CameraEnrollmentService.php            # New: enrollment business logic
database/migrations/
│   └── xxxx_add_status_to_camera_enrollments_table.php  # Add status column
resources/js/
├── components/
│   └── EnrollmentSummaryPanel.vue             # New: per-camera summary cards
├── pages/personnel/
│   ├── Index.vue                              # Modified: add summary panel
│   └── Show.vue                               # Modified: wire enrollment sidebar + Echo
└── types/
    └── enrollment.ts                          # New: enrollment types
```

### Pattern 1: Enrollment Job with WithoutOverlapping
**What:** One `EnrollPersonnelBatch` job dispatched per camera. The job uses `WithoutOverlapping` middleware keyed by camera ID to ensure only one batch is in-flight per camera at a time. Jobs waiting for the lock are automatically released back to the queue.
**When to use:** Every time enrollment needs to be sent to a camera.
**Example:**
```php
// Source: Laravel docs + FRAS spec section 5.5
class EnrollPersonnelBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Camera $camera,
        public array $personnelIds,
    ) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('enrollment-camera-'.$this->camera->id))
                ->releaseAfter(30)  // re-try after 30s if lock held
                ->expireAfter(300), // lock expires after 5min (matches ACK timeout)
        ];
    }

    public function handle(CameraEnrollmentService $service): void
    {
        $service->upsertBatch($this->camera, $this->personnelIds);
    }
}
```
[VERIFIED: WithoutOverlapping signature from vendor/laravel/framework source]

### Pattern 2: Cache-Based ACK Correlation
**What:** When publishing an enrollment batch, store a cache entry keyed by `enrollment-ack:{camera_id}:{messageId}` with value containing the personnel IDs in the batch. TTL matches `config('hds.enrollment.ack_timeout_minutes')`. When the ACK arrives, the handler looks up this cache key to find which enrollment records to update.
**When to use:** Every outbound EditPersonsNew message.
**Example:**
```php
// Source: FRAS spec section 5.6 + D-04
// In CameraEnrollmentService::upsertBatch():
$messageId = 'EditPersonsNew' . now()->format('Y-m-d\TH:i:s') . '_' . Str::random(6);
$ttl = config('hds.enrollment.ack_timeout_minutes') * 60; // seconds

Cache::put("enrollment-ack:{$camera->id}:{$messageId}", [
    'camera_id' => $camera->id,
    'personnel_ids' => $personnelIds,
    'dispatched_at' => now()->toIso8601String(),
], $ttl);

// In AckHandler::handle():
$data = json_decode($message, true);
$messageId = $data['messageId'] ?? null;
$cameraId = /* extract from topic */;
$cacheKey = "enrollment-ack:{$cameraId}:{$messageId}";
$pending = Cache::pull($cacheKey); // pull = get + forget
```
[VERIFIED: Cache::put/pull API from Laravel framework]

### Pattern 3: Real-Time Enrollment Status via Reverb
**What:** After updating `camera_enrollments` rows (on ACK success, ACK failure, or timeout), dispatch an `EnrollmentStatusChanged` broadcast event on `fras.alerts`. The Vue Show page listens via `useEcho` and updates the SyncStatusDot reactively.
**When to use:** Every enrollment status transition (pending->enrolled, pending->failed).
**Example:**
```php
// Source: CameraStatusChanged pattern in existing codebase
class EnrollmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $personnel_id,
        public int $camera_id,
        public string $status,        // 'enrolled' | 'pending' | 'failed'
        public ?string $enrolled_at,
        public ?string $last_error,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('fras.alerts');
    }
}
```
```typescript
// Source: cameras/Index.vue Echo pattern
useEcho(
    'fras.alerts',
    '.EnrollmentStatusChanged',
    (payload: EnrollmentStatusPayload) => {
        // Update local enrollment data reactively
    },
);
```
[VERIFIED: CameraStatusChanged pattern + useEcho from existing codebase]

### Pattern 4: Enrollment Dispatch on Personnel Save
**What:** After `PersonnelController::store()` and `update()` persist the record, dispatch `EnrollPersonnelBatch` jobs to all online cameras. Skip offline cameras but create pending enrollment rows for them.
**When to use:** Every personnel create/update.
**Example:**
```php
// In PersonnelController::store() after Personnel::create():
$onlineCameras = Camera::where('is_online', true)->get();
foreach ($onlineCameras as $camera) {
    CameraEnrollment::updateOrCreate(
        ['camera_id' => $camera->id, 'personnel_id' => $personnel->id],
        ['status' => 'pending', 'last_error' => null]
    );
    EnrollPersonnelBatch::dispatch($camera, [$personnel->id]);
}

// Also create pending rows for offline cameras (D-03)
$offlineCameras = Camera::where('is_online', false)->get();
foreach ($offlineCameras as $camera) {
    CameraEnrollment::updateOrCreate(
        ['camera_id' => $camera->id, 'personnel_id' => $personnel->id],
        ['status' => 'pending', 'last_error' => null]
    );
}
```
[VERIFIED: Camera model has `is_online` boolean field]

### Anti-Patterns to Avoid
- **Publishing MQTT from HTTP request thread:** Never publish MQTT messages synchronously in the controller. Always dispatch a queued job. MQTT publishing can block on broker connection issues.
- **Trusting cache for permanent state:** Cache entries expire. The `camera_enrollments` table is the source of truth for enrollment status. Cache is only used for transient ACK correlation.
- **Sending batches larger than 1000:** Camera rejects with errcode 417 if `PersonNum` does not match `info` array length, or if batch exceeds 1000. Always chunk.
- **Sending enrollment to offline cameras via MQTT:** MQTT messages to a disconnected camera are lost (QoS 0). Create pending rows and dispatch when camera comes online.
- **Re-dispatching before lock expires:** WithoutOverlapping prevents this, but ensure `expireAfter` matches the expected enrollment round-trip time (ACK timeout).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Job concurrency per camera | Custom lock table/flag | `WithoutOverlapping` middleware | Built-in, handles lock release, expiry, and re-queue automatically |
| Scheduled task for timeout detection | Custom daemon or loop | `Schedule::command()->everyMinute()` | Laravel scheduler already manages the MQTT listener and offline check; add one more command |
| MQTT message publishing | Raw socket code | `MQTT::publish()` facade | php-mqtt/laravel-client already configured with broker connection, reconnect, QoS |
| Real-time browser updates | Polling/SSE | Reverb + Echo (`useEcho`) | Already configured and used for camera status; consistent pattern |
| Broadcast event boilerplate | Manual WebSocket push | Laravel `ShouldBroadcast` event | Framework handles serialization, channel auth, Reverb dispatch |

**Key insight:** The entire real-time pipeline (MQTT -> Handler -> Cache -> Database -> Broadcast -> Echo -> Vue) is assembled from existing framework and library primitives. The only genuinely new code is the enrollment business logic in `CameraEnrollmentService` and the ACK correlation in `AckHandler`.

## Common Pitfalls

### Pitfall 1: ACK Timeout Race Condition
**What goes wrong:** The scheduled timeout command marks enrollments as failed at the same moment the ACK arrives. The ACK handler then tries to update already-failed rows.
**Why it happens:** Cache TTL and timeout detection are not atomic. The cache entry may expire between the timeout check and the ACK arrival.
**How to avoid:** Use `Cache::pull()` (get + delete atomically) in the AckHandler. If the cache entry is gone (expired or already processed), check the database for pending rows as a fallback. The timeout command should only mark enrollments as failed if they are still in `pending` status (use a `where('status', 'pending')` guard).
**Warning signs:** Enrollment rows flipping between enrolled and failed, or logs showing "unknown messageId" for ACKs that arrive just after timeout.

### Pitfall 2: Duplicate Enrollment Jobs on Rapid Saves
**What goes wrong:** Admin saves a personnel record, then immediately saves again. Both saves dispatch enrollment jobs. The second job overwrites the first's cache entry with the same messageId format.
**Why it happens:** MessageId generation based only on timestamp may collide within the same second.
**How to avoid:** Include a random suffix in the messageId (e.g., `Str::random(6)`). WithoutOverlapping naturally serializes jobs per camera, so the second batch queues behind the first.
**Warning signs:** ACK correlation returning wrong personnel IDs, or cache entries being overwritten.

### Pitfall 3: picURI Not Reachable from Camera
**What goes wrong:** Camera receives the EditPersonsNew message but cannot download the photo because the `picURI` points to a hostname the camera cannot resolve.
**Why it happens:** The Laravel server's APP_URL uses a domain name that the camera subnet cannot resolve (e.g., `https://fras.test` which is Herd-only).
**How to avoid:** The `picURI` must use an IP or hostname reachable from the camera network. Use `config('app.url')` but ensure the `.env` APP_URL in production is network-accessible from the camera subnet. Add a note in deployment docs. Error code 464 (DNS resolution failed) or 465 (download failed) indicates this issue.
**Warning signs:** All enrollments failing with error code 464 or 465.

### Pitfall 4: Camera Enrollment Table Missing Status Column
**What goes wrong:** The existing `camera_enrollments` migration has `enrolled_at` and `last_error` but no explicit `status` column. Code tries to filter by status and gets errors.
**Why it happens:** The original Phase 1 migration created the table based on spec, which infers status from `enrolled_at` being null/not-null. Phase 4 needs explicit status for pending/enrolled/failed states.
**How to avoid:** Add a migration to add `status` column (varchar or enum) with default 'pending'. Backfill existing rows if any.
**Warning signs:** Query errors on `camera_enrollments.status`.

### Pitfall 5: OnlineOfflineHandler Dispatching Too Many Jobs
**What goes wrong:** When a camera comes online, the handler dispatches enrollment jobs for ALL personnel. With 200 personnel and a batch size of 1000, this is one job per camera. But if multiple cameras come online simultaneously (after a power outage), the queue floods.
**Why it happens:** OnlineOfflineHandler fires synchronously for each camera's Online message.
**How to avoid:** Dispatch a single job per camera that handles all pending enrollments. WithoutOverlapping naturally serializes per camera. Keep batch size at 1000 (fits 200 personnel in one batch). Log the dispatch count for monitoring.
**Warning signs:** Queue worker falling behind, enrollment taking minutes to complete after a multi-camera reconnect.

### Pitfall 6: DeletePersons Payload Format Unknown
**What goes wrong:** The FRAS spec lists DeletePersons as an operation but the Appendix C.5 notes it is "not yet verified" against real firmware.
**Why it happens:** Only EditPersonsNew and RecPush have been tested against the actual camera.
**How to avoid:** Implement DeletePersons based on the standard MQTT face device protocol (operator: "DeletePersons", info array with customIds). Mark as fire-and-forget per D-12. Add logging to detect if the camera rejects the message. Test during deployment.
**Warning signs:** Deleted personnel still recognized by cameras after deletion.

## Code Examples

### EditPersonsNew Payload Construction
```php
// Source: FRAS spec section 3.5
public function buildEditPersonsNewPayload(Camera $camera, array $personnelRecords, string $messageId): array
{
    $info = [];

    foreach ($personnelRecords as $personnel) {
        $entry = [
            'customId' => $personnel->custom_id,
            'name' => $personnel->name,
            'personType' => $personnel->person_type,
            'isCheckSimilarity' => 1,
        ];

        // picURI required for first enrollment; also sent on photo change
        if ($personnel->photo_path) {
            $entry['picURI'] = url('storage/' . $personnel->photo_path);
        }

        // Optional fields -- only include if present
        if ($personnel->gender !== null) {
            $entry['gender'] = $personnel->gender;
        }
        if ($personnel->birthday) {
            $entry['birthday'] = $personnel->birthday->format('Y-m-d');
        }
        if ($personnel->id_card) {
            $entry['idCard'] = $personnel->id_card;
        }
        if ($personnel->phone) {
            $entry['telnum1'] = $personnel->phone;
        }
        if ($personnel->address) {
            $entry['address'] = $personnel->address;
        }

        $info[] = $entry;
    }

    return [
        'messageId' => $messageId,
        'DataBegin' => 'BeginFlag',
        'operator' => 'EditPersonsNew',
        'PersonNum' => count($info),
        'info' => $info,
        'DataEnd' => 'EndFlag',
    ];
}
```
[VERIFIED: Matches spec section 3.5 JSON structure exactly]

### ACK Response Processing
```php
// Source: FRAS spec section 5.6
// Expected ACK payload structure (from spec, not yet firmware-verified):
// {
//   "messageId": "EditPersonsNew2026-04-10T10:00:00_abc123",
//   "operator": "EditPersonsNew-Ack",
//   "AddSucInfo": [{"customId": "hds-staff-00001"}, ...],
//   "AddErrInfo": [{"customId": "hds-staff-00002", "errcode": 468}, ...]
// }

public function processAck(int $cameraId, array $data): void
{
    $messageId = $data['messageId'] ?? null;
    if (!$messageId) { return; }

    $cacheKey = "enrollment-ack:{$cameraId}:{$messageId}";
    $pending = Cache::pull($cacheKey);

    if (!$pending) {
        Log::warning('ACK for unknown/expired messageId', compact('cameraId', 'messageId'));
        return;
    }

    // Process successes
    foreach ($data['AddSucInfo'] ?? [] as $item) {
        CameraEnrollment::where('camera_id', $cameraId)
            ->whereHas('personnel', fn ($q) => $q->where('custom_id', $item['customId']))
            ->update([
                'status' => 'enrolled',
                'enrolled_at' => now(),
                'photo_hash' => $pending['photo_hashes'][$item['customId']] ?? null,
                'last_error' => null,
            ]);
    }

    // Process failures
    foreach ($data['AddErrInfo'] ?? [] as $item) {
        $errorMessage = self::translateErrorCode($item['errcode'] ?? 0);
        CameraEnrollment::where('camera_id', $cameraId)
            ->whereHas('personnel', fn ($q) => $q->where('custom_id', $item['customId']))
            ->update([
                'status' => 'failed',
                'last_error' => $errorMessage,
            ]);
    }
}
```
[ASSUMED: ACK payload structure based on spec text, not firmware-verified -- see Appendix C.5]

### Error Code Translation Map
```php
// Source: FRAS spec section 3.5 error codes table
public const ERROR_CODES = [
    461 => 'Internal error: missing personnel ID',
    463 => 'Photo required for first enrollment',
    464 => 'Camera could not resolve photo host',
    465 => 'Camera could not download photo',
    466 => 'Photo URL returned no data',
    467 => 'Photo too large; re-upload with smaller file',
    468 => 'No usable face detected in photo',
    474 => 'Camera storage full; remove old enrollments',
    478 => 'Person may already be enrolled',
];
```
[VERIFIED: Error codes from spec section 3.5]

### DeletePersons Payload
```php
// Source: FRAS spec section 3.3 (not firmware-verified, see Appendix C.5)
public function buildDeletePersonsPayload(array $customIds, string $messageId): array
{
    return [
        'messageId' => $messageId,
        'operator' => 'DeletePersons',
        'info' => array_map(fn ($id) => ['customId' => $id], $customIds),
    ];
}
```
[ASSUMED: DeletePersons payload structure based on standard MQTT face device protocol -- not yet verified against firmware]

### Enrollment Status Summary Query
```php
// Source: Application pattern for D-10
// Per-camera enrollment counts for the Index page summary panel
$summary = Camera::select('cameras.id', 'cameras.name')
    ->withCount([
        'enrollments as enrolled_count' => fn ($q) => $q->where('status', 'enrolled'),
        'enrollments as pending_count' => fn ($q) => $q->where('status', 'pending'),
        'enrollments as failed_count' => fn ($q) => $q->where('status', 'failed'),
    ])
    ->orderBy('name')
    ->get();
```
[VERIFIED: Laravel withCount subquery pattern]

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `Inertia::lazy()` / `LazyProp` | `Inertia::optional()` | Inertia v3 | Use `optional()` for enrollment data that is only loaded when needed |
| `window.Echo.channel()` | `useEcho()` from `@laravel/echo-vue` | Current codebase pattern | Composable-based Echo with automatic cleanup |
| `router.cancel()` | `router.cancelAll()` | Inertia v3 | Minor API change |
| `future` config namespace | Always enabled | Inertia v3 | No config needed for future features |

**Deprecated/outdated:**
- `Inertia::lazy()` removed in v3 -- use `Inertia::optional()` instead [VERIFIED: CLAUDE.md Inertia v3 rules]
- `window.Echo.channel()` -- use `useEcho` composable from `@laravel/echo-vue` for automatic lifecycle management [VERIFIED: cameras/Index.vue existing pattern]

## Assumptions Log

> List all claims tagged `[ASSUMED]` in this research.

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | EditPersonsNew-Ack payload has `AddSucInfo` and `AddErrInfo` arrays with per-person results | Code Examples: ACK Response Processing | AckHandler correlation logic would need restructuring; ACK format is documented in spec but not firmware-verified (Appendix C.5) |
| A2 | DeletePersons payload uses `operator: "DeletePersons"` with `info` array of `customId` objects | Code Examples: DeletePersons Payload | Delete sync would fail silently; marked fire-and-forget (D-12) so impact is limited; will be validated during deployment |
| A3 | ACK response includes `messageId` matching the one sent in the request | Architecture Pattern 2 | Cache correlation would fail entirely; fallback would need database-based correlation by timestamp |

**Mitigation for A1-A3:** All three assumptions are about camera firmware behavior documented in spec but not yet tested (Appendix C.5 of FRAS spec). The implementation should include defensive logging: log the full raw ACK payload on receipt, and log any unexpected structure. During deployment testing, these payloads will be verified against real firmware. The fire-and-forget approach for deletes (D-12) limits the impact of A2.

## Open Questions

1. **APP_URL for picURI in production**
   - What we know: Cameras fetch photos via HTTP from the `picURI` URL. The URL must be reachable from the camera subnet.
   - What's unclear: Whether the production APP_URL (e.g., `https://fras.test` or an IP address) is resolvable from the camera network.
   - Recommendation: Document in deployment guide. Use IP-based URL if DNS is not available on camera subnet. The `url()` helper uses APP_URL.

2. **DeletePersons exact payload structure**
   - What we know: The spec lists DeletePersons as an operation. Standard MQTT face device protocol uses `operator: "DeletePersons"` with an info array.
   - What's unclear: Exact field names and required markers (DataBegin/DataEnd). Not firmware-verified (Appendix C.5).
   - Recommendation: Implement with standard structure, log rejections, verify during deployment testing.

3. **ACK arrival timing for large batches**
   - What we know: Cameras process enrollment sequentially, downloading each photo via HTTP.
   - What's unclear: How long a 200-person batch takes (each photo download adds latency). Whether the 5-minute ACK timeout is sufficient.
   - Recommendation: Start with 5 minutes (`config('hds.enrollment.ack_timeout_minutes')`), make configurable, monitor during deployment.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 with Laravel plugin |
| Config file | `phpunit.xml` (Pest uses PHPUnit config) |
| Quick run command | `php artisan test --compact --filter=EnrollmentTest` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| ENRL-01 | Store personnel dispatches enrollment jobs | feature | `php artisan test --compact --filter=EnrollmentSyncTest` | Wave 0 |
| ENRL-02 | Batches chunked at 1000 | unit | `php artisan test --compact --filter=CameraEnrollmentServiceTest` | Wave 0 |
| ENRL-03 | WithoutOverlapping prevents concurrent batches | feature | `php artisan test --compact --filter=EnrollPersonnelBatchTest` | Wave 0 |
| ENRL-04 | ACK correlation via cache | feature | `php artisan test --compact --filter=AckHandlerTest` | Wave 0 |
| ENRL-05 | Per-camera enrollment status | feature | `php artisan test --compact --filter=EnrollmentStatusTest` | Wave 0 |
| ENRL-06 | Error code translation | unit | `php artisan test --compact --filter=CameraEnrollmentServiceTest` | Wave 0 |
| ENRL-07 | Single-camera retry | feature | `php artisan test --compact --filter=EnrollmentRetryTest` | Wave 0 |
| ENRL-08 | Re-sync all | feature | `php artisan test --compact --filter=EnrollmentResyncTest` | Wave 0 |
| ENRL-09 | Delete dispatches DeletePersons | feature | `php artisan test --compact --filter=EnrollmentDeleteSyncTest` | Wave 0 |
| ENRL-10 | Bulk summary counts | feature | `php artisan test --compact --filter=EnrollmentSummaryTest` | Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --compact --filter=Enrollment`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Enrollment/EnrollmentSyncTest.php` -- covers ENRL-01
- [ ] `tests/Feature/Enrollment/CameraEnrollmentServiceTest.php` -- covers ENRL-02, ENRL-06
- [ ] `tests/Feature/Enrollment/EnrollPersonnelBatchTest.php` -- covers ENRL-03
- [ ] `tests/Feature/Enrollment/AckHandlerTest.php` -- covers ENRL-04
- [ ] `tests/Feature/Enrollment/EnrollmentStatusTest.php` -- covers ENRL-05
- [ ] `tests/Feature/Enrollment/EnrollmentRetryTest.php` -- covers ENRL-07
- [ ] `tests/Feature/Enrollment/EnrollmentResyncTest.php` -- covers ENRL-08
- [ ] `tests/Feature/Enrollment/EnrollmentDeleteSyncTest.php` -- covers ENRL-09
- [ ] `tests/Feature/Enrollment/EnrollmentSummaryTest.php` -- covers ENRL-10

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | All enrollment routes behind `auth` + `verified` middleware (existing pattern) |
| V3 Session Management | no | No session changes in this phase |
| V4 Access Control | yes | Enrollment actions require authenticated user; broadcast channel `fras.alerts` requires auth |
| V5 Input Validation | yes | Retry/re-sync routes use Form Requests; MQTT payloads validated before processing |
| V6 Cryptography | no | No new crypto; photos already hashed with MD5 (integrity check, not security) |

### Known Threat Patterns for This Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| MQTT message injection (fake ACK) | Spoofing | Cache-based correlation requires knowing the exact messageId; broker auth via MQTT credentials |
| Unauthorized enrollment retry | Elevation of Privilege | POST routes protected by `auth` middleware |
| Queue job manipulation | Tampering | Database queue driver; jobs serialized in DB, not user-accessible |
| picURI SSRF | Spoofing | Camera fetches URL, not server; URL is constructed from known storage path, not user input |

## Project Constraints (from CLAUDE.md)

- **Test enforcement:** Every change must be programmatically tested. Run `php artisan test --compact` with specific filter.
- **Pint formatting:** Run `vendor/bin/pint --dirty --format agent` before finalizing any PHP changes.
- **Artisan make commands:** Use `php artisan make:` for new files (jobs, models, controllers, migrations, tests).
- **Wayfinder:** Use Wayfinder-generated route functions for all frontend-backend connections. Run `wayfinder:generate` after adding routes.
- **Inertia flash toasts:** Use `Inertia::flash('toast', ['type' => 'success', 'message' => __(...)])` for mutation feedback.
- **Vue script setup:** All Vue components use `<script setup lang="ts">`.
- **No dependency changes** without approval (no new packages needed -- all already installed).
- **Form Requests:** Use for all controller methods accepting user input.
- **PHP 8 attributes:** Use `#[Fillable([...])]` on models, not `$fillable` array.
- **Existing test convention:** Use `test()` (not `it()`), feature tests in `tests/Feature/`, use factories.

## Sources

### Primary (HIGH confidence)
- `docs/HDS-FRAS-Spec-v1.1.md` -- EditPersonsNew payload (section 3.5), error codes, ACK correlation (section 5.6), enrollment job design (section 5.5)
- `config/hds.php` -- enrollment.batch_size (1000), enrollment.ack_timeout_minutes (5)
- `app/Mqtt/Handlers/AckHandler.php` -- Stub handler ready for implementation
- `app/Mqtt/Handlers/OnlineOfflineHandler.php` -- Camera state transition handler to extend
- `app/Http/Controllers/PersonnelController.php` -- Resource controller to add enrollment dispatch hooks
- `database/migrations/2026_04_10_000004_create_camera_enrollments_table.php` -- Existing schema (needs status column)
- `app/Events/CameraStatusChanged.php` -- Broadcast event pattern to follow
- `resources/js/pages/cameras/Index.vue` -- `useEcho` real-time pattern to follow
- `vendor/laravel/framework/src/Illuminate/Queue/Middleware/WithoutOverlapping.php` -- Middleware API

### Secondary (MEDIUM confidence)
- `.planning/phases/04-enrollment-sync/04-CONTEXT.md` -- All 12 locked decisions (D-01 through D-12)
- `.planning/REQUIREMENTS.md` -- ENRL-01 through ENRL-10 requirement definitions
- `.planning/codebase/STRUCTURE.md` -- Where to add new code (Jobs/, Services/, Events/)

### Tertiary (LOW confidence)
- FRAS spec Appendix C.5: EditPersonsNew-Ack response format and DeletePersons behavior listed as "not yet verified against real firmware"

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all libraries already installed and in use in the codebase
- Architecture: HIGH - patterns directly follow existing codebase conventions (CameraStatusChanged, useEcho, TopicRouter)
- Pitfalls: HIGH - error codes from spec, race conditions from architectural analysis
- ACK payload structure: MEDIUM - documented in spec but not firmware-verified
- DeletePersons payload: LOW - listed in spec but not firmware-verified

**Research date:** 2026-04-10
**Valid until:** 2026-05-10 (stable -- no fast-moving dependencies)

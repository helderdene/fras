# Phase 5: Recognition & Alerting - Research

**Researched:** 2026-04-11
**Domain:** MQTT event processing, real-time broadcasting, Vue alert UI
**Confidence:** HIGH

## Summary

Phase 5 implements the core event pipeline of HDS-FRAS: receiving RecPush MQTT messages from cameras, parsing them with firmware quirk handling, saving face/scene images, classifying severity, broadcasting via Reverb/Pusher, and rendering a live alert feed in the browser with audio notifications and operator acknowledge/dismiss.

The backend work follows established patterns from Phases 2 and 4: the `RecognitionHandler` stub already exists and is registered in `TopicRouter`, broadcast events follow the `CameraStatusChanged`/`EnrollmentStatusChanged` pattern on `fras.alerts` channel, and the `recognition_events` table migration is already in place. The new pieces are: an `AlertSeverity` PHP enum (first enum in the project -- requires creating `app/Enums/` directory), base64 image decoding and date-partitioned storage, a new migration for acknowledge/dismiss columns, a `RecognitionEvent` model, a `RecognitionAlert` broadcast event, a controller for the alert feed page and acknowledge/dismiss endpoints, and the Vue alert feed page with Echo listener, detail modal, and audio notifications.

**Primary recommendation:** Build backend-first (handler, enum, model, broadcast event, controller) then frontend (alert feed page, detail modal, audio). The handler is the critical path -- it must be correct and well-tested since it processes every recognition event.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Compact list rows -- dense rows with small face crop thumbnail, person name, camera name, severity tag, similarity score, and relative timestamp. Left border colored by severity (red/amber/green). Fits maximum alerts on screen for command center monitoring.
- **D-02:** New alerts slide in from the top, pushing existing alerts down with a brief highlight flash on the new row.
- **D-03:** Feed capped at ~50 most recent alerts. Older alerts are only accessible via Event History (Phase 7). Keeps the feed performant and focused on current activity.
- **D-04:** Severity toggle filter pills at the top of the feed: All | Critical | Warning | Info. Quick filtering to focus during busy periods.
- **D-05:** Side-by-side layout in a wide modal -- face crop on the left (~150px), scene image on the right (with bounding box overlay drawn from target_bbox coordinates). Metadata below both images (person name, custom ID, camera, similarity, person type, captured timestamp). Acknowledge/Dismiss buttons at the bottom.
- **D-06:** Date-partitioned image storage: `storage/app/recognition/{YYYY-MM-DD}/faces/` and `storage/app/recognition/{YYYY-MM-DD}/scenes/`. Files named by event ID. Supports Phase 7 retention cleanup by date.
- **D-07:** When scene image is missing (firmware quirk -- some cameras don't send it), show a gray placeholder box with "Scene image not available" text. Face crop still displays normally. Modal layout stays the same.
- **D-08:** Single chime per critical event -- short, distinct alert sound (~1-2 seconds) plays once per critical (block-list) event. Multiple rapid events each play their chime. Mute toggle available.
- **D-09:** Explicit "Enable Alert Sound" button (bell icon) in the feed header or status bar. Clicking triggers a user gesture to unlock browser audio. Operator knows audio is active. Reliable approach for browser autoplay restrictions.
- **D-10:** Inline Ack/Dismiss icon buttons appear on hover/focus of each alert row in the feed. Acknowledge records who handled it (current user) and when (timestamp). Dismiss fades the row but keeps it in the feed and DB. Single-click actions for rapid response.
- **D-11:** Spec-based severity mapping via PHP enum `AlertSeverity`:
  - **Critical** = Block-list match (person_type=1, verify_status=0) -- "Known threat recognized"
  - **Warning** = Entry refused (verify_status=2, any person_type) -- "Access denied at gate"
  - **Info** = Allow-list match (person_type=0, verify_status=0) -- "Known person recognized"
  - **Ignored** = Stranger (verify_status=3) or No-match (verify_status=1) -- stored in DB, NOT surfaced in alert feed or broadcast
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

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| REC-01 | MQTT listener subscribes to camera recognition topics (mqtt/face/+/Rec) and processes RecPush events | TopicRouter already routes `mqtt/face/+/Rec` to RecognitionHandler stub; handler needs full implementation |
| REC-02 | Handler parses RecPush payload with firmware quirk handling (personName/persionName fallback, string-to-int casting, empty customId) | Spec field reference (section 3.4) documents all quirks; casting rules in section "Type handling" |
| REC-03 | Handler decodes and saves base64 face crop image to storage; scene image saved if present (nullable) | Date-partitioned storage per D-06; base64 data URI prefix stripping needed; scene is nullable per firmware observation |
| REC-04 | Handler inserts recognition_events row with all fields and full raw payload for forensics | Migration already exists with correct schema; RecognitionEvent model and factory needed |
| REC-05 | Events classified into three severity levels: critical (block-list match), warning (refused), info (allowed) | AlertSeverity enum per D-11/D-12; note verify_status mapping discrepancy flagged in research |
| REC-06 | Manual replay events (PushType=2) stored but not surfaced as alerts | D-13: filter by is_real_time in handler before broadcast; always store in DB |
| REC-07 | Recognition events broadcast in real time via Laravel Reverb WebSocket to all connected browsers | RecognitionAlert broadcast event on `fras.alerts` channel following CameraStatusChanged pattern |
| REC-08 | Live alert feed shows reverse-chronological events with avatar, person name, camera, severity tag, similarity score, and timestamp | Alert feed page with Inertia props and Echo listener; D-01 compact row design |
| REC-09 | Critical alerts have red left border and subtle red background; warnings use amber; info uses green | Tailwind classes for severity coloring per D-01 |
| REC-10 | Clicking an alert opens a detail modal with face crop, scene image with bounding box overlay, and full event metadata | D-05 wide modal layout; D-07 missing scene placeholder; CSS overlay for bbox |
| REC-11 | Critical (block-list) events trigger an audible alert sound in the browser | D-08/D-09: HTML5 Audio API with user gesture unlock; MP3 chime file |
| REC-12 | Each alert displays the confidence/similarity score from the camera | Similarity field in recognition_events and broadcast payload |
| REC-13 | Operator can acknowledge or dismiss an alert, recording who handled it and when | D-10: new migration for acknowledged_by/acknowledged_at/dismissed_at columns; controller endpoints |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | v13.0 | Backend framework, broadcasting, Eloquent | Already installed [VERIFIED: codebase] |
| Laravel Reverb | v1.10.0 | WebSocket server for real-time broadcasting | Already installed [VERIFIED: composer show] |
| @laravel/echo-vue | v2.3.4 | Vue composable for Echo/WebSocket listeners | Already installed [VERIFIED: npm list] |
| pusher-js | (installed) | Pusher protocol client for Echo | Already installed [VERIFIED: app.ts import] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| lucide-vue-next | v0.468.0 | Icons (Bell, BellOff, Check, X, Eye) | Alert feed UI icons [VERIFIED: package.json] |
| reka-ui | v2.6.1 | Headless dialog/modal primitives | Detail modal via shadcn dialog [VERIFIED: codebase] |
| vue-sonner | v2.0.0 | Toast notifications | Success feedback for ack/dismiss [VERIFIED: codebase] |

### No New Dependencies Required
This phase uses entirely existing stack. No new npm or composer packages needed. The audio alert uses the browser's native HTML5 Audio API. [VERIFIED: codebase analysis]

**Installation:** None required -- all dependencies are already installed.

## Architecture Patterns

### Recommended Project Structure
```
app/
├── Enums/
│   └── AlertSeverity.php           # NEW: PHP enum for severity classification
├── Events/
│   └── RecognitionAlert.php        # NEW: Broadcast event for real-time alerts
├── Http/
│   └── Controllers/
│       └── AlertController.php     # NEW: Alert feed page, acknowledge, dismiss
├── Models/
│   └── RecognitionEvent.php        # NEW: Eloquent model with Camera/Personnel relations
├── Mqtt/
│   └── Handlers/
│       └── RecognitionHandler.php  # EXISTS (stub): Full implementation
database/
├── factories/
│   └── RecognitionEventFactory.php # NEW: Factory for testing
├── migrations/
│   └── YYYY_MM_DD_HHMMSS_add_acknowledgment_columns_to_recognition_events_table.php  # NEW
resources/js/
├── components/
│   ├── AlertFeedItem.vue           # NEW: Compact alert row component
│   └── AlertDetailModal.vue        # NEW: Detail modal with images and bbox
├── composables/
│   └── useAlertSound.ts            # NEW: Audio playback with user gesture unlock
├── pages/
│   └── alerts/
│       └── Index.vue               # NEW: Alert feed page
├── types/
│   └── recognition.ts              # NEW: TypeScript types for recognition events
storage/
└── app/
    └── recognition/                # NEW: Date-partitioned image storage
        └── {YYYY-MM-DD}/
            ├── faces/              # Face crop images
            └── scenes/             # Scene images
```

### Pattern 1: RecognitionHandler Implementation (Direct Processing)
**What:** The RecognitionHandler processes RecPush events synchronously within the MQTT listener process -- no job dispatch needed.
**When to use:** Always -- recognition events are lightweight (parse JSON, decode base64, insert row, dispatch broadcast event). The MQTT listener already runs as a long-lived process.
**Why not a job:** Jobs add latency (queue round-trip) and complexity. The handler is already running in a background process. The broadcast event dispatch is the only async part and Laravel handles that via the queue connection. [VERIFIED: follows AckHandler and OnlineOfflineHandler patterns which process directly]

```php
// Source: Follows existing AckHandler pattern
class RecognitionHandler implements MqttHandler
{
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);
        if (! $data || ($data['operator'] ?? null) !== 'RecPush') {
            return;
        }

        // Extract device_id from topic: mqtt/face/{device_id}/Rec
        $segments = explode('/', $topic);
        $deviceId = $segments[2] ?? null;

        $camera = Camera::where('device_id', $deviceId)->first();
        if (! $camera) {
            Log::warning('RecPush for unknown camera', ['device_id' => $deviceId]);
            return;
        }

        $info = $data['info'] ?? [];
        // ... parse, save images, insert event, classify, broadcast
    }
}
```

### Pattern 2: AlertSeverity PHP Enum
**What:** Backed enum with `fromEvent()` static method for classification logic.
**When to use:** Every RecPush event classification and every severity display.

```php
// Source: D-11, D-12 from CONTEXT.md
enum AlertSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
    case Ignored = 'ignored';

    public static function fromEvent(int $personType, int $verifyStatus): self
    {
        // Block-list match takes priority
        if ($personType === 1) {
            return self::Critical;
        }
        // Refused entry
        if ($verifyStatus === 2) {
            return self::Warning;
        }
        // Stranger or no-match
        if (in_array($verifyStatus, [3, 0], true)) {
            return self::Ignored;
        }
        // Allow-list match (verifyStatus=1, personType=0)
        return self::Info;
    }

    public function shouldBroadcast(): bool
    {
        return $this !== self::Ignored;
    }
}
```

### Pattern 3: RecognitionAlert Broadcast Event
**What:** Broadcast event following existing `CameraStatusChanged` pattern.
**When to use:** After inserting a recognition event that should be surfaced.

```php
// Source: Follows CameraStatusChanged + EnrollmentStatusChanged pattern
class RecognitionAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $id,
        public int $camera_id,
        public string $camera_name,
        public ?int $personnel_id,
        public ?string $person_name,
        public ?string $custom_id,
        public string $severity,
        public float $similarity,
        public int $person_type,
        public ?string $face_image_url,
        public ?string $scene_image_url,
        public ?array $target_bbox,
        public string $captured_at,
        public string $created_at,
    ) {}

    public function broadcastAs(): string
    {
        return 'RecognitionAlert';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('fras.alerts');
    }
}
```

### Pattern 4: Echo Listener on Vue Alert Feed
**What:** Use `useEcho` composable from `@laravel/echo-vue` to listen for RecognitionAlert events.
**When to use:** Alert feed page component.

```typescript
// Source: Follows cameras/Index.vue Echo pattern
useEcho(
    'fras.alerts',
    '.RecognitionAlert',
    (payload: RecognitionAlertPayload) => {
        // Prepend to reactive alerts array (D-02: slide in from top)
        alerts.value.unshift(payload);
        // Cap at 50 (D-03)
        if (alerts.value.length > 50) {
            alerts.value = alerts.value.slice(0, 50);
        }
        // Play audio for critical events (D-08)
        if (payload.severity === 'critical') {
            playAlertSound();
        }
    },
);
```

### Pattern 5: Base64 Image Decoding and Storage
**What:** Strip data URI prefix, decode base64, save to date-partitioned directory.
**When to use:** RecognitionHandler for face crops and scene images.

```php
// Source: D-06 storage pattern
private function saveImage(string $dataUri, string $type, int $eventId, string $date): string
{
    // Strip "data:image/jpeg;base64," prefix
    $base64 = preg_replace('#^data:image/\w+;base64,#', '', $dataUri);
    $imageData = base64_decode($base64);

    $directory = "recognition/{$date}/{$type}s";
    $filename = "{$eventId}.jpg";
    $path = "{$directory}/{$filename}";

    Storage::disk('local')->put($path, $imageData);

    return $path;
}
```

### Pattern 6: Alert Feed Controller with Inertia
**What:** Controller serves the alert feed page and handles acknowledge/dismiss POST actions.
**When to use:** Alert feed page route.

```php
// Source: Follows PersonnelController/CameraController Inertia pattern
class AlertController extends Controller
{
    public function index(): \Inertia\Response
    {
        $events = RecognitionEvent::with(['camera', 'personnel'])
            ->whereIn('severity', ['critical', 'warning', 'info'])
            ->where('is_real_time', true)
            ->latest('captured_at')
            ->limit(50)
            ->get();

        return Inertia::render('alerts/Index', [
            'events' => $events,
        ]);
    }

    public function acknowledge(RecognitionEvent $event): RedirectResponse
    {
        $event->update([
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert acknowledged.')]);

        return back();
    }
}
```

### Anti-Patterns to Avoid
- **Dispatching a queued job for each RecPush:** Adds unnecessary latency. Process directly in the handler like AckHandler does.
- **Storing images on the `public` disk:** Recognition images should NOT be publicly accessible via URL without auth. Use the `local` disk and serve through a controller route with auth middleware.
- **Using `router.post()` for acknowledge/dismiss:** Use `useHttp` for inline acknowledge/dismiss since they're lightweight JSON actions that shouldn't trigger a full Inertia page visit. This keeps the feed responsive.
- **Querying the DB on every Echo event:** The broadcast payload should contain all data needed to render the alert row. No additional API calls needed.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| WebSocket broadcasting | Custom WebSocket server | Laravel Reverb + Echo | Already configured, handles auth, reconnection, channel management |
| Real-time Vue updates | Manual polling or SSE | `useEcho` from `@laravel/echo-vue` | Already used in cameras/Index.vue and personnel/Show.vue; handles subscription lifecycle |
| Audio playback | Custom audio library | HTML5 Audio API (`new Audio()`) | Browser-native, no dependency; only need user gesture unlock pattern |
| Modal dialog | Custom overlay component | shadcn Dialog from `resources/js/components/ui/dialog/` | Already in component library; accessible, keyboard-friendly |
| Severity badges | Custom colored spans | shadcn Badge from `resources/js/components/ui/badge/` | Already used for person_type display in personnel pages |
| Image serving with auth | Public storage URLs | Laravel `Storage::disk('local')->get()` via controller route | Recognition images need auth protection |
| Relative time formatting | Moment.js or date-fns | Existing `formatRelativeTime()` pattern | Already implemented in cameras/Index.vue and personnel/Show.vue; extract to shared utility |

**Key insight:** This phase's entire real-time pipeline is already scaffolded by Phases 1, 2, and 4. The MQTT handler interface, topic routing, broadcast events on `fras.alerts`, and Echo listeners in Vue are all established patterns. Phase 5 fills in the RecognitionHandler implementation and adds the alert feed UI.

## Common Pitfalls

### Pitfall 1: VerifyStatus Value Mapping Discrepancy
**What goes wrong:** D-11 specifies `verify_status=0` for both Critical and Info, but the spec field reference says VerifyStatus 0 = "Nothing" and the observed firmware uses VerifyStatus 1 for allow and 2 for refuse.
**Why it happens:** The CONTEXT D-11 mapping was authored based on a simplified reading. The spec and firmware observations show: VerifyStatus 0="Nothing" (not observed in testing), 1="Allow", 2="Refuse", 3="Not registered".
**How to avoid:** The `AlertSeverity::fromEvent()` logic should prioritize `PersonType` for Critical (PersonType=1 means block-list regardless of VerifyStatus), then check VerifyStatus=2 for Warning, then VerifyStatus=3 or 0 for Ignored, then default to Info for VerifyStatus=1 + PersonType=0. This matches the spec's alert classification table (section 3.4).
**Warning signs:** Tests where allow-list events get classified as Ignored instead of Info.

### Pitfall 2: Browser Audio Autoplay Restrictions
**What goes wrong:** Attempting to play audio without a prior user gesture results in a `NotAllowedError` (Chrome, Firefox, Safari all block autoplay).
**Why it happens:** Browsers require at least one user-initiated interaction (click, tap, keypress) before allowing `Audio.play()`.
**How to avoid:** D-09 specifies an "Enable Alert Sound" button. When the operator clicks this button, call `audioElement.play()` (even briefly) to unlock the audio context. Store the unlock state. Subsequent `play()` calls for alert chimes will work. [CITED: MDN Web Docs autoplay policy]
**Warning signs:** No sound on first critical event; works only after user interacts with page.

### Pitfall 3: Base64 Data URI Prefix Must Be Stripped
**What goes wrong:** Calling `base64_decode()` on the full data URI string (including `data:image/jpeg;base64,` prefix) produces corrupt image data.
**Why it happens:** The camera sends `"pic": "data:image/jpeg;base64,/9j/4AAQ..."` -- the prefix is NOT base64 data.
**How to avoid:** Strip the prefix before decoding: `preg_replace('#^data:image/\w+;base64,#', '', $dataUri)` then `base64_decode()`.
**Warning signs:** Saved image files are corrupt or 0 bytes.

### Pitfall 4: String-to-Int Casting for Numeric Fields
**What goes wrong:** Comparing string "1" to int 1 with strict equality fails; storing string "83.000000" as similarity loses precision context.
**Why it happens:** Camera firmware sends most numeric fields as strings (VerifyStatus, PersonType, similarity1, RecordID, isNoMask) per spec section "Type handling".
**How to avoid:** Explicitly cast all numeric fields in the handler: `(int) ($info['VerifyStatus'] ?? 0)`, `(float) ($info['similarity1'] ?? 0)`. Document which fields are already ints (Sendintime, PushType, targetPosInScene array contents).
**Warning signs:** Severity classification produces wrong results; similarity stored as 0.

### Pitfall 5: Empty customId Lookup
**What goes wrong:** Personnel lookup by `custom_id` when `customId` is empty string returns no match, losing the ability to correlate the event with a personnel record.
**Why it happens:** Camera firmware sends `"customId": ""` when personnel were enrolled via the camera's own web UI rather than the platform.
**How to avoid:** Check `customId` first; if empty, fall back to looking up by `camera_person_id` (the `personId` field from the camera). If neither matches, set `personnel_id` to null. Log the fallback for admin awareness.
**Warning signs:** Events for UI-enrolled personnel always show as "Unknown person".

### Pitfall 6: Image Storage Path Must Be Set After Event Insert
**What goes wrong:** Saving images before creating the recognition event means the event ID is unknown, so filenames can't be `{event_id}.jpg` as specified in D-06.
**Why it happens:** The event ID is auto-incremented by the database.
**How to avoid:** Insert the recognition event first (with null image paths), then save images using the event ID as filename, then update the event with the image paths. Or use a two-step approach: generate a UUID for the filename, insert with that path.
**Warning signs:** Image filenames don't match event IDs; images saved with temporary names.

### Pitfall 7: personName vs persionName Field Name
**What goes wrong:** Reading only `personName` from the payload fails on older firmware that uses the misspelled `persionName`.
**Why it happens:** Spec PDF documents `persionName` (typo), real firmware uses `personName`. Different firmware versions may use either.
**How to avoid:** Read `personName` first, fall back to `persionName`: `$info['personName'] ?? $info['persionName'] ?? null`. [VERIFIED: Spec section 3.4 "Field name discrepancy"]
**Warning signs:** Person name is null for events from certain camera firmware versions.

### Pitfall 8: Vue Reactivity with Large Alert Arrays
**What goes wrong:** Performance degrades when the alert feed processes many rapid events, causing Vue to reactively update the entire list.
**Why it happens:** `unshift()` on a reactive array triggers reactivity for the whole array.
**How to avoid:** D-03 caps at 50 items, which is manageable. Use `shallowRef` or limit re-renders. Avoid deep watching the alerts array.
**Warning signs:** UI jank when multiple events arrive within seconds.

## Code Examples

### RecPush Payload Parsing (Full Field Extraction)
```php
// Source: HDS-FRAS-Spec-v1.1.md section 3.4 field reference + type handling
$info = $data['info'] ?? [];

$parsed = [
    'custom_id' => trim($info['customId'] ?? '') ?: null,
    'camera_person_id' => $info['personId'] ?? null,
    'record_id' => (int) ($info['RecordID'] ?? 0),
    'verify_status' => (int) ($info['VerifyStatus'] ?? 0),
    'person_type' => (int) ($info['PersonType'] ?? 0),
    'similarity' => (float) ($info['similarity1'] ?? 0),
    'is_real_time' => ($info['Sendintime'] ?? 0) === 1, // Already int
    'name_from_camera' => $info['personName'] ?? $info['persionName'] ?? null,
    'facesluice_id' => $info['facesluiceId'] ?? null,
    'id_card' => trim($info['idCard'] ?? '') ?: null,
    'phone' => trim($info['telnum'] ?? '') ?: null, // Trim space char
    'is_no_mask' => (int) ($info['isNoMask'] ?? 0),
    'target_bbox' => $info['targetPosInScene'] ?? null, // Already int array
    'captured_at' => $info['time'] ?? now()->format('Y-m-d H:i:s'),
    'is_manual_replay' => ($info['PushType'] ?? 0) === 2, // Already int
];
```

### Serving Recognition Images Through Controller
```php
// Source: Architecture decision -- images on local disk, not public
// Route: GET /alerts/{event}/face, GET /alerts/{event}/scene
public function faceImage(RecognitionEvent $event): Response
{
    if (! $event->face_image_path) {
        abort(404);
    }

    return Storage::disk('local')->response($event->face_image_path);
}
```

### Audio Unlock Pattern (Vue Composable)
```typescript
// Source: Browser autoplay policy + D-09
export function useAlertSound() {
    let audio: HTMLAudioElement | null = null;
    const isEnabled = ref(false);

    function enable() {
        if (!audio) {
            audio = new Audio('/sounds/alert-chime.mp3');
        }
        // User gesture unlocks audio context
        audio.play().then(() => {
            audio!.pause();
            audio!.currentTime = 0;
            isEnabled.value = true;
        }).catch(() => {
            isEnabled.value = false;
        });
    }

    function play() {
        if (isEnabled.value && audio) {
            const clone = audio.cloneNode() as HTMLAudioElement;
            clone.play().catch(() => {});
        }
    }

    return { isEnabled, enable, disable: () => { isEnabled.value = false; }, play };
}
```

### Bounding Box Overlay (CSS Approach)
```vue
<!-- Source: D-05 scene image with bbox overlay -->
<template>
    <div class="relative inline-block">
        <img :src="sceneImageUrl" alt="Scene" class="max-w-full" ref="sceneImg" />
        <div
            v-if="targetBbox"
            class="absolute border-2 border-yellow-400 pointer-events-none"
            :style="bboxStyle"
        />
    </div>
</template>

<script setup lang="ts">
// target_bbox is [x1, y1, x2, y2] in scene image pixel coordinates
// CSS overlay scales with image via percentage positioning
const bboxStyle = computed(() => {
    if (!props.targetBbox || !imageNaturalSize.value) return {};
    const [x1, y1, x2, y2] = props.targetBbox;
    const { width, height } = imageNaturalSize.value;
    return {
        left: `${(x1 / width) * 100}%`,
        top: `${(y1 / height) * 100}%`,
        width: `${((x2 - x1) / width) * 100}%`,
        height: `${((y2 - y1) / height) * 100}%`,
    };
});
</script>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Laravel Echo (standalone) | `@laravel/echo-vue` composable | Inertia v3 / 2025 | Use `useEcho()` not manual `window.Echo.private()` |
| `Inertia::lazy()` / `LazyProp` | `Inertia::optional()` | Inertia v3 | Do not use lazy() for optional props |
| `router.cancel()` | `router.cancelAll()` | Inertia v3 | v3 breaking change |

**Deprecated/outdated:**
- `window.Echo` global: Use `useEcho` composable from `@laravel/echo-vue` instead [VERIFIED: existing codebase uses useEcho]

## Assumptions Log

> List all claims tagged `[ASSUMED]` in this research.

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Alert chime MP3 file (~1-2 second professional chime) needs to be sourced or created and placed in `public/sounds/` | Audio pattern | Low -- can use any short audio file; swap later |
| A2 | CSS percentage-based bounding box overlay will scale correctly with responsive images | Bounding box pattern | Medium -- if image aspect ratio is constrained by CSS, bbox may misalign; needs testing |
| A3 | `useHttp` is the best approach for acknowledge/dismiss (inline actions without full page visit) | Anti-patterns | Low -- could use `router.post` with `preserveScroll` as fallback; both work |
| A4 | The `local` disk (storage/app/private) is the correct choice for recognition images (not public) | Image storage | Medium -- if images need to be accessible from camera network, would need public; but images are for operator viewing only |
| A5 | D-11 verify_status=0 for Critical/Info was a simplification; actual mapping should follow spec (PersonType=1 for Critical, VerifyStatus=2 for Warning, VerifyStatus=1+PersonType=0 for Info) | Pitfall 1 | HIGH -- wrong mapping means wrong severity classification; must verify with user |

## Open Questions

1. **VerifyStatus mapping in D-11 vs spec**
   - What we know: D-11 says verify_status=0 for both Critical and Info. The spec says VerifyStatus 0="Nothing", 1="Allow", 2="Refuse". Observed firmware: only 1 and 2 seen.
   - What's unclear: Whether D-11's verify_status=0 was intentional or a simplification. The practical behavior should be: PersonType=1 -> Critical (regardless of VerifyStatus), VerifyStatus=2 -> Warning, VerifyStatus in [0,3] -> Ignored, VerifyStatus=1 + PersonType=0 -> Info.
   - Recommendation: Implement based on the spec's alert classification table (section 3.4) since it matches observed firmware behavior. The `fromEvent()` method should prioritize PersonType=1 for Critical, then branch on VerifyStatus. This is consistent with D-11's intent even if the exact VerifyStatus numbers differ.

2. **Alert sound file**
   - What we know: D-08 says "short, professional chime" (~1-2 seconds).
   - What's unclear: Whether a specific sound file exists or needs to be sourced.
   - Recommendation: Use a royalty-free alert chime MP3 from a public domain source, or generate a simple tone. Include in `public/sounds/alert-chime.mp3`. MP3 format has best cross-browser support.

3. **Image serving route protection**
   - What we know: Recognition images should not be publicly accessible. They contain face crops of individuals.
   - What's unclear: Whether a dedicated image-serving controller route is needed or if inline base64 in the broadcast payload is acceptable.
   - Recommendation: Store images on the `local` disk. Serve via a controller route with `auth` middleware (`GET /alerts/{event}/face`, `GET /alerts/{event}/scene`). The broadcast event payload includes the URL to these routes, not base64 data (which would bloat the WebSocket message).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 |
| Config file | `tests/Pest.php` |
| Quick run command | `php artisan test --compact --filter=RecognitionTest` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| REC-01 | RecognitionHandler processes RecPush events from correct topic | unit | `php artisan test --compact --filter=RecognitionHandlerTest` | No -- Wave 0 |
| REC-02 | Handler parses payload with firmware quirks (personName fallback, casting, empty customId) | unit | `php artisan test --compact --filter=RecognitionHandlerTest` | No -- Wave 0 |
| REC-03 | Handler decodes base64 face crop and saves to storage; handles missing scene | unit | `php artisan test --compact --filter=RecognitionHandlerTest` | No -- Wave 0 |
| REC-04 | Handler inserts recognition_events row with all fields and raw payload | unit | `php artisan test --compact --filter=RecognitionHandlerTest` | No -- Wave 0 |
| REC-05 | AlertSeverity enum classifies events correctly | unit | `php artisan test --compact --filter=AlertSeverityTest` | No -- Wave 0 |
| REC-06 | Manual replay events (PushType=2) stored but not broadcast | unit | `php artisan test --compact --filter=RecognitionHandlerTest` | No -- Wave 0 |
| REC-07 | RecognitionAlert broadcasts on fras.alerts channel | unit | `php artisan test --compact --filter=RecognitionAlertTest` | No -- Wave 0 |
| REC-08 | Alert feed page loads with events | feature | `php artisan test --compact --filter=AlertControllerTest` | No -- Wave 0 |
| REC-09 | (UI) Severity coloring correct | manual-only | Visual verification | N/A |
| REC-10 | (UI) Detail modal shows face crop, scene with bbox, metadata | manual-only | Visual verification | N/A |
| REC-11 | (UI) Critical events trigger audio | manual-only | Visual verification | N/A |
| REC-12 | Similarity score present in broadcast payload and page props | unit | `php artisan test --compact --filter=RecognitionAlertTest` | No -- Wave 0 |
| REC-13 | Acknowledge/dismiss updates event with user and timestamp | feature | `php artisan test --compact --filter=AlertControllerTest` | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --compact tests/Feature/Recognition/`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Recognition/RecognitionHandlerTest.php` -- covers REC-01 through REC-04, REC-06
- [ ] `tests/Feature/Recognition/AlertSeverityTest.php` -- covers REC-05
- [ ] `tests/Feature/Recognition/RecognitionAlertTest.php` -- covers REC-07, REC-12
- [ ] `tests/Feature/Recognition/AlertControllerTest.php` -- covers REC-08, REC-13
- [ ] `database/factories/RecognitionEventFactory.php` -- shared factory

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Auth middleware on all alert routes and image serving |
| V3 Session Management | no | Handled by existing Fortify/session setup |
| V4 Access Control | yes | All alert routes behind `auth` + `verified` middleware; image routes require auth |
| V5 Input Validation | yes | MQTT payload validation (JSON decode, field type checks, base64 validation) |
| V6 Cryptography | no | No cryptographic operations in this phase |

### Known Threat Patterns

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Malformed MQTT payload injection | Tampering | Validate operator field, type-check all fields, handle malformed JSON gracefully |
| Oversized base64 image DoS | Denial of Service | Check decoded image size before saving; face crop max 1MB, scene max 2MB per spec |
| Unauthorized image access | Information Disclosure | Serve images via auth-protected controller route, not public storage |
| XSS via person name from camera | Tampering | Vue template auto-escapes all interpolated values; no v-html usage |
| WebSocket channel eavesdropping | Information Disclosure | `fras.alerts` is a PrivateChannel with auth callback; only authenticated users can subscribe |

## Sources

### Primary (HIGH confidence)
- `docs/HDS-FRAS-Spec-v1.1.md` -- RecPush payload schema, field reference, type handling, firmware quirks, alert classification
- `app/Mqtt/Handlers/AckHandler.php` -- Handler implementation pattern
- `app/Events/CameraStatusChanged.php` -- Broadcast event pattern
- `resources/js/pages/cameras/Index.vue` -- Echo listener pattern
- `database/migrations/2026_04_10_000003_create_recognition_events_table.php` -- Existing table schema
- `config/hds.php` -- Retention and alert configuration
- `routes/channels.php` -- Channel authorization pattern

### Secondary (MEDIUM confidence)
- `app/Mqtt/Handlers/OnlineOfflineHandler.php` -- Direct processing pattern (no job dispatch)
- `resources/js/pages/personnel/Show.vue` -- Echo listener pattern with reactive state management

### Tertiary (LOW confidence)
- Audio unlock pattern -- based on browser autoplay policy documentation [ASSUMED: standard approach]
- CSS bounding box overlay scaling -- based on responsive image positioning [ASSUMED: needs testing]

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already installed and used in project
- Architecture: HIGH -- follows established patterns from Phases 1, 2, 4
- Handler implementation: HIGH -- spec is thorough, firmware behavior documented
- Alert severity mapping: MEDIUM -- D-11 has verify_status discrepancy vs spec (see Pitfall 1)
- Frontend patterns: HIGH -- Echo listener, dialog, badge all have existing examples
- Audio notifications: MEDIUM -- standard browser API but autoplay restrictions require careful UX
- Bounding box overlay: MEDIUM -- CSS approach should work but needs testing with real images

**Research date:** 2026-04-11
**Valid until:** 2026-05-11 (stable -- no fast-moving dependencies)

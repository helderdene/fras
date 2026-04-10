# Architecture Research

**Domain:** Face Recognition Alert System with MQTT Camera Integration
**Researched:** 2026-04-10
**Confidence:** HIGH

## System Overview

```
                        ┌──────────────────────────────────────────────────────────────┐
                        │                    AI IP Cameras (up to 8)                    │
                        │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐        │
                        │  │ Camera 1 │ │ Camera 2 │ │ Camera 3 │ │ Camera N │        │
                        │  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘        │
                        └───────┼─────────────┼─────────────┼─────────────┼────────────┘
                                │  MQTT Pub   │  MQTT Pub   │  MQTT Pub   │
                                │  (Rec/Ack/  │             │             │
                                │  heartbeat) │             │             │
                        ┌───────▼─────────────▼─────────────▼─────────────▼────────────┐
                        │                   Mosquitto MQTT Broker                       │
                        │                  (plain MQTT, QoS 0)                          │
                        └───────┬──────────────────────────────────────────┬────────────┘
                                │  MQTT Sub                                │ MQTT Pub
                                │  (all topics)                            │ (commands)
                        ┌───────▼──────────────────────────────────────────▼────────────┐
                        │              Laravel Application (Monolith)                    │
                        │                                                                │
                        │  ┌─────────────────┐    ┌──────────────────┐                   │
                        │  │  MQTT Listener   │───▶│  Queue Workers   │                  │
                        │  │ (artisan daemon) │    │ (process events) │                  │
                        │  └─────────────────┘    └────────┬─────────┘                   │
                        │                                   │                             │
                        │  ┌───────────────────┐   ┌───────▼──────────┐                  │
                        │  │  HTTP Controllers  │   │  Event System    │                  │
                        │  │  (Inertia pages)   │   │  (broadcast)     │                  │
                        │  └────────┬──────────┘   └───────┬──────────┘                  │
                        │           │                       │                             │
                        │  ┌────────▼──────────┐   ┌───────▼──────────┐                  │
                        │  │    MySQL DB        │   │  Laravel Reverb   │                 │
                        │  │  (all state)       │   │  (WebSocket)      │                 │
                        │  └───────────────────┘   └───────┬──────────┘                  │
                        │                                   │                             │
                        │  ┌───────────────────┐            │                             │
                        │  │  Storage (public)  │            │                             │
                        │  │  face/scene images │            │                             │
                        │  │  personnel photos  │            │                             │
                        │  └───────────────────┘            │                             │
                        └───────────────────────────────────┼────────────────────────────┘
                                                            │ WebSocket
                        ┌───────────────────────────────────▼────────────────────────────┐
                        │                    Browser (Vue 3 SPA)                          │
                        │  ┌────────────┐  ┌───────────┐  ┌───────────┐                  │
                        │  │ Map View   │  │ Alert Feed │  │ Admin     │                  │
                        │  │ (Mapbox)   │  │ (Echo)     │  │ Pages     │                  │
                        │  └────────────┘  └───────────┘  └───────────┘                  │
                        └────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Implementation |
|-----------|----------------|----------------|
| AI IP Cameras | Onboard face recognition, publish RecPush/Ack/heartbeat events via MQTT | Hardware with firmware; not under our control |
| Mosquitto MQTT Broker | Route messages between cameras and Laravel | Standalone service, `mosquitto` on Linux |
| MQTT Listener | Long-running artisan command subscribing to all camera topics | `php artisan mqtt:listen` via `php-mqtt/laravel-client`, supervised by Supervisor |
| Queue Workers | Process dispatched jobs (recognition events, enrollment ACKs, image storage) | `php artisan queue:work` via database driver |
| HTTP Controllers | Serve Inertia pages, handle CRUD for cameras/personnel, trigger enrollment | Standard Laravel controllers with Inertia::render() |
| Event System | Dispatch Laravel events, broadcast to WebSocket channels | Laravel Events + `ShouldBroadcast` interface |
| Laravel Reverb | WebSocket server pushing real-time alerts to browsers | `php artisan reverb:start`, first-party Laravel package |
| MySQL Database | Persist cameras, personnel, enrollments, recognition events | MySQL 8.0+ (migration from existing SQLite) |
| Public Storage | Serve personnel photos (camera-accessible), face crops, scene images | Laravel `public` disk with `storage:link` |
| Vue 3 SPA | Dashboard with map, alert feed, camera list; admin pages for personnel/cameras | Inertia pages with Laravel Echo for real-time |
| Mapbox GL JS | Interactive map rendering camera locations with status indicators | Direct Mapbox GL JS v3 integration (no Vue wrapper) |

## Recommended Project Structure

New directories and files added to the existing Laravel + Vue codebase:

```
app/
├── Console/
│   └── Commands/
│       └── MqttListenCommand.php       # Long-running MQTT subscriber daemon
├── Events/
│   ├── RecognitionAlertEvent.php        # Broadcast to WebSocket (ShouldBroadcast)
│   └── CameraStatusChangedEvent.php     # Broadcast camera online/offline
├── Jobs/
│   ├── ProcessRecognitionEvent.php      # Parse RecPush, save images, classify alert
│   ├── ProcessEnrollmentAck.php         # Handle EditPersonsNew-Ack response
│   ├── SyncPersonnelToCamera.php        # Push enrollment via MQTT EditPersonsNew
│   ├── DeletePersonnelFromCamera.php    # Push delete via MQTT DeletePersons
│   └── ProcessHeartbeat.php             # Update camera last_seen_at timestamp
├── Http/
│   └── Controllers/
│       ├── CameraController.php         # CRUD cameras, map data
│       ├── PersonnelController.php      # CRUD personnel, enrollment status
│       ├── DashboardController.php      # Main dashboard page with map + alerts
│       └── RecognitionEventController.php # Event history, search, filter
├── Models/
│   ├── Camera.php                       # id, device_id, name, location, lat, lng, status
│   ├── Personnel.php                    # id, custom_id, name, person_type, photo_path
│   ├── CameraEnrollment.php            # camera_id, personnel_id, status, synced_at
│   └── RecognitionEvent.php            # camera_id, personnel_id, severity, face/scene paths
├── Services/
│   ├── MqttPublisher.php               # Publish commands to cameras (enrollment, delete)
│   └── ImageProcessor.php              # Resize, compress, hash via Intervention Image v3
├── Enums/
│   ├── PersonType.php                   # Allow, Block
│   ├── AlertSeverity.php                # Critical, Warning, Info
│   ├── CameraStatus.php                 # Online, Offline
│   └── EnrollmentStatus.php            # Pending, Synced, Failed
config/
│   └── mqtt-client.php                  # php-mqtt/laravel-client configuration
routes/
│   ├── channels.php                     # WebSocket channel authorization
│   └── fras.php                         # FRAS-specific web routes (included from web.php)
resources/js/
├── pages/
│   ├── fras/
│   │   ├── Dashboard.vue                # Full-viewport map + alerts + camera rail
│   │   ├── Cameras.vue                  # Camera management list
│   │   ├── Personnel.vue                # Personnel list with enrollment status
│   │   ├── PersonnelDetail.vue          # Per-camera enrollment status, retry
│   │   └── EventHistory.vue             # Searchable recognition event log
├── components/
│   ├── fras/
│   │   ├── MapView.vue                  # Mapbox GL JS wrapper
│   │   ├── CameraMarker.vue             # Camera pin with status/pulse
│   │   ├── AlertFeed.vue                # Real-time alert list
│   │   ├── AlertDetailModal.vue         # Face crop + metadata modal
│   │   ├── CameraRail.vue              # Sidebar camera list with status dots
│   │   └── StatusBar.vue               # Connection status, camera counts
├── composables/
│   ├── useAlertFeed.ts                  # Echo listener + reactive alert array
│   ├── useCameraStatus.ts              # Echo listener for camera status changes
│   └── useMapboxMap.ts                 # Map initialization, marker management
```

### Structure Rationale

- **`app/Console/Commands/`:** The MQTT listener is an artisan command because it runs as a supervised daemon process, not a web request handler.
- **`app/Jobs/`:** Every MQTT message type dispatches to a dedicated job. This keeps the MQTT listener thin (just routing) and makes each processing step independently testable and retryable.
- **`app/Services/`:** `MqttPublisher` wraps outbound MQTT publish calls; `ImageProcessor` wraps Intervention Image. These are injected into jobs/controllers via the service container.
- **`app/Enums/`:** PHP 8.1+ backed enums for type safety on person types, alert severities, camera status, enrollment status.
- **`routes/fras.php`:** Separate route file included from `web.php` to keep FRAS routes organized without polluting existing routes.
- **`resources/js/pages/fras/`:** Namespaced under `fras/` to keep FRAS pages distinct from existing settings/auth pages. Dynamic layout resolution in `app.ts` will assign the appropriate layout.
- **`resources/js/composables/`:** Vue composables encapsulate Echo subscriptions and Mapbox interactions, keeping page components declarative.

## Architectural Patterns

### Pattern 1: Thin MQTT Listener, Fat Jobs

**What:** The MQTT subscriber command does minimal work -- it parses the topic to identify the message type, then dispatches a specific Laravel job for each type. All business logic lives in the job, not the listener.

**When to use:** Always for MQTT message handling. The listener runs in an infinite loop and must stay responsive.

**Trade-offs:**
- Pro: Each message type is independently testable, retryable via queue
- Pro: MQTT listener never blocks on heavy processing
- Pro: Failed processing does not crash the MQTT connection
- Con: Adds queue latency (typically <100ms with database driver)

**Example:**
```php
// MqttListenCommand.php
$mqtt->subscribe('AI/{deviceId}/Rec', function (string $topic, string $message) {
    $deviceId = Str::between($topic, 'AI/', '/Rec');
    ProcessRecognitionEvent::dispatch($deviceId, json_decode($message, true));
});

$mqtt->subscribe('AI/{deviceId}/basic', function (string $topic, string $message) {
    $deviceId = Str::between($topic, 'AI/', '/basic');
    ProcessHeartbeat::dispatch($deviceId);
});

$mqtt->loop(true); // Blocking event loop
```

### Pattern 2: Event Broadcasting Bridge

**What:** Queue jobs process MQTT data, persist to database, then fire a Laravel event implementing `ShouldBroadcast`. Reverb pushes the event payload to connected browsers via WebSocket. Laravel Echo on the frontend listens on the channel.

**When to use:** Whenever processed MQTT data needs to reach the browser in real time.

**Trade-offs:**
- Pro: Clean separation -- MQTT world does not touch WebSocket world directly
- Pro: Broadcast payload can be shaped differently from MQTT payload
- Pro: Channel authorization handled by Laravel
- Con: Extra hop (MQTT -> Job -> Event -> Reverb -> Browser)

**Example:**
```php
// ProcessRecognitionEvent job
public function handle(): void
{
    $camera = Camera::where('device_id', $this->deviceId)->firstOrFail();
    $personnel = Personnel::where('custom_id', $this->data['customId'])->first();

    $event = RecognitionEvent::create([...]);

    RecognitionAlertEvent::dispatch($event); // ShouldBroadcast
}

// RecognitionAlertEvent implements ShouldBroadcast
public function broadcastOn(): Channel
{
    return new Channel('fras.alerts'); // Public channel (single admin, no auth needed)
}
```

### Pattern 3: Reactive Map with Composable State

**What:** A Vue composable manages Mapbox GL JS map instance, camera markers, and real-time updates. Echo events trigger marker animations (pulse on recognition, color change on status). The map is NOT wrapped in a Vue component library -- direct Mapbox GL JS API for full control.

**When to use:** For the dashboard map that must respond to both user interaction and server-pushed events.

**Trade-offs:**
- Pro: Full Mapbox GL JS API access without wrapper limitations
- Pro: Vue reactivity drives marker state, Mapbox handles rendering
- Pro: Custom animations (pulse, flash) straightforward with direct API
- Con: More setup code than a Vue wrapper library
- Con: Must manually clean up map resources on component unmount

**Example:**
```typescript
// useMapboxMap.ts
export function useMapboxMap(container: Ref<HTMLElement | null>) {
    const map = shallowRef<mapboxgl.Map | null>(null);
    const markers = reactive(new Map<number, mapboxgl.Marker>());

    function updateCameraStatus(cameraId: number, status: 'online' | 'offline') {
        const marker = markers.get(cameraId);
        if (marker) {
            marker.getElement().classList.toggle('camera-offline', status === 'offline');
        }
    }

    function pulseCamera(cameraId: number) {
        const marker = markers.get(cameraId);
        if (marker) {
            marker.getElement().classList.add('camera-pulse');
            setTimeout(() => marker.getElement().classList.remove('camera-pulse'), 2000);
        }
    }

    onUnmounted(() => map.value?.remove());

    return { map, markers, updateCameraStatus, pulseCamera };
}
```

### Pattern 4: WithoutOverlapping Enrollment Queue

**What:** Enrollment jobs (SyncPersonnelToCamera) use Laravel's `WithoutOverlapping` middleware keyed by camera device ID. Only one enrollment batch per camera is in flight at any time. ACK responses are correlated by matching device ID + message timing.

**When to use:** All outbound camera commands (enrollment, deletion).

**Trade-offs:**
- Pro: Respects camera firmware limitation of one batch in-flight
- Pro: Failed enrollments do not pile up blocking subsequent attempts
- Con: Enrollment throughput limited to serial per camera

**Example:**
```php
// SyncPersonnelToCamera job
public function middleware(): array
{
    return [
        new WithoutOverlapping("camera-enrollment-{$this->camera->device_id}")
            ->releaseAfter(120), // 2-minute timeout for ACK
    ];
}
```

## Data Flow

### Recognition Event Flow (Camera -> Browser)

```
Camera detects face
    │
    ▼
Camera publishes to MQTT topic: AI/{deviceId}/Rec
    │
    ▼
Mosquitto routes to subscriber
    │
    ▼
MqttListenCommand callback fires
    │
    ├── Parse topic to extract deviceId
    ├── Decode JSON payload
    └── Dispatch ProcessRecognitionEvent job to queue
            │
            ▼
        Queue worker picks up job
            │
            ├── Look up Camera by device_id
            ├── Look up Personnel by customId (nullable for strangers)
            ├── Download/save face crop image to storage
            ├── Download/save scene image to storage (if present)
            ├── Classify severity: block -> Critical, refused -> Warning, else Info
            ├── Insert recognition_events row
            └── Fire RecognitionAlertEvent (ShouldBroadcast)
                    │
                    ▼
                Reverb pushes event over WebSocket
                    │
                    ▼
                Laravel Echo in browser receives event
                    │
                    ├── useAlertFeed composable prepends to reactive array
                    ├── AlertFeed component re-renders with new entry
                    ├── useMapboxMap.pulseCamera() animates marker
                    └── If severity === 'critical': play audio alert
```

### Personnel Enrollment Flow (Admin -> Camera)

```
Admin creates/updates Personnel via PersonnelController
    │
    ├── Validate input
    ├── Process photo via ImageProcessor (resize, compress, hash)
    ├── Save Personnel to DB
    ├── Store photo on public disk (camera must HTTP-fetch it)
    └── For each active Camera:
            │
            ├── Create/update CameraEnrollment row (status: Pending)
            └── Dispatch SyncPersonnelToCamera job
                    │
                    ▼ (WithoutOverlapping per camera)
                Queue worker picks up job
                    │
                    ├── Build EditPersonsNew MQTT payload
                    │   (personId, personName, customId, picURI, personType)
                    ├── Publish to MQTT topic: AI/{deviceId}/EditPersonsNew
                    └── Camera receives, downloads photo, enrolls face
                            │
                            ▼
                        Camera publishes ACK: AI/{deviceId}/Ack
                            │
                            ▼
                        MqttListenCommand callback fires
                            │
                            └── Dispatch ProcessEnrollmentAck job
                                    │
                                    ├── Parse success/failure per person
                                    ├── Update CameraEnrollment status (Synced/Failed)
                                    └── Optionally broadcast status update
```

### Camera Heartbeat Flow (Liveness Detection)

```
Camera publishes heartbeat to: AI/{deviceId}/basic (periodic)
    │
    ▼
MqttListenCommand dispatches ProcessHeartbeat job
    │
    ├── Update Camera.last_heartbeat_at = now()
    ├── If Camera was Offline, set status = Online
    └── Fire CameraStatusChangedEvent if status changed

Scheduled command runs every 60 seconds:
    │
    ├── Query cameras WHERE last_heartbeat_at < now() - 90 seconds
    ├── Mark matching cameras as Offline
    └── Fire CameraStatusChangedEvent for each newly-offline camera
```

### Key Data Flows

1. **Recognition (inbound):** Camera MQTT -> Listener -> Job -> DB + Storage -> Broadcast -> Browser
2. **Enrollment (outbound):** Browser -> Controller -> DB -> Job -> MQTT -> Camera -> ACK -> Listener -> Job -> DB
3. **Heartbeat (monitoring):** Camera MQTT -> Listener -> Job -> DB timestamp; Scheduler -> Check stale -> DB update -> Broadcast
4. **Photo serving (cross-network):** Admin uploads -> ImageProcessor -> Public disk -> `storage:link` -> HTTP URL -> Camera fetches via picURI

## Build Order (Dependencies Between Components)

The following order respects component dependencies. Each layer depends on what comes before it.

```
Phase 1: Foundation
├── MySQL migration (from SQLite)
├── Models + Migrations (cameras, personnel, camera_enrollments, recognition_events)
├── Enums (PersonType, AlertSeverity, CameraStatus, EnrollmentStatus)
└── Basic CRUD controllers + Inertia pages (cameras, personnel)
    └── Depends on: nothing new beyond existing app

Phase 2: MQTT Integration
├── Mosquitto broker setup
├── php-mqtt/laravel-client config
├── MqttListenCommand (subscriber daemon)
├── MqttPublisher service (outbound commands)
├── ProcessHeartbeat job
├── Camera liveness (scheduled offline detection)
└── Supervisor config for mqtt:listen
    └── Depends on: Phase 1 (Camera model, DB)

Phase 3: Personnel Enrollment
├── ImageProcessor service (Intervention Image v3)
├── Photo storage on public disk
├── SyncPersonnelToCamera job (WithoutOverlapping)
├── DeletePersonnelFromCamera job
├── ProcessEnrollmentAck job
└── Personnel admin UI (enrollment status, retry)
    └── Depends on: Phase 2 (MQTT publisher, camera model)

Phase 4: Recognition Event Processing
├── ProcessRecognitionEvent job
├── Alert classification logic
├── Face/scene image storage
├── RecognitionEvent model persistence
└── Event history page (search, filter)
    └── Depends on: Phase 2 (MQTT listener), Phase 1 (models)

Phase 5: Real-Time Dashboard
├── Laravel Reverb setup
├── RecognitionAlertEvent (ShouldBroadcast)
├── CameraStatusChangedEvent (ShouldBroadcast)
├── Laravel Echo + channel authorization
├── Mapbox GL JS map with camera markers
├── useMapboxMap composable
├── useAlertFeed composable
├── useCameraStatus composable
├── Full dashboard layout (map + rail + feed + status bar)
├── Alert detail modal
├── Audio alert for critical events
└── Map style toggle (dark/light)
    └── Depends on: Phase 4 (events to broadcast), Phase 2 (camera status)

Phase 6: Operations
├── Storage retention commands (30-day scene, 90-day face cleanup)
├── Supervisor production config
└── Deployment checklist
    └── Depends on: Phase 4 (stored images to clean), Phase 2 (daemon management)
```

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 1-8 cameras (target) | Single server, database queue, single MQTT listener process. This architecture handles it easily. |
| 8-20 cameras | Recognition events may spike. Move queue driver from database to Redis for lower latency. Add queue workers (numprocs=2-3). |
| 20-50 cameras | MQTT listener may struggle with message volume. Consider splitting into topic-specific listeners. Reverb horizontal scaling via Redis pub/sub. |

### Scaling Priorities

1. **First bottleneck:** Queue processing throughput. With 8 cameras, peak ~8 events/second. Database queue is fine. If cameras increase, Redis queue + multiple workers.
2. **Second bottleneck:** Image storage I/O. At ~2MB per event, 8 events/sec = 16MB/sec sustained during activity. Unlikely to saturate local disk but monitor.
3. **Third bottleneck:** WebSocket broadcast fan-out. With a single admin user, Reverb handles trivially. Only matters if multiple operators added later.

## Anti-Patterns

### Anti-Pattern 1: Processing MQTT Messages Inline in the Listener

**What people do:** Put all recognition event processing (DB writes, image downloads, broadcast) directly in the MQTT subscribe callback.
**Why it's wrong:** The MQTT event loop is single-threaded. Heavy processing blocks the loop, causing message backlog. If processing throws an exception, the entire MQTT connection drops.
**Do this instead:** Dispatch a queued job from the callback. The listener stays thin and responsive. Jobs can fail independently and be retried.

### Anti-Pattern 2: Using a Vue Wrapper Library for Mapbox

**What people do:** Install `vue-mapbox-gl` or `v-mapbox` to get declarative Vue components for markers.
**Why it's wrong:** These wrappers lag behind Mapbox GL JS releases, limit access to advanced APIs (custom animations, programmatic camera control), and add an abstraction layer that complicates real-time marker updates from WebSocket events.
**Do this instead:** Use Mapbox GL JS directly inside a Vue composable. Wrap the map instance in `shallowRef`, manage markers imperatively, and connect Echo events to marker update functions.

### Anti-Pattern 3: Polling for Camera Status Instead of Event-Driven

**What people do:** Frontend polls an API endpoint every N seconds to check which cameras are online.
**Why it's wrong:** Wastes bandwidth, adds unnecessary load, and delivers stale status (up to N seconds behind).
**Do this instead:** Broadcast `CameraStatusChangedEvent` over Reverb when status changes. Frontend listens via Echo. Only a scheduled command checks for stale heartbeats (server-side), not the browser.

### Anti-Pattern 4: Storing Personnel Photos on Private Disk

**What people do:** Store enrollment photos on Laravel's `local` (private) disk for "security."
**Why it's wrong:** Cameras fetch photos via HTTP URL (`picURI`). They cannot authenticate with Laravel. Private disk requires a signed URL or authenticated route, which camera firmware cannot handle.
**Do this instead:** Store personnel photos on the `public` disk. Accept this trade-off for v1 (internal network only). Document as a known limitation.

### Anti-Pattern 5: Running MQTT Listener via Laravel Scheduler

**What people do:** Schedule `mqtt:listen` to run every minute via `$schedule->command()`.
**Why it's wrong:** The command never exits (infinite loop), so the scheduler spawns a new process each minute. You end up with N processes all subscribed to the same topics, processing every message N times.
**Do this instead:** Run the MQTT listener as a Supervisor-managed daemon process with `numprocs=1`. Never schedule it.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Mosquitto MQTT Broker | `php-mqtt/laravel-client` with config in `config/mqtt-client.php` | Plain MQTT (no TLS) on internal network. Connection params: host, port, client ID. |
| AI IP Cameras | MQTT pub/sub with JSON payloads. Camera fetches photos via HTTP. | Topic format: `AI/{deviceId}/{messageType}`. Handle firmware quirks (field name typos, types as strings). |
| Mapbox GL JS | npm package, direct JS API in Vue composable | Custom HelderDene dark/light styles. Access token via env variable. Free tier sufficient. |
| Laravel Reverb | First-party package, Pusher protocol, runs as separate process | `php artisan reverb:start` managed by Supervisor. Frontend connects via Laravel Echo with pusher-js. |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| MQTT Listener <-> Queue Workers | Laravel job dispatch (database queue) | Listener produces jobs; workers consume. Decoupled by queue table. |
| Queue Workers <-> WebSocket | Laravel Event dispatch (ShouldBroadcast) | Jobs fire events; Reverb serializes and pushes. |
| Controllers <-> MQTT (outbound) | Via `MqttPublisher` service (injected) | Controllers never touch MQTT directly. Service handles connection, topic formatting, publish. |
| Vue Pages <-> WebSocket | Laravel Echo channel subscription | Composables manage Echo subscriptions. Pages consume reactive state. |
| Vue Pages <-> Laravel (HTTP) | Inertia requests via Wayfinder-generated functions | Standard Inertia pattern, no separate API. |
| Camera <-> Laravel (HTTP) | Camera HTTP GET to public storage URL | For photo download during enrollment. URL must be reachable from camera subnet. |

### Process Supervision (Production)

Four long-running processes managed by Supervisor:

| Process | Command | Instances | Notes |
|---------|---------|-----------|-------|
| MQTT Listener | `php artisan mqtt:listen` | 1 | Must be exactly 1 to avoid duplicate processing |
| Queue Worker | `php artisan queue:work --queue=default` | 1-2 | Process recognition events, enrollment jobs |
| Reverb Server | `php artisan reverb:start` | 1 | WebSocket server for browser connections |
| Laravel Scheduler | `php artisan schedule:work` | 1 | Heartbeat checks, storage retention cleanup |

## Sources

- [php-mqtt/laravel-client GitHub](https://github.com/php-mqtt/laravel-client)
- [Laravel Broadcasting docs](https://laravel.com/docs/12.x/broadcasting)
- [Laravel Reverb docs](https://laravel.com/docs/12.x/reverb)
- [Laravel File Storage docs](https://laravel.com/docs/13.x/filesystem)
- [Mapbox GL JS Vue tutorial](https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/)
- [EMQ - MQTT in PHP](https://www.emqx.com/en/blog/how-to-use-mqtt-in-php)
- [Intervention Image v3 in Laravel](https://medium.com/@pplchamiduravihara/laravel-images-compress-with-intervention-v3-0-a2fec44c4da9)
- [Integrating MQTT into Laravel](https://blog.jjbofficial.com/integrating-mqtt-into-a-laravel-project)
- [Real-time Laravel with Reverb 2025](https://masteryoflaravel.medium.com/real-time-laravel-a-complete-practical-guide-to-websockets-with-laravel-reverb-2025-edition-bae825c0e9ce)

---
*Architecture research for: Face Recognition Alert System with MQTT Camera Integration*
*Researched: 2026-04-10*

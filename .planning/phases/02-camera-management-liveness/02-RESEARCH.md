# Phase 2: Camera Management & Liveness - Research

**Researched:** 2026-04-10
**Domain:** Camera CRUD, MQTT heartbeat/liveness processing, real-time broadcasting, Mapbox GL JS map integration
**Confidence:** HIGH

## Summary

Phase 2 builds the camera management module on top of the Phase 1 infrastructure. The work divides into four distinct areas: (1) a Camera Eloquent model with CRUD controller, form requests, and resource routes; (2) implementing the stub MQTT handlers for HeartBeat and Online/Offline messages to update camera state in the database; (3) a scheduled command running every 30 seconds to detect offline cameras; and (4) Vue 3 pages for camera list, create/edit forms with Mapbox GL JS map integration, and a camera detail page with real-time status updates via Laravel Echo.

The existing codebase provides strong foundations: the `cameras` migration is already in place, the MQTT listener command and topic router are functional, stub handlers exist for both HeartBeat and OnlineOffline topics, Reverb broadcasting is configured with the `fras.alerts` private channel, and `@laravel/echo-vue` provides the `useEcho` composable for real-time event listening. The primary new dependency is `mapbox-gl` (v3.21.0) for the interactive map component.

**Primary recommendation:** Follow the established controller/form-request/page conventions exactly as used by `ProfileController` and `SecurityController`. Create a reusable `MapboxMap.vue` component that serves both interactive (form) and read-only (detail) modes via props. Use `useEcho` from `@laravel/echo-vue` for real-time camera status updates on the list page.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Dedicated pages for camera create (`/cameras/create`) and edit (`/cameras/{id}/edit`) with breadcrumb navigation back to the camera list. Not modals or sheets.
- **D-02:** GPS coordinate input uses both manual lat/lng decimal fields AND an interactive Mapbox map preview. The map updates as coordinates are typed, and clicking the map sets the coordinate fields. Reusable map component for the detail page.
- **D-03:** Device ID accepts any value -- no validation against live cameras. Camera shows as "offline" until the physical device connects and sends heartbeats.
- **D-04:** No extra fields beyond the existing migration schema: device_id, name, location_label, latitude, longitude. Additional metadata (IP, firmware) may come from heartbeat data in future phases.
- **D-05:** Scheduled Laravel command runs every 30-60 seconds, queries cameras where `last_seen_at` is older than 90 seconds, and marks them `is_online = false`. Uses the Laravel scheduler.
- **D-06:** Camera status changes (online/offline) are broadcast to browsers via Reverb on the `fras.alerts` channel using a `CameraStatusChanged` event. Dashboard and camera list update instantly.
- **D-07:** OnlineOfflineHandler trusts Online messages -- immediately marks `is_online = true` and updates `last_seen_at`. Offline messages immediately mark `is_online = false`.
- **D-08:** HeartbeatHandler only updates `last_seen_at` timestamp. Heartbeat is a liveness signal, not a telemetry source. No additional payload extraction.
- **D-09:** Table layout with columns: name, device ID, location, status (online/offline badge), last seen. Standard admin data table pattern.
- **D-10:** Real-time updates via Laravel Echo listener -- listens for `CameraStatusChanged` events to update status badge and last-seen timestamp without page reload.
- **D-11:** No filtering or search -- with at most 8 cameras, filtering is unnecessary complexity.
- **D-12:** "Add camera" primary button positioned top-right in the page header area, next to the page title.
- **D-13:** Two-column layout: camera info on the left (name, device ID, location, GPS, status, last seen, edit/delete actions), enrolled personnel list on the right.
- **D-14:** Small Mapbox map preview in the info section showing the camera's GPS pin. Reuses the same Mapbox component from the camera form.
- **D-15:** Camera deletion uses a confirmation dialog (existing Dialog UI component) explaining consequences, then redirects to camera list on confirm.
- **D-16:** Enrolled personnel section shows a placeholder empty state message until Phase 4 builds enrollment: "No personnel enrolled on this camera yet."

### Claude's Discretion
- Camera model factory states and seeder data for development
- Route naming conventions for camera routes (follow existing `profile.edit`, `profile.update` patterns)
- Scheduled command frequency within the 30-60 second range
- Table component choice -- whether to use an existing UI component or build a simple table
- Mapbox component API design (props, events) for reuse across form and detail page
- Camera form validation rules (required fields, coordinate ranges, device ID format)
- Navigation integration -- where cameras appear in the sidebar

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope

</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| CAM-01 | Admin can register a camera with device ID, name, location label, and GPS coordinates | Camera model, CameraController store method, form request validation, create page with Mapbox map |
| CAM-02 | Admin can edit and delete camera records | CameraController update/destroy methods, edit page reusing create form, delete confirmation dialog |
| CAM-03 | System tracks camera online/offline state via MQTT heartbeat messages | HeartbeatHandler and OnlineOfflineHandler implementations updating Camera model |
| CAM-04 | System marks camera offline when heartbeat absent for more than 90 seconds | Scheduled command `fras:check-offline-cameras` using `everyThirtySeconds()` with 90s threshold from config |
| CAM-05 | Camera list page shows all cameras with online/offline state and last seen time | Camera index page with table, Badge for status, real-time updates via useEcho |
| CAM-06 | Camera detail page shows camera configuration and list of enrolled personnel | Camera show page with two-column layout, Mapbox map preview, placeholder for enrollment list |
| OPS-04 | MQTT listener handles Online/Offline messages to update camera is_online state | OnlineOfflineHandler parses JSON, finds camera by facesluiceId, updates is_online and broadcasts |
| OPS-05 | MQTT listener handles HeartBeat messages to update camera last_seen_at | HeartbeatHandler parses JSON, finds camera by facesluiceId, updates last_seen_at timestamp |

</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| mapbox-gl | 3.21.0 | Interactive map for GPS coordinate selection and camera location display | Industry standard for custom-styled maps; required for HelderDene's custom Mapbox Studio styles [VERIFIED: npm registry] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| @laravel/echo-vue | 2.3.4 | Real-time WebSocket event listening in Vue components | Already installed; use `useEcho` composable for CameraStatusChanged events [VERIFIED: package.json] |

### Already Installed (No New Installation)
| Library | Version | Purpose |
|---------|---------|---------|
| laravel/reverb | v1 | WebSocket broadcasting server [VERIFIED: composer.json/Phase 1] |
| php-mqtt/laravel-client | - | MQTT client for camera communication [VERIFIED: Phase 1 installed] |
| reka-ui | 2.6.1 | Headless UI primitives (Dialog for delete confirmation) [VERIFIED: package.json] |
| lucide-vue-next | 0.468.0 | Icons (Camera, MapPin, Wifi, WifiOff, etc.) [VERIFIED: package.json] |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| mapbox-gl (direct) | vue-mapbox-gl (studiometa wrapper) | Wrapper adds abstraction but is a niche library with fewer maintainers; direct mapbox-gl is better understood and documented |
| shadcn-vue Table component | Plain HTML table with Tailwind | With max 8 cameras, a full data table library is overkill; a simple HTML table styled with Tailwind classes matches the project's minimalist approach |

**Installation:**
```bash
npm install mapbox-gl
```

**Version verification:**
- `mapbox-gl`: 3.21.0 [VERIFIED: `npm view mapbox-gl version` returned 3.21.0 on 2026-04-10]

**Note:** Mapbox GL JS v3 includes built-in TypeScript declarations. The separate `@types/mapbox-gl` package (v3.5.0) exists but is community-maintained and may lag behind. Verify if the built-in types are sufficient before adding `@types/mapbox-gl` as a devDependency. [ASSUMED]

## Architecture Patterns

### Recommended Project Structure
```
app/
  Http/
    Controllers/
      CameraController.php              # Resource controller (index, create, store, show, edit, update, destroy)
    Requests/
      Camera/
        StoreCameraRequest.php           # Validation for camera creation
        UpdateCameraRequest.php          # Validation for camera updates
  Models/
    Camera.php                           # Eloquent model with factory
  Events/
    CameraStatusChanged.php              # Broadcast event for online/offline changes
  Console/
    Commands/
      CheckOfflineCamerasCommand.php     # Scheduled offline detection
  Mqtt/
    Handlers/
      HeartbeatHandler.php               # EXISTS (stub) - implement
      OnlineOfflineHandler.php           # EXISTS (stub) - implement
database/
  factories/
    CameraFactory.php                    # Factory for test data
  seeders/
    CameraSeeder.php                     # Dev seed data
resources/
  js/
    pages/
      cameras/
        Index.vue                        # Camera list page
        Create.vue                       # Camera create form
        Edit.vue                         # Camera edit form
        Show.vue                         # Camera detail page
    components/
      MapboxMap.vue                      # Reusable map component (interactive + read-only modes)
    composables/
      useCameraStatus.ts                 # Echo listener for CameraStatusChanged events
    types/
      camera.ts                          # Camera TypeScript interfaces
routes/
  web.php                                # Add camera resource routes
  console.php                            # Register scheduled offline check
tests/
  Feature/
    Camera/
      CameraCrudTest.php                 # CRUD operation tests
      CameraStatusTest.php              # Heartbeat/offline detection tests
      CameraStatusBroadcastTest.php     # Broadcasting tests
```

### Pattern 1: Camera Resource Controller
**What:** Standard Laravel resource controller following existing `ProfileController` conventions
**When to use:** All camera CRUD operations
**Example:**
```php
// Source: Existing ProfileController pattern in app/Http/Controllers/Settings/ProfileController.php
class CameraController extends Controller
{
    /** Display the camera list page. */
    public function index(): Response
    {
        return Inertia::render('cameras/Index', [
            'cameras' => Camera::orderBy('name')
                ->get(['id', 'device_id', 'name', 'location_label', 'is_online', 'last_seen_at']),
        ]);
    }

    /** Store a new camera record. */
    public function store(StoreCameraRequest $request): RedirectResponse
    {
        Camera::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camera added.')]);

        return to_route('cameras.index');
    }
}
```
[VERIFIED: follows existing codebase conventions from ProfileController.php]

### Pattern 2: MQTT Handler Implementation
**What:** Implement stub handlers to parse MQTT JSON, look up camera, and update state
**When to use:** HeartbeatHandler and OnlineOfflineHandler
**Critical note:** The `mqtt/face/basic` and `mqtt/face/heartbeat` topics do NOT contain the device ID in the topic path. The camera is identified by the `facesluiceId` field inside the JSON payload. The handler must parse the JSON and look up the camera by `facesluiceId` mapped to the `device_id` column.
**Example:**
```php
// Source: FRAS Spec v1.1, Section C.2 (verified payload format)
class HeartbeatHandler implements MqttHandler
{
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (($data['operator'] ?? '') !== 'HeartBeat') {
            Log::warning('Unexpected operator on heartbeat topic', ['topic' => $topic]);
            return;
        }

        $facesluiceId = $data['info']['facesluiceId'] ?? null;

        if (! $facesluiceId) {
            Log::warning('Heartbeat missing facesluiceId', ['topic' => $topic]);
            return;
        }

        // facesluiceId maps to device_id in observed firmware
        Camera::where('device_id', $facesluiceId)
            ->update(['last_seen_at' => now()]);
    }
}
```
[VERIFIED: payload structure from FRAS Spec v1.1 Appendix C.2]

### Pattern 3: Mapbox GL JS in Vue 3 (Avoiding Proxy Trap)
**What:** Mapbox GL map instance must NOT be stored in Vue reactive data -- Vue 3's Proxy breaks mapbox-gl internals
**When to use:** All Mapbox GL map components
**Example:**
```typescript
// Source: Mapbox docs + Vue 3 integration guide
// https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/
<script setup lang="ts">
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    latitude: number;
    longitude: number;
    interactive?: boolean;
    accessToken: string;
    styleUrl: string;
}>();

const emit = defineEmits<{
    'update:coordinates': [lat: number, lng: number];
}>();

const mapContainer = ref<HTMLElement | null>(null);
// CRITICAL: Do NOT use ref() for the map instance
let map: mapboxgl.Map | null = null;
let marker: mapboxgl.Marker | null = null;

onMounted(() => {
    if (!mapContainer.value) return;

    mapboxgl.accessToken = props.accessToken;
    map = new mapboxgl.Map({
        container: mapContainer.value,
        style: props.styleUrl,
        center: [props.longitude, props.latitude],
        zoom: 15,
        interactive: props.interactive ?? false,
    });

    marker = new mapboxgl.Marker()
        .setLngLat([props.longitude, props.latitude])
        .addTo(map);

    if (props.interactive) {
        map.on('click', (e) => {
            emit('update:coordinates', e.lngLat.lat, e.lngLat.lng);
        });
    }
});

onUnmounted(() => {
    map?.remove();
    map = null;
});
</script>
```
[CITED: https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/]

### Pattern 4: Real-time Camera Status with useEcho
**What:** Use `@laravel/echo-vue` composable to listen for `CameraStatusChanged` events on the `fras.alerts` private channel
**When to use:** Camera list page and camera detail page for live status updates
**Example:**
```typescript
// Source: @laravel/echo-vue source code in node_modules
import { useEcho } from '@laravel/echo-vue';

// Default visibility is 'private' -- matches fras.alerts private channel
useEcho('fras.alerts', '.CameraStatusChanged', (payload: CameraStatusPayload) => {
    // Update camera status in local state
    const camera = cameras.value.find(c => c.id === payload.camera_id);
    if (camera) {
        camera.is_online = payload.is_online;
        camera.last_seen_at = payload.last_seen_at;
    }
});
```
[VERIFIED: useEcho source code examined in node_modules/@laravel/echo-vue/src/composables/useEcho.ts, default visibility is 'private']

### Pattern 5: Scheduled Sub-Minute Command
**What:** Laravel 13 natively supports `everyThirtySeconds()` for sub-minute scheduling
**When to use:** Offline camera detection
**Example:**
```php
// In routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('fras:check-offline-cameras')->everyThirtySeconds();
```
[CITED: https://laravel.com/docs/13.x/scheduling]

### Anti-Patterns to Avoid
- **Storing mapbox-gl Map instance in `ref()` or `reactive()`:** Vue 3's Proxy wrapping breaks mapbox-gl's internal identity checks. Use a plain `let` variable instead. [CITED: https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/]
- **Extracting device ID from heartbeat/basic topics:** These topics (`mqtt/face/heartbeat`, `mqtt/face/basic`) do NOT contain device IDs in the topic path. The camera is identified by `facesluiceId` in the JSON payload body. [VERIFIED: FRAS Spec v1.1 Section 3.2 + Appendix C.2]
- **Broadcasting from the HeartbeatHandler on every heartbeat:** Heartbeats arrive every 30-60 seconds per camera. Broadcasting every heartbeat would flood WebSocket clients. Only broadcast when status CHANGES (online->offline or offline->online). The offline detection command and OnlineOfflineHandler should broadcast; the HeartbeatHandler should silently update `last_seen_at`. [ASSUMED]
- **Using `Camera::find()` then `->save()` in high-frequency handlers:** For heartbeats that arrive every 30-60 seconds, use `Camera::where(...)->update([...])` to avoid loading the full model. [ASSUMED]

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Interactive map with GPS pins | Custom canvas/SVG map | `mapbox-gl` v3 | Handles tile rendering, zoom, click events, marker placement, custom styles, dark/light themes |
| Real-time WebSocket subscription | Manual WebSocket client | `useEcho` from `@laravel/echo-vue` | Already configured with Reverb, handles channel auth, auto-reconnect, lifecycle cleanup |
| Sub-minute scheduling | Custom loop/sleep in artisan command | `Schedule::command()->everyThirtySeconds()` | Native Laravel 13 feature, integrates with `schedule:run`, proper signal handling |
| Delete confirmation dialog | Custom modal | `Dialog` from `@/components/ui/dialog` | Already available in shadcn-vue component library, accessible, animated |
| Status badges | Custom styled spans | `Badge` from `@/components/ui/badge` | Already available, consistent with design system |
| Form submission | Manual fetch/axios | Inertia `<Form>` with Wayfinder `.form()` | Handles CSRF, validation errors, processing state, redirects automatically |

**Key insight:** Every UI pattern needed for this phase already exists in the codebase (Dialog, Badge, Card, Button, Input, Label, Breadcrumb). The only new frontend dependency is `mapbox-gl` for the map.

## Common Pitfalls

### Pitfall 1: facesluiceId vs device_id Mapping
**What goes wrong:** The spec glossary says `facesluiceId` is "distinct from the MQTT device ID", but in observed firmware they are identical (both `1026700`). Handler code might assume they are always different or always identical.
**Why it happens:** The spec describes them as distinct conceptually, but real firmware uses the same value for both.
**How to avoid:** Look up cameras using `Camera::where('device_id', $facesluiceId)`. If this returns null, log a warning with both the topic and the `facesluiceId` value. Do NOT hard-fail -- the camera may not be registered yet. Document this mapping assumption.
**Warning signs:** Heartbeat messages arriving but no camera state updates. Log warnings about "camera not found".

### Pitfall 2: Mapbox GL CSS Import Missing
**What goes wrong:** The map container renders as a blank gray box or markers are invisible.
**Why it happens:** `mapbox-gl` requires its CSS stylesheet to be imported for proper rendering. Forgetting `import 'mapbox-gl/dist/mapbox-gl.css'` is the #1 Mapbox debugging issue.
**How to avoid:** Always import the CSS in the map component: `import 'mapbox-gl/dist/mapbox-gl.css'`.
**Warning signs:** Map container has correct dimensions but shows no tiles or controls.

### Pitfall 3: Map Container Not Yet in DOM on Mount
**What goes wrong:** `new mapboxgl.Map({ container: ... })` throws because the container element is null or not yet rendered.
**Why it happens:** In Vue 3, template refs may not be resolved by the time `onMounted` fires if the element is inside a conditional (`v-if`).
**How to avoid:** Use `nextTick()` inside `onMounted`, or use `v-show` instead of `v-if` for the map container. Alternatively, add a null check before instantiating.
**Warning signs:** Console error: "Container element not found."

### Pitfall 4: Broadcasting Storms from High-Frequency Updates
**What goes wrong:** Every heartbeat triggers a broadcast, flooding the WebSocket with events that don't represent state changes.
**Why it happens:** Naively broadcasting after every `last_seen_at` update.
**How to avoid:** Only broadcast `CameraStatusChanged` when `is_online` actually changes. HeartbeatHandler updates `last_seen_at` silently. OnlineOfflineHandler and the scheduled offline check broadcast on state transitions.
**Warning signs:** Browser console shows dozens of WebSocket messages per minute per camera.

### Pitfall 5: Scheduled Command Not Running at Sub-Minute Frequency
**What goes wrong:** The `fras:check-offline-cameras` command only runs once per minute instead of every 30 seconds.
**Why it happens:** The cron job running `schedule:run` exits immediately instead of staying alive for the full minute. Sub-minute tasks require `schedule:run` to loop within the minute.
**How to avoid:** Ensure the cron entry uses `* * * * * php artisan schedule:run >> /dev/null 2>&1`. Laravel automatically handles the sub-minute looping when it detects sub-minute tasks. In dev, the `schedule:work` command handles this.
**Warning signs:** Camera goes offline but status doesn't update for 60+ seconds.

### Pitfall 6: Decimal Precision Loss on GPS Coordinates
**What goes wrong:** Latitude/longitude values lose precision when passed through JavaScript number handling or database round-trips.
**Why it happens:** The migration uses `decimal(10,7)` which supports 7 decimal places (~1cm precision). JavaScript's floating point may introduce tiny rounding differences.
**How to avoid:** Pass coordinates as strings from frontend to backend via Inertia form. Use Laravel's `decimal` validation rule. On the frontend, display with `toFixed(7)` for consistency.
**Warning signs:** Map marker position shifts slightly after save-and-reload.

### Pitfall 7: Mapbox Access Token Exposure
**What goes wrong:** The Mapbox access token is embedded in JavaScript and visible to any user who opens browser DevTools.
**Why it happens:** Mapbox GL JS requires the token client-side for tile requests.
**How to avoid:** This is expected and acceptable. Mapbox tokens are designed to be public. Use URL restriction on the Mapbox account to limit the token to `fras.test` and the production domain. Store the token in `config/hds.php` and pass it to the frontend as an Inertia shared prop or page prop.
**Warning signs:** None -- this is by design.

## Code Examples

### Camera Model
```php
// Source: Existing User model pattern + cameras migration schema
<?php

namespace App\Models;

use Database\Factories\CameraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['device_id', 'name', 'location_label', 'latitude', 'longitude'])]
class Camera extends Model
{
    /** @use HasFactory<CameraFactory> */
    use HasFactory;

    /** Get the attributes that should be cast. */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }
}
```
[VERIFIED: follows User model pattern with PHP 8 attributes]

### CameraStatusChanged Broadcast Event
```php
// Source: Existing TestBroadcastEvent pattern
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CameraStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $camera_id,
        public string $camera_name,
        public bool $is_online,
        public ?string $last_seen_at,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('fras.alerts');
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'camera_id' => $this->camera_id,
            'camera_name' => $this->camera_name,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at,
        ];
    }
}
```
[VERIFIED: follows TestBroadcastEvent pattern exactly]

### OnlineOfflineHandler Implementation
```php
// Source: FRAS Spec v1.1 Appendix C.2 + D-07 decision
class OnlineOfflineHandler implements MqttHandler
{
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (! $data || ! isset($data['operator'])) {
            Log::warning('Invalid Online/Offline payload', ['topic' => $topic]);
            return;
        }

        $operator = $data['operator'];

        if (! in_array($operator, ['Online', 'Offline'], true)) {
            Log::warning('Unexpected operator on basic topic', [
                'topic' => $topic,
                'operator' => $operator,
            ]);
            return;
        }

        $facesluiceId = $data['info']['facesluiceId'] ?? null;

        if (! $facesluiceId) {
            Log::warning('Online/Offline missing facesluiceId', ['topic' => $topic]);
            return;
        }

        $camera = Camera::where('device_id', $facesluiceId)->first();

        if (! $camera) {
            Log::warning('Online/Offline for unknown camera', [
                'facesluiceId' => $facesluiceId,
            ]);
            return;
        }

        $isOnline = $operator === 'Online';
        $wasOnline = $camera->is_online;

        $camera->is_online = $isOnline;

        if ($isOnline) {
            $camera->last_seen_at = now();
        }

        $camera->save();

        // Only broadcast if status actually changed
        if ($wasOnline !== $isOnline) {
            CameraStatusChanged::dispatch(
                $camera->id,
                $camera->name,
                $isOnline,
                $camera->last_seen_at?->toIso8601String(),
            );
        }
    }
}
```
[VERIFIED: payload from spec; logic follows D-07 decision]

### StoreCameraRequest Validation
```php
// Source: Existing ProfileUpdateRequest pattern + cameras table schema
<?php

namespace App\Http\Requests\Camera;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreCameraRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:255', 'unique:cameras,device_id'],
            'name' => ['required', 'string', 'max:255'],
            'location_label' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
```
[VERIFIED: follows existing FormRequest convention]

### Camera TypeScript Interface
```typescript
// Source: cameras migration schema
export interface Camera {
    id: number;
    device_id: string;
    name: string;
    location_label: string;
    latitude: number;
    longitude: number;
    is_online: boolean;
    last_seen_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface CameraStatusPayload {
    camera_id: number;
    camera_name: string;
    is_online: boolean;
    last_seen_at: string | null;
}
```
[VERIFIED: derived from cameras migration]

### Sidebar Navigation Integration
```typescript
// In resources/js/components/AppSidebar.vue, add to mainNavItems:
import { Camera, LayoutGrid } from 'lucide-vue-next';
import { index } from '@/routes/cameras'; // Wayfinder generated

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Cameras',
        href: index(),  // from @/routes/cameras
        icon: Camera,
    },
];
```
[VERIFIED: follows existing AppSidebar.vue NavItem pattern]

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `spatie/laravel-short-schedule` for sub-minute tasks | Native `everyThirtySeconds()` in Laravel | Laravel 10+ | No external package needed for sub-minute scheduling |
| `window.Echo` global in Vue 2 | `useEcho` composable from `@laravel/echo-vue` | 2025 with Inertia v3 | Type-safe, auto-cleanup on unmount, private channel default |
| mapbox-gl v2 with `@types/mapbox-gl` | mapbox-gl v3 with built-in TypeScript declarations | mapbox-gl v3 release | v3 ships with its own `.d.ts` files |
| Storing map in `data()` (Vue 2) | Plain `let` variable (Vue 3 Composition API) | Vue 3 | Avoids Proxy wrapping that breaks mapbox-gl internals |

**Deprecated/outdated:**
- `@types/mapbox-gl`: Community-maintained types; may be unnecessary with mapbox-gl v3's built-in declarations [ASSUMED]
- `Inertia::lazy()` / `LazyProp`: Removed in Inertia v3; use `Inertia::optional()` instead [CITED: CLAUDE.md project instructions]
- `router.cancel()`: Replaced by `router.cancelAll()` in Inertia v3 [CITED: CLAUDE.md project instructions]

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | mapbox-gl v3 includes built-in TypeScript declarations, making `@types/mapbox-gl` unnecessary | Standard Stack | LOW -- would just need to add `@types/mapbox-gl` as devDependency |
| A2 | Broadcasting every heartbeat would flood WebSocket clients; only broadcast on state changes | Anti-Patterns | LOW -- if broadcasting every heartbeat is desired, it's a minor code change |
| A3 | Using `Camera::where()->update()` is better than `find()->save()` for high-frequency heartbeats | Anti-Patterns | LOW -- performance difference is negligible with 8 cameras |
| A4 | `facesluiceId` always maps to `device_id` in the cameras table for these cameras | Pitfall 1 | HIGH -- if they differ, heartbeat/online messages won't match any camera. Spec says "identical in observed firmware" but warns they are "conceptually distinct" |

## Open Questions

1. **Offline message payload format**
   - What we know: Online message has been captured and verified (spec Appendix C.2). The `operator` field is `"Online"`.
   - What's unclear: The Offline message format is not explicitly shown in the spec appendix. Assumed to be the same structure with `"operator": "Offline"` and `facesluiceId` in `info`.
   - Recommendation: Implement the handler to accept both `"Online"` and `"Offline"` operators. Log a warning if an unexpected operator arrives. This is safe even if the format differs slightly.

2. **facesluiceId vs device_id discrepancy**
   - What we know: Spec glossary says they are "distinct" but observed firmware shows them as "identical" (`1026700`).
   - What's unclear: Whether this holds for all camera models/firmware versions the system will encounter.
   - Recommendation: Use `facesluiceId` to look up by `device_id`. Add logging when lookup fails so the mapping can be debugged at deployment time. Document this in the camera configuration docs.

3. **Mapbox access token delivery to frontend**
   - What we know: Token is in `config/hds.php` under `mapbox.token`. Frontend components need it.
   - What's unclear: Whether to pass it as a shared Inertia prop (available on all pages) or a page-specific prop (only camera pages).
   - Recommendation: Pass as a page prop on camera pages only (create, edit, show). Avoid sharing globally since only camera pages need it. In Phase 6 (Dashboard), it can be added to the dashboard page props as well.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| mapbox-gl (npm) | Map component | Needs install | 3.21.0 (npm registry) | -- |
| Mapbox access token | Map rendering | Needs configuration | -- | Map component shows "token required" message |
| @laravel/echo-vue | Real-time updates | Available | 2.3.4 | -- |
| Laravel Reverb | WebSocket broadcasting | Available (Phase 1) | v1 | -- |
| php-mqtt/laravel-client | MQTT handlers | Available (Phase 1) | -- | -- |
| MySQL | Camera persistence | Available (Phase 1) | -- | -- |

**Missing dependencies with no fallback:**
- `mapbox-gl` npm package must be installed
- Mapbox access token must be configured in `.env` (MAPBOX_ACCESS_TOKEN)

**Missing dependencies with fallback:**
- None

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 |
| Config file | `phpunit.xml` (Pest runs on PHPUnit) |
| Quick run command | `php artisan test --compact --filter=CameraCrud` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CAM-01 | Admin can create camera with all required fields | Feature | `php artisan test --compact --filter=CameraCrudTest` | Wave 0 |
| CAM-02 | Admin can edit and delete cameras | Feature | `php artisan test --compact --filter=CameraCrudTest` | Wave 0 |
| CAM-03 | HeartBeat updates camera last_seen_at | Feature | `php artisan test --compact --filter=CameraStatusTest` | Wave 0 |
| CAM-04 | Offline detection marks cameras offline after 90s | Feature | `php artisan test --compact --filter=CameraStatusTest` | Wave 0 |
| CAM-05 | Camera list returns all cameras with status | Feature | `php artisan test --compact --filter=CameraCrudTest` | Wave 0 |
| CAM-06 | Camera detail shows config and enrollment placeholder | Feature | `php artisan test --compact --filter=CameraCrudTest` | Wave 0 |
| OPS-04 | OnlineOfflineHandler updates is_online state | Feature | `php artisan test --compact --filter=CameraStatusTest` | Wave 0 |
| OPS-05 | HeartbeatHandler updates last_seen_at | Feature | `php artisan test --compact --filter=CameraStatusTest` | Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --compact --filter=Camera`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Camera/CameraCrudTest.php` -- covers CAM-01, CAM-02, CAM-05, CAM-06
- [ ] `tests/Feature/Camera/CameraStatusTest.php` -- covers CAM-03, CAM-04, OPS-04, OPS-05
- [ ] `tests/Feature/Camera/CameraStatusBroadcastTest.php` -- covers D-06, broadcasting on state change
- [ ] `database/factories/CameraFactory.php` -- required by all camera tests
- [ ] Framework install: `npm install mapbox-gl` -- new frontend dependency

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | Yes (existing) | All camera routes behind `auth` + `verified` middleware |
| V3 Session Management | No (inherited) | Handled by Laravel/Fortify session management |
| V4 Access Control | Yes (minimal) | Single admin user for v1; all authenticated users can manage cameras |
| V5 Input Validation | Yes | Form Request classes (`StoreCameraRequest`, `UpdateCameraRequest`) validate all inputs |
| V6 Cryptography | No | No encryption operations in this phase |

### Known Threat Patterns for Camera Management

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Unauthorized camera creation/deletion | Elevation of Privilege | `auth` + `verified` middleware on all camera routes |
| GPS coordinate injection (extreme values) | Tampering | `between:-90,90` and `between:-180,180` validation rules |
| Device ID collision / spoofing | Spoofing | `unique:cameras,device_id` validation; `unique` DB constraint |
| MQTT message injection (fake heartbeat) | Spoofing | Out of scope for v1 (MQTT on trusted internal network per spec section 7.1) |
| Mapbox token abuse | Information Disclosure | URL-restrict token to known domains on Mapbox account |

## Project Constraints (from CLAUDE.md)

These directives from CLAUDE.md must be honored during planning:

1. **Test enforcement:** Every change must be programmatically tested. Write or update tests, then run them.
2. **Pint formatting:** Run `vendor/bin/pint --dirty --format agent` before finalizing PHP changes.
3. **Artisan make commands:** Use `php artisan make:` commands to create new files (models, controllers, migrations, etc.).
4. **Form validation:** Use Form Request classes for all controller method validation.
5. **Wayfinder routes:** Use Wayfinder-generated functions instead of hardcoded URLs. Run `wayfinder:generate` after adding routes.
6. **Inertia flash toasts:** Use `Inertia::flash('toast', ['type' => 'success', 'message' => __('...')])` for success feedback.
7. **Vue conventions:** Use `<script setup lang="ts">` exclusively. Define props with `defineProps<{...}>()`.
8. **shadcn-vue components:** Check existing `components/ui/` before building new ones.
9. **Route naming:** Follow kebab-or-dot notation (e.g., `cameras.index`, `cameras.store`).
10. **No new dependencies without approval:** `mapbox-gl` is an approved stack addition per PROJECT.md.
11. **Documentation files:** Only create if explicitly requested.
12. **Skill activation:** Activate `laravel-best-practices`, `pest-testing`, `wayfinder-development`, `inertia-vue-development`, and `tailwindcss-development` skills during implementation.

## Sources

### Primary (HIGH confidence)
- FRAS Spec v1.1 (`docs/HDS-FRAS-Spec-v1.1.md`) -- MQTT topic patterns, payload schemas, camera schema, heartbeat/online-offline message formats
- Existing codebase -- HeartbeatHandler stub, OnlineOfflineHandler stub, TopicRouter, FrasMqttListenCommand, TestBroadcastEvent, ProfileController, User model, AppSidebar navigation
- `@laravel/echo-vue` source code (`node_modules/@laravel/echo-vue/src/composables/useEcho.ts`) -- useEcho API, default private visibility, channel subscription pattern
- npm registry -- mapbox-gl 3.21.0 verified via `npm view`

### Secondary (MEDIUM confidence)
- [Mapbox GL JS + Vue tutorial](https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/) -- Proxy avoidance pattern, lifecycle management
- [Laravel 13 Task Scheduling docs](https://laravel.com/docs/13.x/scheduling) -- `everyThirtySeconds()` method, sub-minute scheduling behavior
- [shadcn-vue Table component](https://radix.shadcn-vue.com/docs/components/table) -- Table component available for installation

### Tertiary (LOW confidence)
- mapbox-gl v3 built-in TypeScript declarations -- not verified against actual package contents; may still need `@types/mapbox-gl`

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- mapbox-gl version verified via npm, all other dependencies already installed
- Architecture: HIGH -- follows existing codebase patterns exactly, MQTT payload formats verified from spec
- Pitfalls: HIGH -- based on verified spec details (facesluiceId mapping, topic structure) and well-known Vue 3 + Mapbox integration issues

**Research date:** 2026-04-10
**Valid until:** 2026-05-10 (stable domain, no rapidly-changing dependencies)

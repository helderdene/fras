# Face Recognition Alert System

**Technical Specification v1.1**

*HDSystem (HyperDrive System) · Butuan City, Agusan del Norte, Philippines · April 2026*

---

## 1. Overview

This document specifies version 1.1 of a web-based Face Recognition Alert System that integrates with AI Intelligent IP Cameras over MQTT. The system enables operators to monitor camera locations on a map, receive real-time alerts when authorized personnel are recognized, and manage personnel enrollment across cameras from a central admin interface.


### 1.1 Goals

- Display all installed cameras as pins on a map of the deployment site.
- Receive matched-personnel face recognition events from cameras in real time.
- Surface each recognition as a visual alert in the operator dashboard, showing the matched person, camera, and snapshot.
- Provide an admin interface to enroll, edit, and remove personnel, with automatic synchronization to all cameras.
- Track per-camera enrollment status so operators can see which cameras are up to date.


### 1.2 Non-goals (v1)

- Stranger detection alerts (events on the /Snap topic).
- Multi-site or multi-tenant deployments.
- Behavioral analytics events (tripwire, area intrusion, smoke/fire, PPE detection).
- Mobile native applications. The web app must be mobile-responsive but no native build is in scope.
- Bulk personnel import via CSV or Excel.
- Temporary visitor passes with auto-expiry (`tempValid`, `validBegin`, `validEnd`).
- Continuous transmission ACK loop. Cameras will be configured with `ResumefromBreakpoint` disabled in v1.


### 1.3 Target deployment

Single-site facility with up to 8 AI Intelligent IP Cameras and up to 200 enrolled personnel. The Laravel application, MQTT broker, MySQL database, and Reverb WebSocket server all run on a single Linux server reachable from the camera network.


## 2. Architecture


### 2.1 Component diagram

The system has six runtime components, all hosted on a single application server:

- **AI Intelligent IP Cameras** — physical devices configured to publish events to the MQTT broker and to fetch personnel photos from the application server.
- **Mosquitto MQTT broker** — receives camera events, distributes them to the Laravel listener, and accepts platform commands destined for cameras.
- **Laravel MQTT listener** — a long-running artisan command that subscribes to camera topics, parses events, persists them to MySQL, downloads snapshots, and broadcasts alerts.
- **Laravel web application** — serves the operator dashboard, the admin pages, and the API consumed by the Vue front end.
- **Laravel Reverb** — WebSocket server that pushes recognition alerts to connected browser clients.
- **MySQL database** — stores cameras, personnel, recognition events, and per-camera enrollment status.


### 2.2 Event flow

1. A person looks at a camera. The camera matches the face against its onboard personnel database.
1. The camera publishes a `RecPush` JSON message to `mqtt/face/{deviceId}/Rec` on the broker.
1. The Laravel MQTT listener receives the message, parses it, and looks up the camera by device ID.
1. The listener decodes the embedded base64 face crop and scene image, saves them to local storage, and inserts a `recognition_events` row in MySQL.
1. The listener dispatches a `RecognitionAlert` broadcast event through Reverb.
1. All connected browser clients receive the event over WebSocket. The matching camera pin pulses on the map and a toast appears in the alert feed.


### 2.3 Enrollment flow

1. An admin uploads a personnel record with a photo through the Vue admin page.
1. Laravel preprocesses the photo: resizes to maximum 1080p, compresses to under 1MB, computes an MD5 hash, and saves it to the public storage disk.
1. On save, Laravel dispatches one `EnrollPersonnelBatch` job per camera. The job is gated by the `WithoutOverlapping` middleware so that only one enrollment per camera runs at a time.
1. Each job calls `CameraEnrollmentService::upsertBatch`, which builds an `EditPersonsNew` JSON message and publishes it to `mqtt/face/{deviceId}`.
1. The camera processes the batch, fetches the personnel photo via HTTP from the Laravel public URL, and replies with `EditPersonsNew-Ack` on `mqtt/face/{deviceId}/Ack`.
1. The MQTT listener receives the ACK, correlates it to the original messageId via cache, and updates the `camera_enrollments` table with success or failure for each person.


## 3. Camera integration


### 3.1 Protocol summary

- **Protocol:** MQTT v3.1.1, QoS 0.
- **Payload format:** JSON with UTF-8 encoding.
- **Image fields:** base64-encoded inline data with a `data:` URI prefix, or HTTP URL (`picURI`) for photos sent to the camera.
- **Maximum image sizes:** face crop 1MB, scene image 2MB, enrollment photo 1MB at no more than 1080p resolution.


### 3.2 Topic structure

All topics follow the pattern `mqtt/face/{deviceId}`, where `{deviceId}` is the MQTT-registered camera ID configured during installation.

| Topic | Direction | Purpose |
|---|---|---|
| `mqtt/face/{deviceId}` | Platform → Camera | Commands: enrollment, queries, configuration |
| `mqtt/face/{deviceId}/Ack` | Camera → Platform | Command responses and ACKs |
| `mqtt/face/{deviceId}/Rec` | Camera → Platform | Matched personnel recognition events |
| `mqtt/face/basic` | Camera → Platform | Online and offline notifications |
| `mqtt/face/heartbeat` | Camera → Platform | 30-second keep-alive heartbeat |


### 3.3 Supported camera operations

V1 uses a deliberately small subset of the MQTT protocol surface. The following operations are in scope:

| Operator | Direction | v1 Use |
|---|---|---|
| `RecPush` | Inbound | Recognition event ingestion |
| `EditPersonsNew` / `-Ack` | Outbound + Ack | Personnel upsert (add or modify) |
| `DeletePersons` / `-Ack` | Outbound + Ack | Bulk personnel deletion |
| `HeartBeat` | Inbound | Camera liveness tracking |
| `Online` / `Offline` | Inbound | Camera online state tracking |


### 3.4 RecPush event payload

This is the inbound event the system listens for on `mqtt/face/{deviceId}/Rec`. The payload structure has been verified against real firmware on a device with Cloud ID 1026700. The reference example below reflects an actual captured payload, not the spec PDF, which had several discrepancies noted in the field reference.

```json
{
  "operator": "RecPush",
  "info": {
    "customId": "",
    "personId": "3",
    "RecordID": "2",
    "VerifyStatus": "1",
    "PersonType": "0",
    "similarity1": "83.000000",
    "Sendintime": 1,
    "personName": "Dene Helder Gran",
    "facesluiceId": "1026700",
    "idCard": "12345",
    "telnum": " ",
    "time": "2026-04-10 10:00:07",
    "isNoMask": "1",
    "PushType": 0,
    "targetPosInScene": [346, 0, 1572, 1080],
    "pic": "data:image/jpeg;base64,..."
  }
}
```


#### Field reference

| Field | Type | Notes |
|---|---|---|
| `customId` | string | Platform-assigned ID. EMPTY when the person was enrolled via the camera's own web UI. Handler must accept empty values and fall back to lookup by `personId`. |
| `personId` | string | Camera-internal ID, sequential per enrollment. Use as fallback identifier when `customId` is empty. |
| `RecordID` | int (string) | Camera DB record ID, increments with each recognition. Required for ACK reply if continuous transmission is enabled. |
| `VerifyStatus` | int (string) | 0 Nothing, 1 Allow, 2 Refuse, 3 Not registered. Verified against real firmware. |
| `PersonType` | int (string) | 0 Allow list (whitelist), 1 Block list (blacklist). Verified against real firmware. |
| `similarity1` | float (string) | Match confidence 0-100. Real firmware appears to round or bin to whole numbers. |
| `Sendintime` | int | Real int, not string. 1 real-time (within 10s), 0 not real-time. |
| `personName` | string | Person name. The spec PDF documents this as `persionName` (misspelled) but real firmware uses correctly spelled `personName`. Handler should read `personName` first with `persionName` as fallback for older firmware. |
| `facesluiceId` | string | Camera-internal ID, distinct from the topic device ID (though identical in observed firmware). |
| `idCard` | string | National ID. May be blank or whitespace. |
| `telnum` | string | Phone number. Observed as a single space character `" "` when not set; trim before comparing. |
| `time` | string | `YYYY-MM-DD HH:mm:ss` in camera local time. No timezone indicator. |
| `isNoMask` | int (string) | 0 masked or unchecked, 1 no mask, 2 no mask but allowed. |
| `PushType` | int | Real int, not string. 0 automatic, 2 manual replay (suppressed in v1). |
| `targetPosInScene` | int[4] | `[x1, y1, x2, y2]` face bounding box in scene image coordinates. Real ints in array. |
| `pic` | string | Base64 face crop with data URI prefix, max 1MB. Always present. |
| `scene` | string | Base64 full scene image with data URI prefix, max 2MB. NOT PRESENT in observed firmware — field may be optional or controlled by a separate camera config setting. Handler must treat as nullable. |


#### Type handling

Most numeric fields documented as int are transmitted as strings in real payloads. The handler must explicitly cast: `RecordID`, `VerifyStatus`, `PersonType`, `similarity1`, `isNoMask`. The exceptions sent as actual ints are `Sendintime` and `PushType`. Array contents in `targetPosInScene` are real ints.


#### Field name discrepancy: personName

The vendor's MQTT spec PDF (v1.25) documents the person name field as `persionName` (misspelled). Real firmware on the test device uses the correctly spelled `personName`. The handler must read `personName` first and fall back to `persionName` to support older or vendor-customized firmware that retains the typo.


#### Empty customId edge case

When personnel are enrolled directly through the camera's web UI (rather than via the platform's `EditPersonsNew` command), the `customId` field arrives as an empty string. The `personId` field still contains the camera's internal sequential ID. The handler must:

- Accept empty `customId` without error.
- Fall back to looking up the personnel record by `personId` when `customId` is empty.
- Surface UI-enrolled people in the operator dashboard with a flag indicating their origin, so admins know to re-import them through the platform if they want full management.


#### Missing scene image

The spec PDF describes a `scene` field carrying the full scene image as base64 (up to 2MB). Real firmware on the test device omits this field entirely. The handler must treat `scene` as optional and store null when absent. A camera-side configuration option may control whether scene images are uploaded; this should be investigated during deployment if scene images are required for forensic review.


#### Alert classification

V1 classifies recognition events into three severity levels based on `VerifyStatus` and `PersonType`. All three classifications have been verified against real firmware on the test device:

| Severity | Conditions | Operator action |
|---|---|---|
| **Critical** | `PersonType=1` (block list match) | Immediate visual and audible alert |
| **Warning** | `VerifyStatus=2` (recognized but refused) | Visual alert |
| **Info** | `VerifyStatus=1` + `PersonType=0` (normal allowed entry) | Logged in feed only |

Events with `PushType=2` are manual replay events from a `ManualPushSnaps` command and are stored but not surfaced as alerts in v1.


### 3.5 EditPersonsNew enrollment payload

This is the outbound message used to add or update personnel on a camera. `EditPersonsNew` is preferred over `AddPersons` because it operates as an upsert: existing customIds are modified, missing ones are added.

```json
{
  "messageId": "EditPersonsNew2026-04-10T10:00:00_001",
  "DataBegin": "BeginFlag",
  "operator": "EditPersonsNew",
  "PersonNum": 2,
  "info": [
    {
      "customId": "hds-staff-00001",
      "name": "Maria Santos",
      "personType": 0,
      "gender": 1,
      "birthday": "1990-03-15",
      "idCard": "1234-5678-9012",
      "telnum1": "09171234567",
      "address": "Butuan City",
      "isCheckSimilarity": 1,
      "picURI": "http://hds.local/storage/personnel/hds-staff-00001.jpg"
    },
    {
      "customId": "hds-staff-00002",
      "name": "Juan dela Cruz",
      "personType": 0,
      "isCheckSimilarity": 1,
      "picURI": "http://hds.local/storage/personnel/hds-staff-00002.jpg"
    }
  ],
  "DataEnd": "EndFlag"
}
```


#### Constraints

- Maximum 1000 entries per batch. Larger sets must be chunked.
- `PersonNum` must equal the length of the `info` array, otherwise the camera rejects with errcode 417.
- Both `DataBegin` (`"BeginFlag"`) and `DataEnd` (`"EndFlag"`) are required packet markers.
- Only one batch may be in flight per camera at a time. A second batch sent before the first completes will be rejected with errcode 410.
- `picURI` is required for first-time enrollment. For metadata-only modifications, omit `picURI` to leave the existing photo unchanged.
- The `picURI` must be reachable from the camera's network. The camera fetches the URL itself; the platform does not push the image.
- `isCheckSimilarity` should be set to 1 in production to prevent accidental duplicate enrollments of the same face.


#### Error codes

The following per-person errors appear in `AddErrInfo[]` of the ACK:

| Code | Meaning | Operator-facing message |
|---|---|---|
| 461 | Missing customId | Internal error: missing personnel ID |
| 463 | Missing picURI on add | Photo required for first enrollment |
| 464 | DNS resolution failed | Camera could not resolve photo host |
| 465 | Photo download failed or timed out | Camera could not download photo |
| 466 | Photo data empty or unreadable | Photo URL returned no data |
| 467 | Photo too large (>1MB or >1080p) | Photo too large; re-upload with smaller file |
| 468 | Face feature extraction failed | No usable face detected in photo |
| 474 | Camera personnel storage full | Camera storage full; remove old enrollments |
| 478 | Photo too similar to existing person | Person may already be enrolled |


## 4. Data model


### 4.1 Tables


#### cameras

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `device_id` | string unique | MQTT device ID, e.g. `005a213b000b93cc` |
| `name` | string | Operator-friendly name, e.g. "Main gate" |
| `location_label` | string | Human address or zone |
| `latitude` | decimal(10,7) | WGS84 |
| `longitude` | decimal(10,7) | WGS84 |
| `last_seen_at` | datetime nullable | Updated on heartbeat |
| `is_online` | boolean | Computed from heartbeat |
| `timestamps` | timestamps | Standard Laravel |


#### personnel

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `custom_id` | string unique | Sent to camera as `customId`, max 48 chars |
| `name` | string | Max 32 chars |
| `person_type` | tinyint | 0 allow list, 1 block list |
| `gender` | tinyint nullable | 0 male, 1 female |
| `birthday` | date nullable |  |
| `id_card` | string nullable | Max 32 chars |
| `phone` | string nullable | Max 32 chars |
| `address` | string nullable | Max 72 chars |
| `photo_path` | string nullable | Public storage path |
| `photo_hash` | string nullable | MD5 of photo file, used to detect changes |
| `timestamps` | timestamps | Standard Laravel |


#### recognition_events

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `camera_id` | bigint FK | References `cameras.id` |
| `personnel_id` | bigint FK nullable | References `personnel.id`, null if no local match |
| `custom_id` | string nullable | Indexed for fast lookup |
| `camera_person_id` | string nullable | Camera-internal `personId` |
| `record_id` | bigint | Camera `RecordID` |
| `verify_status` | tinyint | 0–3 |
| `person_type` | tinyint | 0 or 1 |
| `similarity` | float | 0–100 |
| `is_real_time` | boolean | Derived from `Sendintime` |
| `name_from_camera` | string nullable | From `personName` field |
| `facesluice_id` | string nullable | Camera-internal ID |
| `id_card` | string nullable |  |
| `phone` | string nullable |  |
| `is_no_mask` | tinyint | 0–2 |
| `target_bbox` | json nullable | `[x1,y1,x2,y2]` |
| `captured_at` | datetime | From camera `time` field |
| `face_image_path` | string nullable | Storage path of cropped face |
| `scene_image_path` | string nullable | Storage path of full scene |
| `raw_payload` | json | Full original message for forensics |
| `timestamps` | timestamps | Standard Laravel |


#### camera_enrollments

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `camera_id` | bigint FK | References `cameras.id` |
| `personnel_id` | bigint FK | References `personnel.id` |
| `enrolled_at` | datetime nullable | Last successful enrollment |
| `photo_hash` | string nullable | Hash at time of last enrollment |
| `last_error` | string nullable | Translated error message if failed |
| `timestamps` | timestamps | Standard Laravel |

Unique constraint on `(camera_id, personnel_id)`.


### 4.2 Indexes

- `recognition_events`: index on `(camera_id, captured_at)` for fast event feed queries.
- `recognition_events`: index on `(person_type, verify_status)` for alert classification queries.
- `recognition_events`: index on `custom_id` for personnel-history lookups.
- `personnel`: unique index on `custom_id`.
- `cameras`: unique index on `device_id`.


## 5. Backend implementation


### 5.1 Stack

- Laravel 11 (PHP 8.3+).
- MySQL 8.0+ for primary data store.
- Mosquitto 2.x as the MQTT broker.
- Laravel Reverb for WebSocket broadcasting.
- `php-mqtt/laravel-client` for MQTT integration.
- Intervention Image v3 for photo preprocessing.
- Supervisor for managing the long-running listener and Reverb processes.


### 5.2 Long-running processes

Two artisan commands must run continuously, managed by Supervisor:

- `php artisan mqtt:listen` — subscribes to camera topics, parses incoming events, broadcasts recognition alerts, and processes enrollment ACKs.
- `php artisan reverb:start` — runs the WebSocket server that delivers broadcasts to browser clients.

Both processes must be configured with autostart, autorestart, and centralized logging.


### 5.3 MQTT subscriptions

The listener subscribes to four topic patterns:

| Topic pattern | Handler | Action |
|---|---|---|
| `mqtt/face/+/Rec` | handleRecognition | Insert event, broadcast alert |
| `mqtt/face/+/Ack` | handleEnrollmentAck | Update camera_enrollments status |
| `mqtt/face/basic` | handleOnlineOffline | Update camera is_online state |
| `mqtt/face/heartbeat` | handleHeartbeat | Update camera last_seen_at |


### 5.4 Recognition handler logic

1. Parse the topic to extract the device ID. Look up the camera. If unknown, log a warning and return.
1. Decode JSON. Verify operator equals `RecPush`. Skip otherwise.
1. Read `PushType`. If equal to 2 (manual replay), persist but do not broadcast.
1. Look up the personnel by `customId`. The personnel may be null if the camera has stale entries.
1. Decode and save the base64 `pic` and `scene` images to public storage.
1. Cast all numeric fields explicitly. Read `personName` with `persionName` fallback.
1. Insert a `recognition_events` row with all fields and the full raw payload.
1. Classify the alert severity based on `VerifyStatus` and `PersonType`.
1. Dispatch a `RecognitionAlert` broadcast on the `recognition` channel.


### 5.5 Enrollment job

Personnel changes trigger queued jobs that publish enrollment messages to cameras. The key constraint is that only one enrollment job per camera may run at a time.

The `EnrollPersonnelBatch` job uses the `WithoutOverlapping` middleware with a per-camera lock key. Jobs queue safely behind in-flight enrollments and are retried with exponential backoff on failure.

The job calls `CameraEnrollmentService::upsertBatch` which:

1. Chunks the input list to 1000 entries if larger.
1. Builds the `EditPersonsNew` JSON, including only changed fields per person.
1. Stores the pending message ID in cache for 5 minutes, mapping it to the camera and person IDs in this batch.
1. Publishes to `mqtt/face/{deviceId}`.
1. Returns immediately. The ACK arrives asynchronously and is processed by `handleEnrollmentAck`.


### 5.6 ACK correlation

`EditPersonsNew-Ack` messages do not arrive on a per-job basis. The handler:

1. Reads the messageId from the ACK payload.
1. Pulls the matching pending entry from cache.
1. If no entry exists (expired or unknown), logs a warning and returns.
1. For each customId in `AddSucInfo`, marks the matching `camera_enrollments` row as enrolled with the current photo hash.
1. For each customId in `AddErrInfo`, translates the errcode to an operator-friendly message and stores it in `last_error`.


### 5.7 Photo preprocessing

Every uploaded personnel photo passes through Intervention Image before storage:

- Resize so the longest edge is at most 1080 pixels.
- Re-encode as JPEG with quality 85.
- Verify the resulting file is under 1MB. If not, reduce quality in 5-point steps until it fits.
- Compute the MD5 hash of the final file and store it on the personnel row.
- Save to `storage/app/public/personnel/{custom_id}.jpg`.

This preprocessing prevents the most common camera-side errors (467 photo too large, 468 face extraction failed) before they occur.


## 6. Frontend implementation


### 6.1 Stack

- Vue 3 with Composition API.
- Inertia.js for SPA-style routing without a separate API layer.
- Tailwind CSS for styling.
- Mapbox GL JS v3 for the map view, with custom Mapbox Studio styles for both dark and light modes.
- Laravel Echo with Pusher protocol adapter for Reverb WebSocket subscriptions.


### 6.2 Pages

| Route | Page | Purpose |
|---|---|---|
| `/` | Dashboard | Map of cameras with live alert feed sidebar |
| `/personnel` | Personnel list | Searchable list with sync status indicator |
| `/personnel/{id}` | Personnel detail | Edit form with per-camera enrollment status |
| `/personnel/create` | Personnel create | New person form with photo upload |
| `/cameras` | Camera list | Configured cameras with online state |
| `/cameras/{id}` | Camera detail | Camera config and enrolled personnel list |
| `/events` | Event history | Searchable, filterable recognition log |
| `/login` | Login | Single admin user for v1 |


### 6.3 Dashboard layout

The dashboard occupies the full viewport with a three-column layout below a top bar and above a status bar:

- **Top bar:** brand mark, primary navigation (Dashboard, Personnel, Cameras, Events, Settings), live clock, and a system status indicator.
- **Left rail (~280px):** camera list with online/offline indicators and per-camera recognition counts, plus a "Today" statistics panel showing total recognitions, critical events, warnings, and enrolled personnel count.
- **Center panel:** Mapbox GL JS map showing all cameras as CCTV-icon markers within circular badges. Markers are colored by status: green border and icon for online, muted gray for offline. When a recognition event arrives for a camera, that marker fills with red and emits an expanding ring animation for approximately 3 seconds. A toggle button in the map controls switches between the dark and light Mapbox Studio styles for shift-handover or daytime use.
- **Right rail (~380px):** reverse-chronological live alert feed. Each entry shows an avatar, the matched person's name, the camera name, severity tag, similarity percentage, and timestamp. Critical alerts (block list) are highlighted with a red left border and a subtle red background gradient. Warnings use amber. Info alerts use green. Clicking an entry opens a modal with the face crop, scene image with bounding box overlay, and full event metadata.
- **Status bar:** MQTT connection status, broker address, Reverb WebSocket status, and queue depth indicator. Acts as both a runtime health check and a quick visual confirmation that the live data path is healthy.


#### Map style and Mapbox account

The dashboard uses two custom Mapbox Studio styles authored on the HelderDene Mapbox account: one dark and one light. The dark style is the default and is used for normal command-center operation. The light style is available via a toggle button for daytime use or operator preference. Both styles are referenced by their `mapbox://` style URL and loaded with a public Mapbox access token.

Mapbox's free tier provides 50,000 map loads per month, which is more than sufficient for a single command center running the dashboard continuously on one or two screens. A "map load" is one page initialization, not one tile fetch, so a kiosk-mode dashboard left running consumes very few loads. If the dashboard is later deployed across multiple sites or as part of a public-facing application, the Mapbox load count should be monitored against the plan's limit.

The custom style URLs are stored in a Vue composable so they can be swapped without recompilation. If Mapbox pricing changes or the deployment requires a fully open-source alternative, both styles can be exported from Mapbox Studio and migrated to MapLibre GL JS with self-hosted tiles. The library APIs are nearly identical (MapLibre forked from Mapbox GL JS), so the migration is mostly a one-line library swap.


### 6.4 Personnel admin layout

The personnel pages match the mockup produced in the design phase:

- List page shows all personnel with avatar, name, custom ID, list type, and a single status dot summarizing camera sync state (green all enrolled, amber some pending, red some failed).
- Detail page is split into a left form (name, custom ID, list type, ID card, phone, photo) and a right sidebar showing per-camera enrollment status.
- Photo upload uses a dropzone with client-side preview. The 1MB and 1080p constraints are shown as static help text.
- Each per-camera status row shows the camera name, current state (enrolled, pending, failed), the last sync time or error message, and a contextual action button (Retry only on failed rows).
- A "Save and sync" primary button persists the personnel record and dispatches enrollment jobs to all cameras. A "Re-sync all" button on the detail page allows operators to force a re-push without editing fields.


### 6.5 Real-time event subscription

```javascript
// resources/js/composables/useRecognitionAlerts.js
import { ref, onMounted, onUnmounted } from 'vue'

export function useRecognitionAlerts() {
  const alerts = ref([])
  const flashCameraId = ref(null)

  onMounted(() => {
    window.Echo.channel('recognition')
      .listen('RecognitionAlert', (event) => {
        alerts.value.unshift(event)
        if (alerts.value.length > 50) alerts.value.pop()

        flashCameraId.value = event.camera.id
        setTimeout(() => { flashCameraId.value = null }, 3000)

        if (event.severity === 'critical') {
          new Audio('/sounds/critical.mp3').play().catch(() => {})
        }
      })
  })

  onUnmounted(() => {
    window.Echo.leaveChannel('recognition')
  })

  return { alerts, flashCameraId }
}
```


## 7. Security and access control


### 7.1 v1 scope

V1 ships with a deliberately minimal security model appropriate for a single-site deployment on a trusted internal network:

- Single admin user account, created by seed during installation.
- Standard Laravel session-based authentication with bcrypt password hashing.
- All web routes (except `/login`) require authentication.
- MQTT broker accepts connections only from localhost and the camera subnet.
- Camera authentication on the MQTT broker uses username and password configured per camera.
- Personnel photos are served from `/storage/personnel/` which is publicly accessible without auth — required because cameras must fetch them. This is an accepted trade-off for v1; if photo URLs leak they expose enrollment photos but no other data.


### 7.2 Out of scope for v1

- Multiple user roles or permissions.
- Audit logs of admin actions.
- MQTT TLS (`mqtts://`). Plain MQTT only on the internal network.
- Encrypted at-rest storage of personnel data.
- Two-factor authentication.


## 8. Operations


### 8.1 Camera configuration

Each camera must be configured before deployment with:

- MQTT broker host, port, username, and password.
- Cloud topic set to `mqtt/face/{deviceId}` where `{deviceId}` matches the camera's unique identifier.
- `StrangerUploadType` set to 1 (no upload) since stranger events are out of scope for v1.
- `RecordUploadType` set to 1 (identification record upload with captured image).
- `ResumefromBreakpoint` set to 0 (continuous transmission disabled). This avoids the need for a `PushAck` loop in v1.
- `KeepAliveInterval` set to 30 seconds.


### 8.2 Supervisor configuration

```ini
[program:hds-mqtt]
command=php /var/www/hds/artisan mqtt:listen
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/hds-mqtt.log
stopwaitsecs=10

[program:hds-reverb]
command=php /var/www/hds/artisan reverb:start
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/hds-reverb.log
stopwaitsecs=10

[program:hds-queue]
command=php /var/www/hds/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
stdout_logfile=/var/log/hds-queue.log
stopwaitsecs=60
```


### 8.3 Storage and retention

Recognition events store both a face crop and a scene image, which can grow rapidly. The system runs a scheduled cleanup job nightly that:

- Deletes scene images older than 30 days while keeping the `recognition_events` row and face crop.
- Deletes face crops older than 90 days while keeping the `recognition_events` row.
- Optionally archives `recognition_events` older than 1 year to a cold storage table.

Retention windows are configurable in `config/hds.php`.


### 8.4 Monitoring

- The MQTT listener writes a structured log entry for every received message at debug level.
- Camera offline detection runs every 60 seconds: any camera whose `last_seen_at` is more than 90 seconds old is marked offline.
- A simple status page at `/status` reports the count of online cameras, the last received event timestamp, the queue depth, and the disk usage of the storage directory.


## 9. Build plan and effort estimate

V1 is estimated at 8–10 development days, broken down as follows:

| Phase | Effort | Deliverable |
|---|---|---|
| Database migrations and Eloquent models | 0.5 day | Cameras, personnel, recognition_events, camera_enrollments tables and models |
| MQTT listener artisan command + Supervisor config | 0.5 day | Daemon connecting to broker and logging all received messages |
| Reverb + Echo wiring with stub event | 0.5 day | Proven end-to-end broadcast pipeline |
| RecognitionEventHandler for RecPush | 1 day | Real events parsed, persisted, and broadcast |
| Dashboard map and alert feed (Vue + Mapbox GL JS) | 1 day | Live operator dashboard |
| Personnel CRUD pages | 1 day | Admin can manage personnel records |
| CameraEnrollmentService and EnrollPersonnelBatch job | 1 day | Personnel changes pushed to cameras |
| ACK handler and per-camera status display | 1 day | Operators see per-camera sync state |
| Photo preprocessing and error code translation | 0.5 day | Robust photo handling, friendly error messages |
| Supervisor configs, smoke tests, hardware integration | 1.5 days | Production-ready deployment |
| **TOTAL** | **8.5 days** | **Working v1** |

Risks that could extend the timeline:

- Camera firmware quirks. ODM cameras frequently have undocumented behavior in their MQTT implementation. Plan for one extra day of debugging on first hardware integration.
- Photo URL reachability. If the camera and Laravel server are on different network segments, additional firewall rules or routing may be required.
- MQTT broker tuning. The default Mosquitto configuration may need adjustment for message size limits and persistence settings.


## 10. Future work (post-v1)


### 10.1 v1.1 — Operational hardening

- Continuous transmission ACK loop (`PushAck`) so events are not lost during Laravel downtime.
- Stranger detection alerts on the `/Snap` topic with admin-approved enrollment workflow.
- CSV bulk import for personnel.
- Audit log of admin actions.


### 10.2 v2 — Multi-site

- Multi-site support with site-scoped cameras and personnel.
- Per-site operator accounts and role-based access control.
- Camera grouping so personnel can be enrolled to a subset of cameras instead of all of them.
- Temporary visitor passes with auto-expiry.


### 10.3 v2+ — Expanded AI events

The cameras support a rich set of additional AI events that map well onto HDS-IRMS incident categories. These can be incorporated incrementally:

- `FireSmokeSnapPush` — smoke and fire detection for CDRRMO incident auto-creation.
- `ClothHelmetSnapPush` — PPE compliance monitoring for construction or industrial sites.
- `ParabolicSnapPush` — object thrown from height, useful for high-rise safety.
- `BehaviorSnapPush` — tripwire and area intrusion for perimeter security.
- `CatAttrSnapPush` — vehicle attribute capture for parking and access control.


## Appendix A: Glossary

| Term | Definition |
|---|---|
| AI Intelligent IP Camera | Network camera with onboard face recognition, supporting MQTT command and event protocol |
| `customId` | Platform-assigned unique ID for a person, sent to and stored on cameras |
| device ID | MQTT-registered camera identifier used in topic paths |
| `facesluiceId` | Camera-internal numeric ID, distinct from the MQTT device ID |
| `RecPush` | MQTT operator name for matched personnel recognition events |
| `EditPersonsNew` | MQTT operator name for batch personnel upsert (preferred over `AddPersons`) |
| `picURI` | HTTP URL pointing to a personnel photo, fetched by the camera during enrollment |
| LGU | Local Government Unit, the typical Philippine deployment context for HDSystem projects |
| CDRRMO | City Disaster Risk Reduction and Management Office, target user organization for incident-response variants |
| Reverb | Laravel's first-party WebSocket server for broadcasting real-time events to browsers |
| `WithoutOverlapping` | Laravel queue middleware that prevents two jobs sharing a key from running concurrently |


## Appendix B: Reference documents

- AI Intelligent IP Camera User Manual (vendor PDF, October 2024).
- MQTT Protocol of Intelligent Network Camera v1.25 (vendor PDF, December 2024).
- Laravel 11 documentation: https://laravel.com/docs/11.x
- php-mqtt/laravel-client: https://github.com/php-mqtt/laravel-client
- Mapbox GL JS: https://docs.mapbox.com/mapbox-gl-js/


## Appendix C: Firmware verification log

The MQTT message formats and field semantics in this specification have been verified against real firmware on a test device, not just against the vendor PDF. This appendix records the test environment, the verification results, and any observed deviations from the spec PDF that the implementation must accommodate.


### C.1 Test environment

| Item | Value |
|---|---|
| Camera Cloud ID (device ID) | `1026700` |
| Camera default IP | `192.168.254.100/24` |
| Camera reported facesname | `IPC1026700` |
| MQTT broker | Mosquitto on public VPS, `148.230.99.73:1883` |
| MQTT credentials | Username `admin` with password authentication |
| Test client | MQTTX desktop application on macOS |
| Test date | April 10, 2026 |
| Heartbeat interval observed | 60 seconds (camera default) |


### C.2 Verified message formats

The following MQTT messages were captured and verified against the spec PDF during the verification session.


#### Online notification

Topic: `mqtt/face/basic`. Sent by the camera on initial connection and re-sent at heartbeat intervals until the platform replies with `Online-Ack`.

```json
{
  "operator": "Online",
  "info": {
    "facesluiceId": "1026700",
    "username": "admin",
    "time": "2026-04-10 09:43:25",
    "ip": "192.168.254.100",
    "facesname": "IPC1026700"
  }
}
```

Note that the `ip` field reports the camera's local LAN address, not its public address. This is expected behavior since the camera has no awareness of upstream NAT translation.


#### Heartbeat

Topic: `mqtt/face/heartbeat`. Sent every `KeepAliveInterval` seconds (default 60).

```json
{
  "operator": "HeartBeat",
  "info": {
    "facesluiceId": "1026700",
    "time": "2026-04-10 09:42:25"
  }
}
```


#### Recognition event - allowed

Topic: `mqtt/face/1026700/Rec`. Captured when an enrolled allow-list person was recognized.

```json
{
  "operator": "RecPush",
  "info": {
    "customId": "",
    "personId": "3",
    "RecordID": "2",
    "VerifyStatus": "1",
    "PersonType": "0",
    "similarity1": "83.000000",
    "Sendintime": 1,
    "personName": "Dene Helder Gran",
    "facesluiceId": "1026700",
    "idCard": "12345",
    "telnum": " ",
    "time": "2026-04-10 10:00:07",
    "isNoMask": "1",
    "PushType": 0,
    "targetPosInScene": [346, 0, 1572, 1080],
    "pic": "data:image/jpeg;base64,..."
  }
}
```


#### Recognition event - block list refused

Topic: `mqtt/face/1026700/Rec`. Captured when the same person was temporarily reassigned to the block list (Wanted group) for verification testing.

```json
{
  "operator": "RecPush",
  "info": {
    "customId": "",
    "personId": "3",
    "RecordID": "1",
    "VerifyStatus": "2",
    "PersonType": "1",
    "similarity1": "83.000000",
    "Sendintime": 1,
    "personName": "Dene Helder Gran",
    "facesluiceId": "1026700",
    "idCard": "12345",
    "telnum": " ",
    "time": "2026-04-10 09:55:46",
    "isNoMask": "1",
    "PushType": 0,
    "targetPosInScene": [488, 0, 1525, 1080],
    "pic": "data:image/jpeg;base64,..."
  }
}
```


### C.3 Deviations from spec PDF

The following discrepancies between the vendor PDF and observed real-firmware behavior were identified during verification. The implementation must accommodate all of these.

| Item | Spec PDF | Real firmware |
|---|---|---|
| Person name field | `persionName` (misspelled) | `personName` (corrected) |
| `customId` for UI-enrolled people | Always populated | Empty string |
| `scene` field | Present, up to 2MB | Not present in payload |
| `VerifyStatus` values | 0 to 3 or 22, 24 | Observed only 1 and 2 |
| `similarity1` precision | Float 0-100 | Appears rounded or binned to whole numbers |
| `telnum` empty value | Optional | Single space character `" "` when not set |
| `Sendintime` type | int | Real int (not string) |
| `PushType` type | int | Real int (not string) |


### C.4 Confirmed-as-documented behaviors

The following spec PDF behaviors were verified and matched real firmware output exactly:

- Topic structure: `mqtt/face/{deviceId}`, `mqtt/face/{deviceId}/Ack`, `mqtt/face/{deviceId}/Rec`, `mqtt/face/basic`, `mqtt/face/heartbeat`.
- `PersonType` 0 = allow list, 1 = block list. Confirmed by enrolling the same person under both groups and observing matching values in `RecPush` events.
- `VerifyStatus` 1 = allow, 2 = refuse. Confirmed by the same enrollment toggle test.
- Numeric fields `RecordID`, `VerifyStatus`, `PersonType`, `similarity1`, `isNoMask` transmitted as strings.
- `RecordID` is sequential per camera, increments with each recognition.
- `targetPosInScene` is a four-element integer array of bounding box coordinates.
- Camera re-sends `Online` notification at heartbeat intervals until platform acknowledges with `Online-Ack`.
- MQTT spec section 10.1 `GetMQTTconfig` and `UpMQTTconfig` field structure (verified by reading current config from camera).


### C.5 Outstanding behaviors not yet verified

The following spec-documented behaviors should be verified during integration testing before relying on them in production:

- `EditPersonsNew` batch enrollment from platform to camera, including `picURI` photo fetch from a Laravel-hosted URL.
- `EditPersonsNew-Ack` response format and per-person error code reporting.
- `DeletePersons` and `DeleteAllPerson` behavior.
- Whether the `scene` image field can be re-enabled via a camera config setting, and which setting controls it.
- Whether `similarity1` precision is genuinely binned by the firmware or only appears so for high-confidence matches near the threshold.
- Behavior of `VerifyStatus` values 3, 22, and 24 documented in the spec but not observed during initial testing.


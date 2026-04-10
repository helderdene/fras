# Domain Pitfalls

**Domain:** Face Recognition Alert System with MQTT Camera Integration
**Researched:** 2026-04-10

## Critical Pitfalls

Mistakes that cause rewrites, data loss, or system-level failures.

### Pitfall 1: QoS 0 Message Loss Treated as Acceptable

**What goes wrong:** The camera firmware uses MQTT QoS 0 ("fire and forget") for all messages including recognition events (RecPush) and enrollment ACKs. Messages are silently dropped during brief network blips, broker restarts, or subscriber reconnections. The MQTT listener misses critical recognition events or ACK responses, leaving the system with stale enrollment states and missing security alerts.

**Why it happens:** QoS 0 provides no delivery guarantee and no retry. If the php-mqtt subscriber is disconnected for even a few seconds during a restart or crash, every message published in that window is permanently lost. The Mosquitto broker does not queue QoS 0 messages for offline subscribers regardless of clean session settings.

**Consequences:**
- Block-list recognition events silently vanish -- the core value proposition fails.
- Enrollment ACKs are lost, leaving `camera_enrollments` stuck in "pending" indefinitely.
- Operators lose trust in the system because alerts appear intermittently unreliable.

**Prevention:**
- Accept QoS 0 as a camera firmware constraint but build compensating mechanisms.
- Implement a camera-side "last event" polling or heartbeat reconciliation check so the listener can detect gaps.
- Use Mosquitto retained messages on heartbeat topics so the listener always gets the latest state on reconnect.
- Track a `last_event_at` timestamp per camera; if recognition-capable cameras go silent for an unexpected period (not offline), flag a "possible missed events" warning.
- Design the enrollment flow with explicit timeout-and-retry: if no ACK arrives within N seconds, re-publish the enrollment command rather than waiting forever.

**Detection:** Monitor for cameras that are "online" (heartbeat active) but have zero recognition events over an unusually long period. Alert when enrollment ACK wait times exceed the expected threshold.

**Phase relevance:** Phase 1 (MQTT Listener) and Phase 2 (Enrollment Sync). Must be addressed from the very first MQTT integration.

---

### Pitfall 2: Long-Running MQTT Artisan Command Memory Leaks and Silent Death

**What goes wrong:** The `artisan mqtt:listen` command runs indefinitely as a long-running PHP process. Over hours or days, it accumulates memory from Eloquent model hydration, event dispatching, logging buffers, and the php-mqtt client's internal state. Eventually it exceeds the memory limit and dies, or the MQTT connection silently drops and the client does not reconnect. No one notices because there is no health monitoring.

**Why it happens:** PHP was not designed for long-running daemons. Laravel's service container, query log, event listeners, and Eloquent all accumulate references over time. The php-mqtt/client library has documented issues with message skipping (GitHub Issue #73) and connection drops (Issue #65). Without explicit reconnection logic and memory management, the process degrades.

**Consequences:**
- The entire alert pipeline goes dark -- no MQTT messages are processed, no recognition events are saved, no alerts are broadcast.
- Enrollment ACKs are never processed, blocking all enrollment operations.
- If undetected, the system appears functional (web UI loads, map shows cameras) but is actually deaf.

**Prevention:**
- Use Supervisor (or systemd) with `autorestart=true` and `startsecs=5` to ensure the process is always running.
- Implement a `--max-memory=128` flag (similar to queue workers) that gracefully restarts the process when memory exceeds the threshold.
- Add a `--max-time=3600` flag to force periodic restarts (hourly) as a memory leak safety net.
- Inside the loop, call `gc_collect_cycles()` periodically and avoid holding Eloquent model references across iterations.
- Implement SIGTERM handling via `pcntl_signal()` to finish processing the current message before exiting, so Supervisor can restart cleanly.
- Write a health-check heartbeat: the listener updates a `cache()->put('mqtt:listener:alive', now())` on every loop iteration, and a scheduled command checks it every minute, alerting if stale.

**Detection:** The health-check heartbeat goes stale. Memory usage trends upward in monitoring. Camera heartbeat messages stop being recorded despite cameras being online.

**Phase relevance:** Phase 1 (MQTT Listener). This is the very first thing to build and must include Supervisor config and health monitoring from day one.

---

### Pitfall 3: Enrollment ACK Correlation Without Request-Response Tracking

**What goes wrong:** The system publishes an `EditPersonsNew` command to a camera and expects an `EditPersonsNew-Ack` response on a different topic. Without a correlation mechanism, the system cannot match which ACK belongs to which enrollment request, especially when multiple enrollment batches are sent to different cameras concurrently.

**Why it happens:** MQTT is pub/sub, not request-response. There is no built-in correlation ID. The camera firmware returns an ACK on a per-camera topic but does not echo back a request ID. If two enrollment requests are in flight to different cameras, ACKs arrive asynchronously and must be matched by camera device ID and timing.

**Consequences:**
- ACKs are attributed to the wrong enrollment batch, corrupting the `camera_enrollments` state.
- The system marks an enrollment as successful when it actually failed (or vice versa).
- "One batch in-flight per camera" constraint is violated, causing the camera firmware to drop or overwrite the first batch.

**Prevention:**
- Enforce a strict state machine: each camera can have at most one pending enrollment batch. Use `WithoutOverlapping` middleware keyed by camera ID.
- Store the pending batch metadata (camera_id, person_ids, sent_at) before publishing. When an ACK arrives on that camera's topic, correlate by camera_id (only one batch can be pending).
- Implement a timeout: if no ACK arrives within 30-60 seconds, mark the batch as "timed_out" and allow retry.
- Never process an ACK if no batch is pending for that camera -- log and discard as spurious.
- Record the full ACK payload including per-person success/failure status, as the camera may partially succeed.

**Detection:** Enrollment batches stuck in "pending" state beyond the timeout. Multiple ACKs arriving for a camera that has no pending batch. Mismatched person counts between request and ACK.

**Phase relevance:** Phase 2 (Enrollment Sync). This is the hardest protocol interaction to get right and needs careful state machine design.

---

### Pitfall 4: Firmware Payload Inconsistencies Causing Silent Data Corruption

**What goes wrong:** The camera firmware has documented quirks: `personName` vs `persionName` (typo in field name), empty `customId` for camera-UI-enrolled people, missing `scene` field in some events, and numeric fields encoded as strings. Code that does not account for every variant silently drops data, throws type errors, or stores corrupted records.

**Why it happens:** Camera firmware is a black box with inconsistent JSON serialization. Different firmware versions or camera models may produce subtly different payloads. Developers write parsing code against the spec but the spec does not match reality.

**Consequences:**
- Recognition events are silently dropped because a required field is missing or has an unexpected type.
- Personnel names are not matched because the code checks `personName` but the payload uses `persionName`.
- Null `customId` causes unique constraint violations or lookup failures.
- Numeric comparisons fail because confidence scores arrive as strings ("0.85" instead of 0.85).

**Prevention:**
- Build a defensive parser with explicit fallback handling: check both `personName` and `persionName`, coerce string numerics to floats/ints, treat empty strings as null.
- Create a `CameraPayloadNormalizer` class that transforms raw firmware JSON into a canonical internal format before any business logic touches it.
- Log the raw payload JSON alongside the parsed result for every message type, at least during development and early production, so discrepancies can be diagnosed.
- Write comprehensive Pest tests with real firmware payload samples (capture from test device Cloud ID 1026700) covering every known quirk.
- Never validate with `required` on fields that are known to be intermittently absent (like `scene`). Use `nullable` and handle gracefully.

**Detection:** Pest tests with real payload samples fail. Recognition events appear in the database with null person names or zero confidence. Enrollment ACKs are not parsed correctly.

**Phase relevance:** Phase 1 (MQTT Listener / RecPush processing). Must be built into the parsing layer from the start. Add firmware payload samples as test fixtures.

---

### Pitfall 5: Browser Audio Autoplay Policy Blocks Critical Alert Sounds

**What goes wrong:** The system is designed to play an audio alert on block-list (critical) recognition events. Modern browsers (Chrome 66+, Firefox, Safari) block audio playback until the user has interacted with the page via a click or tap. An operator opens the dashboard, walks away, and when a critical alert arrives, no sound plays because the AudioContext is in "suspended" state.

**Why it happens:** Browser autoplay policies require a user gesture to unlock audio. If the AudioContext is created before any user interaction, it starts suspended. A WebSocket-driven event that tries to play audio without prior user gesture is silently blocked.

**Consequences:**
- The most critical feature (audible block-list alerts) fails silently in the exact scenario it is most needed: when the operator is not actively looking at the screen.
- Operators lose trust in the alert system and stop relying on it.

**Prevention:**
- On dashboard load, display a prominent "Enable Audio Alerts" button or modal that the operator must click. This user gesture unlocks the AudioContext.
- Use the Web Audio API: create an `AudioContext`, check its `.state` property, and call `.resume()` on user interaction. Pre-load the alert sound buffer.
- Show a persistent visual indicator of audio alert status (enabled/disabled) in the status bar.
- If the AudioContext is suspended, show a non-dismissable banner: "Audio alerts are disabled. Click to enable."
- As a fallback, use the Notification API (`Notification.requestPermission()`) for OS-level notifications that work even when the browser tab is not focused.

**Detection:** `AudioContext.state === 'suspended'` after dashboard load. Test by opening the dashboard fresh and waiting for a critical event without clicking anything.

**Phase relevance:** Phase 4 (Dashboard / Alert Feed). Must be designed into the dashboard UX from the start, not retrofitted.

## Moderate Pitfalls

### Pitfall 6: Race Condition Between MQTT Events and WebSocket Broadcasts

**What goes wrong:** A recognition event arrives via MQTT, is saved to the database, and broadcast via Reverb to the browser. But the broadcast arrives at the browser before the Inertia page props have refreshed, or a second event arrives while the first is still being processed, causing duplicate alerts or out-of-order rendering in the alert feed.

**Prevention:**
- Assign each recognition event a monotonically increasing ID or timestamp. The Vue alert feed component inserts events by ID, ignoring duplicates.
- Use Laravel's `ShouldBroadcastNow` (not queued broadcast) for critical alerts to minimize latency, but use queued broadcasts for info-level events to avoid overwhelming the WebSocket.
- On the frontend, maintain a Set of seen event IDs. Deduplicate before inserting into the reactive alert list.
- Include the full event payload in the broadcast (not just an ID that requires a fetch), so the browser does not need a round-trip to display the alert.

**Detection:** Duplicate alerts appearing in the feed. Alert feed showing events out of chronological order. Console errors from rapid state updates.

**Phase relevance:** Phase 3 (Real-time Broadcast) and Phase 4 (Dashboard).

---

### Pitfall 7: Mapbox Marker DOM Thrashing on Frequent Updates

**What goes wrong:** Using HTML Markers (via `mapboxgl.Marker`) for camera pins and then updating their state (pulsing, flashing, color changes) on every recognition event causes DOM thrashing. With 8 cameras and frequent events, the map becomes sluggish because each Marker update triggers a browser reflow.

**Prevention:**
- Use Mapbox GL JS symbol/circle layers with GeoJSON sources instead of HTML Markers for camera pins. Layer-based rendering uses WebGL and is vastly more performant.
- For pulse/flash animations on recognition events, use `map.setFeatureState()` to toggle a feature state property, and define the animation in the layer's paint expression using `feature-state`. This avoids DOM manipulation entirely.
- Keep a separate small GeoJSON source for "active alert" indicators, updated only when events occur, so the full camera source is not reprocessed.
- Throttle visual updates to at most one animation cycle per camera per second to avoid visual noise.

**Detection:** Map frame rate drops below 30fps during recognition event bursts. Browser DevTools Performance tab shows excessive layout/reflow from Marker DOM updates.

**Phase relevance:** Phase 4 (Map View). Choose the rendering strategy (layers vs markers) in initial map implementation.

---

### Pitfall 8: Disk Space Exhaustion from Unmanaged Image Storage

**What goes wrong:** Every recognition event stores a face crop (up to 1MB) and a scene image (up to 2MB). With 8 cameras producing events, storage grows by gigabytes per week. Without automated cleanup, the server disk fills, and MySQL, Mosquitto, and Laravel all fail unpredictably.

**Prevention:**
- Implement the retention policy (30-day scene, 90-day face crops) as a scheduled Laravel command from Phase 1, not as a "we'll add it later" task.
- Store images in a structured directory: `storage/app/recognition/{YYYY}/{MM}/{DD}/{event_id}/` so cleanup can delete entire date directories efficiently.
- Monitor disk usage in the status bar of the dashboard (or via a scheduled health check).
- Set MySQL `innodb_file_per_table` and monitor table sizes to prevent recognition_events from becoming unwieldy.
- Consider storing only the face crop permanently and the scene image as a shorter-retention asset, since scene images are 2x larger but less critical for audit.

**Detection:** Disk usage exceeding 70% capacity. Scheduled cleanup command not running (check `schedule:list`). Storage directory size growing faster than expected.

**Phase relevance:** Phase 1 (RecPush processing -- storage structure) and Phase 5 (Retention policy command).

---

### Pitfall 9: Camera Photo Download Failure During Enrollment

**What goes wrong:** Enrollment via `EditPersonsNew` includes a `picURI` that the camera must HTTP-fetch to get the personnel photo. If the camera cannot reach the Laravel server (network segmentation, wrong IP, firewall), the enrollment silently fails or the ACK reports a per-person error that is not handled.

**Prevention:**
- During camera registration, verify bidirectional connectivity: the server can reach the camera (MQTT) AND the camera can reach the server (HTTP for photo download). Include a connectivity test endpoint.
- Serve personnel photos from a dedicated public route (`/api/personnel/{id}/photo`) with no authentication (per the key decision in PROJECT.md) and ensure the URL uses the server's IP address reachable from the camera subnet, not `localhost` or a hostname the camera cannot resolve.
- Parse the per-person result in the enrollment ACK. If a person's enrollment failed due to photo download error, mark it specifically (not just generic "failed") so the operator can diagnose the network issue.
- Pre-validate photos before enrollment: check that the image meets the <1MB, <1080p constraint before even publishing the MQTT command.

**Detection:** Enrollment ACKs showing per-person failures with photo-related error codes. Personnel stuck in "failed" enrollment state across all cameras (indicating a server-side photo serving issue rather than a per-camera problem).

**Phase relevance:** Phase 2 (Enrollment Sync). Must validate photo serving accessibility as part of camera setup.

---

### Pitfall 10: Alert Fatigue from Unfiltered Recognition Events

**What goes wrong:** Every face recognition event (including routine "allowed" personnel walking past cameras multiple times per day) generates an alert in the feed. The operator's screen fills with low-severity green (info) alerts, burying the critical red (block-list) alerts. The operator starts ignoring the feed entirely.

**Prevention:**
- Default the alert feed to show only critical and warning alerts. Provide a filter toggle to include info-level events.
- Implement client-side deduplication: if the same person is recognized by the same camera within N seconds, collapse into a single entry with a count badge.
- Use distinct visual hierarchy: critical alerts get full-width red banners with sound; warnings get amber compact cards; info events are small, muted rows.
- The event history page (not the live feed) is where operators review all events including routine ones.
- Consider a configurable "quiet period" per person per camera (e.g., 5 minutes) where repeated recognitions are suppressed from the live feed but still logged.

**Detection:** Operator feedback that the dashboard is "too noisy." Alert feed scrolling faster than a human can read. Critical alerts being missed in testing.

**Phase relevance:** Phase 4 (Dashboard / Alert Feed). Design the alert hierarchy and filtering from the start.

## Minor Pitfalls

### Pitfall 11: MQTT Topic Subscription Using Overly Broad Wildcards

**What goes wrong:** Subscribing to `#` (all topics) to "catch everything" causes the listener to process broker system messages, other application traffic, and creates a security/performance risk. Even subscribing to `camera/#` without understanding the full topic tree wastes CPU processing irrelevant messages.

**Prevention:**
- Subscribe to specific topic patterns per message type: `{deviceId}/Rec`, `{deviceId}/Ack`, `{deviceId}/basic`, `{deviceId}/heartbeat`.
- If using a wildcard for convenience (e.g., `+/Rec` for all cameras), validate the device ID against the registered cameras table before processing. Discard messages from unknown devices.
- Use single-level wildcards (`+`) not multi-level (`#`) to avoid recursive topic tree traversal overhead.

**Detection:** MQTT listener processing messages from unknown device IDs. Broker CPU usage higher than expected for the number of cameras.

**Phase relevance:** Phase 1 (MQTT Listener topic subscription design).

---

### Pitfall 12: SQLite-to-MySQL Migration Gaps

**What goes wrong:** The existing app uses SQLite. FRAS requires MySQL for JSON columns, proper indexing on large tables, and concurrent write support. Migration scripts written against SQLite may use syntax or types that behave differently in MySQL (e.g., boolean handling, JSON path queries, datetime precision, foreign key enforcement).

**Prevention:**
- Switch the development environment to MySQL before writing any FRAS migrations. Do not develop against SQLite and "migrate later."
- Use Laravel's database-agnostic migration methods. Avoid raw SQL in migrations.
- Test all existing migrations against MySQL before starting FRAS work to surface any incompatibilities early.
- Configure the test suite to also run against MySQL (or use RefreshDatabase with MySQL) for FRAS-specific tests.

**Detection:** Migration failures when switching `DB_CONNECTION` from sqlite to mysql. Test failures due to different type coercion behavior.

**Phase relevance:** Phase 0 (Project setup / infrastructure). Must be resolved before any FRAS development begins.

---

### Pitfall 13: Reverb WebSocket Connection Behind Reverse Proxy Fails

**What goes wrong:** The dashboard uses `wss://` for WebSocket connections via Laravel Reverb. If Nginx or another reverse proxy is not configured for WebSocket upgrade headers, connections fail silently. Mixed content (HTTPS page, WS connection) is blocked by browsers.

**Prevention:**
- Configure Nginx with proper WebSocket proxy pass: `proxy_set_header Upgrade $http_upgrade; proxy_set_header Connection "upgrade";`.
- Ensure Reverb listens on `wss://` in production (or is proxied through Nginx's TLS termination).
- Since this is a Herd development environment, verify that Herd's built-in proxy supports WebSocket upgrades for the `.test` domain.
- Include a connection status indicator in the dashboard status bar: "Live" (green) when WebSocket is connected, "Disconnected" (red) when it drops, with auto-reconnect.

**Detection:** Browser console showing WebSocket connection refused or mixed content errors. Dashboard showing stale data (no live updates). Connection indicator showing "Disconnected."

**Phase relevance:** Phase 3 (Real-time Broadcast setup). Must be verified in both development (Herd) and production (Nginx) environments.

---

### Pitfall 14: Heartbeat Timeout Too Aggressive or Too Lenient

**What goes wrong:** The spec says cameras are marked offline when heartbeat is absent for >90 seconds. If heartbeat interval is 60 seconds and the timeout is 90 seconds, a single dropped heartbeat (QoS 0, remember) causes a false offline status. If the timeout is too lenient (e.g., 5 minutes), genuinely offline cameras are not detected promptly.

**Prevention:**
- Set the offline threshold to at least 2x the heartbeat interval plus a buffer. If heartbeat is every 60 seconds, use 150 seconds (2.5x) rather than 90 seconds.
- When a camera transitions to "offline," do not immediately alarm. Wait for a second missed heartbeat to confirm, then transition to offline.
- Use retained messages on the heartbeat topic so the listener always knows the last heartbeat time on reconnect, avoiding false offlines after listener restarts.
- Log heartbeat irregularities (jitter, gaps) to identify cameras with unstable connections before they go fully offline.

**Detection:** Cameras flickering between online/offline status. False offline events correlating with MQTT listener restarts.

**Phase relevance:** Phase 1 (Camera Liveness). Tune the threshold based on observed heartbeat behavior from the test device.

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Phase 0: MySQL Migration | SQLite-to-MySQL incompatibilities (#12) | Switch to MySQL before writing any FRAS code |
| Phase 1: MQTT Listener | Memory leak / silent death (#2) | Supervisor + health check + max-memory from day one |
| Phase 1: MQTT Listener | QoS 0 message loss (#1) | Compensating timeout/retry mechanisms, not blind trust |
| Phase 1: MQTT Listener | Firmware payload quirks (#4) | CameraPayloadNormalizer with real fixture tests |
| Phase 1: Camera Liveness | False offline from heartbeat jitter (#14) | 2.5x heartbeat interval threshold, retained messages |
| Phase 2: Enrollment Sync | ACK correlation failure (#3) | Single-batch-per-camera state machine, timeout-and-retry |
| Phase 2: Enrollment Sync | Camera cannot fetch photos (#9) | Connectivity check during camera registration |
| Phase 3: Real-time Broadcast | WebSocket proxy misconfiguration (#13) | Verify Nginx/Herd WebSocket upgrade headers |
| Phase 3: Real-time Broadcast | Event race conditions / duplicates (#6) | Monotonic IDs, client-side deduplication Set |
| Phase 4: Dashboard | Audio autoplay blocked (#5) | Explicit user gesture to unlock AudioContext |
| Phase 4: Dashboard | Alert fatigue (#10) | Severity filtering defaults, collapse duplicates |
| Phase 4: Map View | Marker DOM thrashing (#7) | Use symbol/circle layers, not HTML Markers |
| Phase 5: Retention | Disk space exhaustion (#8) | Scheduled cleanup from Phase 1, structured directories |

## Sources

- [MQTT QoS Explained - EMQ](https://www.emqx.com/en/blog/introduction-to-mqtt-qos)
- [MQTT QoS Essentials - HiveMQ](https://www.hivemq.com/blog/mqtt-essentials-part-6-mqtt-quality-of-service-levels/)
- [MQTT Persistent Sessions - HiveMQ](https://www.hivemq.com/blog/mqtt-essentials-part-7-persistent-session-queuing-messages/)
- [MQTT Topics and Wildcards Best Practices - HiveMQ](https://www.hivemq.com/blog/mqtt-essentials-part-5-mqtt-topics-best-practices/)
- [MQTT Message Ordering - HiveMQ](https://www.hivemq.com/blog/understanding-mqtt-message-ordering/)
- [php-mqtt/client - Skipping Messages Issue #73](https://github.com/php-mqtt/client/issues/73)
- [php-mqtt/client - Client Disconnecting Issue #65](https://github.com/php-mqtt/client/issues/65)
- [Avoiding Memory Leaks in Laravel Queue Workers](https://themsaid.com/avoiding-memory-leaks-when-running-laravel-queue-workers)
- [Handling Signals in Laravel - Fly.io](https://fly.io/laravel-bytes/handling-signals-in-laravel/)
- [Mapbox GL JS Performance](https://docs.mapbox.com/help/troubleshooting/mapbox-gl-js-performance/)
- [Mapbox Markers vs Layers](https://docs.mapbox.com/help/troubleshooting/markers-vs-layers/)
- [Mapbox Updating Markers Real-time Issue #4514](https://github.com/mapbox/mapbox-gl-native/issues/4514)
- [Chrome Autoplay Policy](https://developer.chrome.com/blog/autoplay)
- [Web Audio Autoplay and Games - Chrome](https://developer.chrome.com/blog/web-audio-autoplay)
- [Mosquitto Configuration - Message Queuing](https://mosquitto.org/man/mosquitto-conf-5.html)
- [Laravel Reverb Documentation](https://laravel.com/docs/12.x/reverb)
- [Scaling Laravel Reverb - Real-time WebSockets](https://dev.to/ameer-pk/mastering-real-time-architectures-scaling-laravel-13-reverb-with-nextjs-15-3cim)
- [Retry Patterns in Distributed Systems - ByteByteGo](https://blog.bytebytego.com/p/a-guide-to-retry-pattern-in-distributed)
- [Timeouts, Retries, and Backoff with Jitter - AWS](https://aws.amazon.com/builders-library/timeouts-retries-and-backoff-with-jitter/)
- [Face Recognition Challenges - AIMultiple](https://research.aimultiple.com/facial-recognition-challenges/)
- [Reducing False Positives in Video Surveillance - March Networks](https://www.marchnetworks.com/blog/the-top-10-benefits-of-reducing-false-positives-with-your-video-surveillance-system/)
- [Intervention Image Memory Issues - GitHub #846](https://github.com/Intervention/image/issues/846)

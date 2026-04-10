# SECURITY.md — HDS-FRAS Phase 02: Camera Management & Liveness

**Phase:** 02 — Camera Management & Liveness
**ASVS Level:** 1
**Audited:** 2026-04-10
**Threats Closed:** 12/12

---

## Threat Verification

| Threat ID | Category | Disposition | Evidence |
|-----------|----------|-------------|----------|
| T-02-01 | Elevation of Privilege | mitigate | `routes/web.php:11` — `Route::middleware(['auth', 'verified'])` wraps entire camera resource |
| T-02-02 | Tampering | mitigate | `app/Http/Requests/Camera/StoreCameraRequest.php:25,27` — `between:-90,90`, `between:-180,180`, `max:255` on all string fields |
| T-02-03 | Spoofing | mitigate | `app/Http/Requests/Camera/StoreCameraRequest.php:24` — `unique:cameras,device_id`; `app/Http/Requests/Camera/UpdateCameraRequest.php:25` — `Rule::unique()->ignore()` for self-update |
| T-02-04 | Information Disclosure | accept | See accepted risks log below |
| T-02-05 | Spoofing | accept | See accepted risks log below |
| T-02-06 | Spoofing | accept | See accepted risks log below — handler validation at `app/Mqtt/Handlers/OnlineOfflineHandler.php:25` (`in_array($operator, ['Online', 'Offline'], true)`) is additional hardening beyond the accepted boundary |
| T-02-07 | Denial of Service | mitigate | `app/Mqtt/Handlers/HeartbeatHandler.php:30-31` — `Camera::where('device_id', $facesluiceId)->update(['last_seen_at' => now()])` (bulk update, no model hydration) |
| T-02-08 | Denial of Service | mitigate | `app/Mqtt/Handlers/OnlineOfflineHandler.php:64` — `if ($wasOnline !== $isOnline)` guards `CameraStatusChanged::dispatch()` |
| T-02-09 | Tampering | accept | See accepted risks log below |
| T-02-10 | Information Disclosure | accept | See accepted risks log below |
| T-02-11 | Spoofing | mitigate | `routes/channels.php:9-11` — `Broadcast::channel('fras.alerts', fn($user) => $user !== null)` requires authenticated user for PrivateChannel auth |
| T-02-12 | Tampering | mitigate | `app/Http/Requests/Camera/StoreCameraRequest.php` and `UpdateCameraRequest.php` — full server-side validation of all camera fields; frontend `type="text"` for coordinate inputs does not bypass server validation |

---

## Accepted Risks Log

### T-02-04 — Information Disclosure: Mapbox token in Inertia page props
- **Component:** `app/Http/Controllers/CameraController.php`
- **Risk:** The Mapbox access token is passed as a server-rendered Inertia prop (`mapboxToken`) and becomes visible in the browser's page JSON. Any authenticated user can read the token from the page source.
- **Rationale:** Mapbox access tokens are explicitly designed for client-side use. The Mapbox SDK requires the token to be present in browser JS to load tiles. This is standard Mapbox architecture.
- **Residual risk:** Token could be scraped and used for tile requests against the Mapbox account quota.
- **Control:** URL-restrict the Mapbox access token to `fras.test` and the production domain in Mapbox account settings. Monitor usage via Mapbox dashboard.
- **Accepted by:** HDS-FRAS team
- **Review date:** 2026-04-10

### T-02-05 — Spoofing: MQTT HeartbeatHandler trusts message origin
- **Component:** `app/Mqtt/Handlers/HeartbeatHandler.php`
- **Risk:** MQTT v3.1.1 at QoS 0 has no per-message authentication. Any device on the MQTT broker network can publish a HeartBeat payload with any `facesluiceId`, causing arbitrary camera `last_seen_at` to be updated.
- **Rationale:** The MQTT broker is on the facility's internal camera subnet. Per project spec section 7.1, MQTT TLS/mTLS is out of scope for v1. The blast radius is limited — a spoofed heartbeat only updates `last_seen_at`, it cannot change `is_online` status or trigger broadcasts.
- **Residual risk:** An attacker on the internal network could prevent offline detection for a specific camera by spoofing its heartbeats.
- **Control:** Network-level isolation of camera subnet. MQTT TLS deferred to a future hardening phase.
- **Accepted by:** HDS-FRAS team
- **Review date:** 2026-04-10

### T-02-06 — Spoofing: MQTT OnlineOfflineHandler trusts message origin
- **Component:** `app/Mqtt/Handlers/OnlineOfflineHandler.php`
- **Risk:** Same MQTT trust boundary as T-02-05. An attacker on the internal network could publish spoofed Online/Offline messages with any `facesluiceId`, changing camera status and triggering WebSocket broadcasts to all connected operators.
- **Rationale:** Same network isolation rationale as T-02-05. The handler validates the `operator` field is exactly `'Online'` or `'Offline'` (strict comparison), reducing but not eliminating the spoofing surface.
- **Residual risk:** Spoofed messages could create false online/offline alerts for operators. No data integrity impact — camera configuration is unaffected.
- **Control:** Network-level isolation of camera subnet. MQTT TLS deferred to a future hardening phase.
- **Accepted by:** HDS-FRAS team
- **Review date:** 2026-04-10

### T-02-09 — Tampering: CheckOfflineCamerasCommand threshold from config
- **Component:** `app/Console/Commands/CheckOfflineCamerasCommand.php`
- **Risk:** The offline detection threshold (`config('hds.alerts.camera_offline_threshold')`) is read from application config, which is sourced from the `.env` file. A compromised server environment could alter `FRAS_OFFLINE_THRESHOLD` to prevent or accelerate offline detection.
- **Rationale:** The command runs as a scheduled system process with no external HTTP input surface. The threshold is server-controlled and not user-supplied. An attacker would need server-level access to modify `.env`, at which point the application itself is compromised — this is out of scope for application-layer security.
- **Residual risk:** Server compromise could suppress offline alerting.
- **Control:** OS-level file permissions on `.env`. No mitigation at the application layer is warranted.
- **Accepted by:** HDS-FRAS team
- **Review date:** 2026-04-10

### T-02-10 — Information Disclosure: Mapbox token in Vue component props
- **Component:** `resources/js/components/MapboxMap.vue` (receives token as `accessToken` prop)
- **Risk:** The Mapbox access token is bundled into the SSR-rendered HTML and visible in the JS bundle and network requests.
- **Rationale:** Duplicate of T-02-04 at the frontend layer. Same Mapbox architecture rationale applies. URL restriction on the Mapbox account is the accepted control.
- **Residual risk:** Same as T-02-04.
- **Control:** URL-restrict the token on Mapbox account settings. Same control as T-02-04.
- **Accepted by:** HDS-FRAS team
- **Review date:** 2026-04-10

---

## Unregistered Flags

None. No `## Threat Flags` section was present in any Phase 02 SUMMARY file (02-01, 02-02, 02-03).

---

*Generated by GSD Security Auditor — Phase 02*
*ASVS Level: 1 | block_on: high*

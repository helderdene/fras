---
phase: 05-recognition-alerting
asvs_level: 1
audited: 2026-04-11
auditor: gsd-secure-phase
result: SECURED
threats_total: 15
threats_closed: 15
threats_open: 0
---

# Security Audit — Phase 05: Recognition Alerting

## Summary

All 15 registered threats verified. 11 mitigated threats have confirmed code evidence. 4 accepted threats are documented below. 0 threats are open. No unregistered threat flags were raised in any SUMMARY.md file.

## Threat Verification

| Threat ID | STRIDE | Component | Disposition | Status | Evidence |
|-----------|--------|-----------|-------------|--------|----------|
| T-5-01 | Tampering | AlertSeverity::fromEvent() | mitigate | CLOSED | app/Enums/AlertSeverity.php:17 — params typed `int $personType, int $verifyStatus`; unknown verifyStatus falls through to `Ignored` (fail-safe default) |
| T-5-02 | Information Disclosure | RecognitionAlert broadcast | mitigate | CLOSED | app/Events/RecognitionAlert.php:65 — `new PrivateChannel('fras.alerts')`; routes/channels.php:9 — `Broadcast::channel('fras.alerts', fn($user) => $user !== null)` requires authenticated user |
| T-5-03 | Information Disclosure | RecognitionEvent face_image_url accessor | mitigate | CLOSED | app/Models/RecognitionEvent.php:88–92 — accessor returns `/alerts/{id}/face` controller path, not raw storage path; routes/web.php:23–24 — those routes are inside `auth + verified` middleware group |
| T-5-04 | Tampering | RecognitionHandler payload parsing | mitigate | CLOSED | app/Mqtt/Handlers/RecognitionHandler.php:21 — rejects messages where `operator !== 'RecPush'`; line 113–116 — explicit `(int)` and `(float)` casts on all numeric fields; line 111 — null-coalesce with defaults throughout parsePayload |
| T-5-05 | Denial of Service | Base64 image decoding | mitigate | CLOSED | app/Mqtt/Handlers/RecognitionHandler.php:148–156 — decoded size checked against `$maxBytes` (1 048 576 for face, 2 097 152 for scene); oversized images rejected with Log::warning, event still created |
| T-5-06 | Tampering | Base64 data injection | mitigate | CLOSED | app/Mqtt/Handlers/RecognitionHandler.php:139 — `preg_replace('#^data:image/\w+;base64,#', '', $dataUri)` strips data URI prefix; line 140 — `base64_decode($base64, true)` strict mode rejects non-base64; line 142 — false return treated as failure |
| T-5-07 | Spoofing | RecPush for unknown camera | accept | CLOSED | See accepted risks log below |
| T-5-08 | Information Disclosure | Raw payload stored in DB | accept | CLOSED | See accepted risks log below |
| T-5-09 | Information Disclosure | faceImage/sceneImage routes | mitigate | CLOSED | routes/web.php:14 — all alert routes inside `Route::middleware(['auth', 'verified'])->group()`; AlertController.php:56–63, 67–73 — 404 for missing paths, otherwise `Storage::disk('local')->response()` |
| T-5-10 | Elevation of Privilege | acknowledge endpoint | accept | CLOSED | See accepted risks log below |
| T-5-11 | Tampering | Route model binding {event} | mitigate | CLOSED | routes/web.php:21–24 — `{event}` parameter with `RecognitionEvent $event` type-hint; Laravel returns 404 for non-existent IDs; AlertController.php:33–34, 45 — only `acknowledged_by`, `acknowledged_at`, `dismissed_at` columns updated (no mass-assignment vector) |
| T-5-12 | Tampering | XSS via person_name in feed | mitigate | CLOSED | resources/js/components/AlertFeedItem.vue — no `v-html` directives present (grep confirmed zero matches); all interpolation uses `{{ }}` mustache syntax (Vue auto-escapes); resources/js/pages/alerts/Index.vue — same, no v-html |
| T-5-13 | Tampering | Image URL manipulation | mitigate | CLOSED | resources/js/components/AlertFeedItem.vue:134 — `faceImage.url(event)` (Wayfinder route function); AlertDetailModal.vue:105,120 — `faceImage.url(event)`, `sceneImage.url(event)`; no user-supplied string rendered in img src anywhere |
| T-5-14 | Denial of Service | Rapid WebSocket events flooding UI | mitigate | CLOSED | resources/js/pages/alerts/Index.vue:138–140 — `alerts.value.slice(0, 50)` enforced after each prepend; useAlertSound.ts:38–41 — `audio.cloneNode()` for overlapping playback, non-blocking |
| T-5-15 | Information Disclosure | Alert data in browser memory | accept | CLOSED | See accepted risks log below |

## Accepted Risks Log

| Threat ID | Rationale | Owner | Review Trigger |
|-----------|-----------|-------|----------------|
| T-5-07 (Spoofing — unknown camera) | Handler logs warning and returns early with no state change. Camera must be pre-enrolled in DB. Acceptable for single-site deployment where camera provisioning is controlled. | Ops | New camera provisioning workflow added |
| T-5-08 (Info Disclosure — raw payload in DB) | Raw MQTT payload stored for forensic audit trail. DB access requires authentication. Payload contains only data the camera firmware already captured (face metadata, bbox, similarity scores). No additional PII risk beyond what the camera itself exposes. | DBA | PII classification review or DB access control change |
| T-5-10 (EoP — acknowledge endpoint) | Any authenticated operator can acknowledge any alert. Role-based access control is explicitly out of scope for v1 per REQUIREMENTS.md for a single-site command center. | Product | Role separation requirement added to backlog |
| T-5-15 (Info Disclosure — alert data in browser memory) | Operator workstations are a trusted environment within the command center. Alert data is already authorized at the channel subscription layer (T-5-02 mitigated). No additional exposure beyond what the operator is already authorized to see. | Ops | Shared workstation policy or untrusted operator scenario introduced |

## Unregistered Flags

None. No `## Threat Flags` sections were present in any of the four SUMMARY.md files (05-01 through 05-04).

## Files Audited

- app/Enums/AlertSeverity.php
- app/Events/RecognitionAlert.php
- app/Http/Controllers/AlertController.php
- app/Models/RecognitionEvent.php
- app/Mqtt/Handlers/RecognitionHandler.php
- resources/js/pages/alerts/Index.vue
- resources/js/components/AlertFeedItem.vue
- resources/js/components/AlertDetailModal.vue
- resources/js/components/SceneImageOverlay.vue
- resources/js/composables/useAlertSound.ts
- routes/web.php
- routes/channels.php

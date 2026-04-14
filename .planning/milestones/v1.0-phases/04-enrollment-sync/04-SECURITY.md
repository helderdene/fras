---
phase: 04-enrollment-sync
audited: 2026-04-10
asvs_level: 1
auditor: gsd-security-auditor
---

# Security Audit — Phase 04: Enrollment Sync

## Summary

**Threats Closed:** 13/13
**ASVS Level:** 1
**Block On:** high
**Result:** SECURED

---

## Threat Verification

| Threat ID | Category | Disposition | Status | Evidence |
|-----------|----------|-------------|--------|----------|
| T-4-01 | Spoofing | mitigate | CLOSED | `routes/web.php:13` — `Route::middleware(['auth', 'verified'])->group(...)` wraps all camera and personnel routes including `Route::resource('cameras', ...)` and `Route::resource('personnel', ...)` |
| T-4-02 | Spoofing | accept | CLOSED | Accepted: MQTT broker credential auth; fake ACK requires exact messageId (timestamp + Str::random(6)); internal trusted network per project scope. Documented in plan 04-02 threat model. |
| T-4-03 | Elevation of Privilege | mitigate | CLOSED | `routes/web.php:17-18` — `enrollment.retry` and `enrollment.resync-all` POST routes are inside the `['auth', 'verified']` middleware group. `EnrollmentController` uses `RetryEnrollmentRequest` and `ResyncAllRequest` Form Request classes. |
| T-4-04 | Information Disclosure | accept | CLOSED | Accepted: enrollment summary counts are aggregate data (no PII); routes behind `auth` + `verified` middleware. Documented in plan 04-04 threat model. |
| T-4-05 | Tampering | accept | CLOSED | Accepted: MQTT broker uses credential auth; camera subnet is trusted internal network per project scope. Documented in plan 04-01 threat model. |
| T-4-06 | Information Disclosure | mitigate | CLOSED | `app/Services/CameraEnrollmentService.php:72` — messageId format `'EditPersonsNew' . now()->format('Y-m-d\TH:i:s') . '_' . Str::random(6)` produces non-guessable IDs. `Cache::put(...)` at line 76 with TTL of `ack_timeout_minutes * 60`. |
| T-4-07 | Denial of Service | mitigate | CLOSED | `app/Jobs/EnrollPersonnelBatch.php:32-35` — `WithoutOverlapping('enrollment-camera-' . $this->camera->id)->releaseAfter(30)->expireAfter(300)`. Batch chunking in `CameraEnrollmentService::enrollAllToCamera()` at line 54-58. |
| T-4-08 | Tampering | mitigate | CLOSED | `app/Mqtt/Handlers/AckHandler.php:53` — `Cache::pull($cacheKey)` atomically retrieves and deletes, preventing replay. `app/Console/Commands/CheckEnrollmentTimeoutsCommand.php:23` — timeout query uses `where('status', CameraEnrollment::STATUS_PENDING)` guard. |
| T-4-09 | Repudiation | mitigate | CLOSED | `app/Mqtt/Handlers/AckHandler.php:64-69` — `Log::info('Processing enrollment ACK', ['camera_id' => ..., 'messageId' => ...])`. `app/Events/EnrollmentStatusChanged.php:25` — `ShouldBroadcast` on `PrivateChannel('fras.alerts')` provides audit trail for all status transitions. |
| T-4-10 | Denial of Service | accept | CLOSED | Accepted: query in CheckEnrollmentTimeoutsCommand limited to pending enrollments only (bounded by cameras * personnel count, typically low); runs every minute. Documented in plan 04-02 threat model. |
| T-4-11 | Spoofing | mitigate | CLOSED | `app/Events/EnrollmentStatusChanged.php:25-28` — `broadcastOn()` returns `new PrivateChannel('fras.alerts')`, requiring authenticated WebSocket connection via Laravel Echo. |
| T-4-12 | Information Disclosure | accept | CLOSED | Accepted: error messages returned via `CameraEnrollmentService::translateErrorCode()` are operator-friendly strings per FRAS spec codes (e.g., "No usable face detected in photo"), not raw system errors. Documented in plan 04-03 threat model. |
| T-4-13 | Denial of Service | accept | CLOSED | Accepted: `withCount` subqueries in `PersonnelController::index()` (line 45-49) bounded by camera count expected < 20; uses efficient SQL subqueries. Documented in plan 04-04 threat model. |

---

## Accepted Risks Log

| Threat ID | Category | Rationale |
|-----------|----------|-----------|
| T-4-02 | Spoofing — AckHandler MQTT message | MQTT broker requires MQTT_USERNAME/MQTT_PASSWORD credentials. Fake ACK requires knowing the exact messageId (timestamp + 6 random chars generated server-side). Deployment is a single-site internal network; camera subnet is trusted per project scope. Risk accepted by project owner. |
| T-4-04 | Information Disclosure — Enrollment summary counts | Data exposed is aggregate counts only (enrolled/pending/failed per camera), no personally identifiable information. All routes are behind `auth` + `verified` middleware. |
| T-4-05 | Tampering — MQTT payload construction | MQTT broker uses credential authentication. Camera subnet is an internal trusted network per project scope. No public-facing MQTT endpoint. Risk accepted by project owner. |
| T-4-10 | Denial of Service — Timeout command query | Query is scoped to `status = 'pending'` rows only, bounded by the product of camera count and personnel count. At the declared scale (< 20 cameras, < 1000 personnel), maximum rows is 20,000 which is well within safe query bounds. Scheduled every minute with a configurable timeout window. |
| T-4-12 | Information Disclosure — Enrollment error messages | All error messages are human-readable operator guidance produced by `translateErrorCode()`. No stack traces, database errors, or internal paths are exposed. |
| T-4-13 | Denial of Service — withCount queries on index page | Bounded by camera count (declared < 20). Laravel `withCount` uses a single SQL subquery per count, not separate queries per row. |

---

## Unregistered Flags

No threat flags were raised in SUMMARY.md files for plans 04-01 through 04-04. No unregistered flags to log.

---

## Files Audited

- `.planning/phases/04-enrollment-sync/04-01-PLAN.md`
- `.planning/phases/04-enrollment-sync/04-02-PLAN.md`
- `.planning/phases/04-enrollment-sync/04-03-PLAN.md`
- `.planning/phases/04-enrollment-sync/04-04-PLAN.md`
- `.planning/phases/04-enrollment-sync/04-01-SUMMARY.md`
- `.planning/phases/04-enrollment-sync/04-02-SUMMARY.md`
- `.planning/phases/04-enrollment-sync/04-03-SUMMARY.md`
- `.planning/phases/04-enrollment-sync/04-04-SUMMARY.md`
- `app/Http/Controllers/PersonnelController.php`
- `app/Http/Controllers/EnrollmentController.php`
- `app/Mqtt/Handlers/AckHandler.php`
- `app/Services/CameraEnrollmentService.php`
- `app/Events/EnrollmentStatusChanged.php`
- `app/Jobs/EnrollPersonnelBatch.php`
- `app/Console/Commands/CheckEnrollmentTimeoutsCommand.php`
- `routes/web.php`

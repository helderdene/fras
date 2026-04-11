# Security Audit — Phase 07: Event History & Operations

**Audited:** 2026-04-11
**ASVS Level:** 1
**Phase:** 07-event-history-operations
**Threats Closed:** 8/8
**Open Threats:** 0/8

---

## Threat Verification

| Threat ID | Category | Disposition | Status | Evidence |
|-----------|----------|-------------|--------|----------|
| T-7-01 | Tampering | mitigate | CLOSED | `app/Http/Controllers/EventHistoryController.php:16-20` — `$allowedSorts = ['captured_at', 'similarity', 'severity']` with `in_array($request->input('sort'), $allowedSorts, true)` and fallback to `'captured_at'`. Raw sort input never interpolated into ORDER BY. |
| T-7-02 | Tampering | mitigate | CLOSED | `app/Http/Controllers/EventHistoryController.php:35-39` — All LIKE queries use Eloquent `where('col', 'like', "%{$search}%")` and `whereHas` with bound parameters. No raw SQL string concatenation in ORDER BY or WHERE clauses. |
| T-7-03 | Information Disclosure | mitigate | CLOSED | `routes/web.php:16,28` — `Route::get('events', ...)` is registered inside `Route::middleware(['auth', 'verified'])->group(...)`. Unauthenticated and unverified requests cannot reach the controller. |
| T-7-04 | Tampering | accept | CLOSED | No new image-serving routes were introduced in this phase. The accepted risk is documented here: the existing `AlertController::faceImage` and `sceneImage` routes use Eloquent model binding and `Storage::disk` without user-controlled path input. No path traversal surface added. |
| T-7-05 | Denial of Service | mitigate | CLOSED | `app/Console/Commands/CleanupRetentionImagesCommand.php:44` — `->chunkById(200, ...)` prevents full-table load into memory. `routes/console.php:13` — schedule entry calls `->withoutOverlapping()` to prevent concurrent runs. |
| T-7-06 | Tampering | mitigate | CLOSED | `app/Http/Controllers/EventHistoryController.php:22` — `$direction = $request->input('direction') === 'asc' ? 'asc' : 'desc'` — binary ternary; any value other than the literal string `'asc'` resolves to `'desc'`. No user string reaches the query directly. |
| T-7-07 | Information Disclosure | accept | CLOSED | `app/Models/RecognitionEvent.php:14` — `#[Hidden(['raw_payload', 'face_image_path', 'scene_image_path'])]` attribute excludes sensitive storage paths and raw MQTT payloads from all serialized Inertia props. Image URLs are served only through auth-protected `alerts.face-image` / `alerts.scene-image` routes (in scope of T-7-03 middleware group). |
| T-7-08 | Spoofing (XSS) | mitigate | CLOSED | `resources/js/pages/events/Index.vue` and `resources/js/components/EventHistoryTable.vue` — zero occurrences of `v-html`. All event data rendered exclusively via `{{ }}` Vue template interpolation, which auto-escapes HTML entities. Confirmed by grep returning no matches. |

---

## Accepted Risks Log

| Threat ID | Risk | Rationale | Owner |
|-----------|------|-----------|-------|
| T-7-04 | Image path traversal via serving routes | No new image-serving routes added in this phase. Existing routes rely on Eloquent model binding (route model binding resolves ID to model; path comes from DB record, not request). Risk surface unchanged from prior phase. | Phase 07 |
| T-7-07 | Sensitive fields in serialized event data | `#[Hidden]` attribute on `RecognitionEvent` excludes `raw_payload`, `face_image_path`, `scene_image_path` from JSON/array serialization. Image access gated by `auth+verified` middleware on serving routes. | Phase 07 |

---

## Unregistered Threat Flags

None. The `## Threat Flags` sections of both SUMMARY files contained no flags outside the registered threat register.

---

## Notes

- The `like "%{$search}%"` pattern (T-7-02) relies on PDO prepared statement parameter binding supplied by Eloquent's query builder. The interpolation into the PHP string constructs the bound parameter value, not raw SQL; the actual SQL parameter binding occurs at the PDO driver level. This is the correct Eloquent pattern and does not constitute SQL injection risk.
- Eloquent `orderBy($sort, $direction)` at `EventHistoryController.php:43` is safe because both `$sort` and `$direction` are validated to enum-equivalent sets before reaching the query builder (T-7-01, T-7-06).

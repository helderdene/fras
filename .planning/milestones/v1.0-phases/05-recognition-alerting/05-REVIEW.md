---
phase: 05-recognition-alerting
reviewed: 2026-04-11T18:30:00Z
depth: standard
files_reviewed: 21
files_reviewed_list:
  - app/Enums/AlertSeverity.php
  - app/Events/RecognitionAlert.php
  - app/Http/Controllers/AlertController.php
  - app/Models/RecognitionEvent.php
  - app/Mqtt/Handlers/RecognitionHandler.php
  - database/factories/RecognitionEventFactory.php
  - database/migrations/2026_04_11_000001_add_acknowledgment_columns_to_recognition_events_table.php
  - resources/js/components/AlertDetailModal.vue
  - resources/js/components/AlertFeedItem.vue
  - resources/js/components/AppHeader.vue
  - resources/js/components/AppSidebar.vue
  - resources/js/components/SceneImageOverlay.vue
  - resources/js/components/SeverityBadge.vue
  - resources/js/composables/useAlertSound.ts
  - resources/js/pages/alerts/Index.vue
  - resources/js/types/index.ts
  - resources/js/types/recognition.ts
  - routes/web.php
  - tests/Feature/Recognition/AlertControllerTest.php
  - tests/Feature/Recognition/AlertSeverityTest.php
  - tests/Feature/Recognition/RecognitionAlertTest.php
  - tests/Feature/Recognition/RecognitionHandlerTest.php
findings:
  critical: 1
  warning: 4
  info: 3
  total: 8
status: issues_found
---

# Phase 5: Code Review Report

**Reviewed:** 2026-04-11T18:30:00Z
**Depth:** standard
**Files Reviewed:** 21
**Status:** issues_found

## Summary

This phase introduces the recognition alerting subsystem: severity classification (AlertSeverity enum), MQTT recognition event handling, real-time broadcasting via Laravel Reverb, and a Vue-based live alert feed with acknowledge/dismiss workflows. The backend code is well-structured with proper separation of concerns, comprehensive test coverage, and correct use of Laravel patterns (Eloquent casts, event broadcasting, route model binding). The frontend follows project conventions with `<script setup lang="ts">`, Wayfinder route functions, and composable patterns.

Key concerns: (1) the `acknowledged_by` field is hardcoded to `1` in the optimistic frontend update rather than using the authenticated user's ID, (2) the `raw_payload` column -- which stores the full MQTT JSON including base64-encoded images -- is mass-assignable and serialized to the frontend without being hidden, exposing potentially large payloads and sensitive camera data, and (3) the `AlertController` lacks authorization checks on acknowledge/dismiss/image endpoints, meaning any authenticated user can acknowledge or dismiss any event and access any stored image.

## Critical Issues

### CR-01: `raw_payload` exposed to frontend via Inertia serialization

**File:** `app/Models/RecognitionEvent.php:13-37`
**Issue:** The `raw_payload` column stores the complete MQTT JSON payload including base64-encoded face and scene images (up to 1MB + 2MB respectively). This column is not marked as `$hidden`, so it is serialized to the frontend via the `AlertController::index()` Eloquent query. With 50 events loaded, this could send hundreds of megabytes of base64 image data to the browser unnecessarily. Additionally, the raw payload may contain sensitive camera metadata (IP addresses, firmware versions, device identifiers) that should not be exposed to the frontend.
**Fix:**
Add `raw_payload` (and other internal-only fields) to the model's `$hidden` array:
```php
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Hidden(['raw_payload', 'face_image_path', 'scene_image_path'])]
class RecognitionEvent extends Model
```
Alternatively, use an Eloquent API Resource to explicitly control which fields are serialized. The `face_image_path` and `scene_image_path` columns are also internal storage paths that should not be exposed -- the model already provides `face_image_url` and `scene_image_url` accessors for frontend use.

## Warnings

### WR-01: Hardcoded `acknowledged_by = 1` in optimistic update

**File:** `resources/js/pages/alerts/Index.vue:165`
**Issue:** When an alert is acknowledged, the optimistic update hardcodes `acknowledged_by = 1` instead of using the authenticated user's actual ID. This means the local state incorrectly attributes the acknowledgment to user ID 1 regardless of who is logged in. While the server stores the correct value, the client-side state is wrong until the next page load, and any UI that displays "acknowledged by" information will show incorrect data.
**Fix:**
Import the authenticated user from Inertia shared props and use their ID:
```typescript
import { usePage } from '@inertiajs/vue3';

const page = usePage();

function handleAcknowledge(event: RecognitionEvent): void {
    http.submit(acknowledgeRoute.post(event), {
        onSuccess: () => {
            const alert = alerts.value.find((a) => a.id === event.id);
            if (alert) {
                alert.acknowledged_at = new Date().toISOString();
                alert.acknowledged_by = page.props.auth.user.id;
            }
        },
    });
}
```

### WR-02: Missing authorization on acknowledge, dismiss, and image endpoints

**File:** `app/Http/Controllers/AlertController.php:31-73`
**Issue:** The `acknowledge`, `dismiss`, `faceImage`, and `sceneImage` methods have no authorization checks beyond the route-level `auth` middleware. Any authenticated user can acknowledge or dismiss any recognition event, and access any stored face/scene image. Per the project's security best practices (`rules/security.md`), every action should be authorized. In a security-sensitive face recognition system, this is particularly important -- an operator should not be able to dismiss another operator's critical alerts without proper authorization, and image access should be controlled.
**Fix:**
At minimum, consider adding a Policy or Gate check. If all authenticated users should indeed have these permissions (single command center with trusted operators), document this decision with a comment. Otherwise, create a policy:
```php
public function acknowledge(RecognitionEvent $event): RedirectResponse
{
    $this->authorize('acknowledge', $event);
    // ...
}
```

### WR-03: No validation that `faceImage`/`sceneImage` file exists on disk before serving

**File:** `app/Http/Controllers/AlertController.php:56-73`
**Issue:** The `faceImage` and `sceneImage` methods check if the database path is non-null, but do not verify the file actually exists on disk before calling `Storage::disk('local')->response()`. If the file was deleted (e.g., by a retention cleanup job), `Storage::response()` will throw a `FileNotFoundException` resulting in an unhandled 500 error instead of a clean 404.
**Fix:**
Add an existence check before serving:
```php
public function faceImage(RecognitionEvent $event): StreamedResponse
{
    if (! $event->face_image_path || ! Storage::disk('local')->exists($event->face_image_path)) {
        abort(404);
    }

    return Storage::disk('local')->response($event->face_image_path);
}
```

### WR-04: `isAcknowledged`/`isDismissed` checks may fail with falsy string comparison

**File:** `resources/js/components/AlertDetailModal.vue:62-63` and `resources/js/components/AlertFeedItem.vue:59-60`
**Issue:** The computed properties use strict equality `!== null` to check acknowledgment/dismissal status. However, the `acknowledged_at` and `dismissed_at` fields come from the server as ISO 8601 strings or `null`. When the optimistic update in `Index.vue` sets `acknowledged_at = new Date().toISOString()`, this works correctly. But if the server ever returns an empty string `""` or `undefined` (e.g., from a missing JSON key in the broadcast payload), the check `!== null` would incorrectly evaluate to `true`. The `RecognitionEvent` TypeScript type defines these as `string | null`, so `undefined` would be a type violation, but the broadcast payload mapping at line 108-109 of `Index.vue` explicitly sets these to `null`, which is correct. This is a minor robustness concern -- consider using a truthy check instead.
**Fix:**
Use a truthy check for more defensive behavior:
```typescript
const isAcknowledged = computed(() => !!props.event?.acknowledged_at);
const isDismissed = computed(() => !!props.event?.dismissed_at);
```

## Info

### IN-01: `similarity` value may need scaling clarification

**File:** `resources/js/pages/alerts/Index.vue:163-164` and `resources/js/components/AlertDetailModal.vue:159-160`
**Issue:** The similarity is displayed as `(event.similarity * 100).toFixed(1)%`. However, the camera firmware sends similarity as a value like `83.000000` (already a percentage), and the handler stores it as `(float) ($info['similarity1'] ?? 0)` without dividing by 100. This means `event.similarity` is already in the 0-100 range. Multiplying by 100 would display `8300.0%` for a similarity of 83. Verify whether the value stored is a ratio (0.0-1.0) or a percentage (0-100) and adjust the display accordingly.
**Fix:**
If similarity is stored as a percentage (0-100), display without multiplication:
```vue
{{ event.similarity.toFixed(1) }}%
```
If stored as a ratio (0.0-1.0), the current multiplication is correct. Check the `RecognitionHandlerTest` -- it stores `83.0` and `95.5`, suggesting percentage format, which means the multiplication is a bug.

### IN-02: `filterCounts` does not include an `ignored` count but `Ignored` events are excluded from the query

**File:** `resources/js/pages/alerts/Index.vue:58-63`
**Issue:** The `filterCounts` computed property has counts for `all`, `critical`, `warning`, and `info` but not `ignored`. This is intentionally correct since the `AlertController::index()` query excludes `Ignored` events via `whereIn('severity', [...])`. However, the filter pills array at line 193-198 does not include an `ignored` option either. This is consistent, but worth noting that if ignored events are ever included in the query (e.g., for a "show all" mode), the filter UI would need updating.
**Fix:** No action required. This is a design note for future reference.

### IN-03: Unused imports in `AppHeader.vue`

**File:** `resources/js/components/AppHeader.vue:9`
**Issue:** The `Search` icon from `lucide-vue-next` is imported and rendered (line 218-220) but the search button has no `@click` handler or functionality -- it is a non-functional UI element.
**Fix:** Either wire up search functionality or remove the search button to avoid confusing users with a non-functional control. If it is a placeholder for future work, add a comment.

---

_Reviewed: 2026-04-11T18:30:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

---
phase: 10-milestone-gap-closure
reviewed: 2026-04-14T00:00:00Z
depth: standard
files_reviewed: 15
files_reviewed_list:
  - .env.example
  - app/Events/CameraStatusChanged.php
  - app/Http/Controllers/AlertController.php
  - app/Http/Controllers/EventHistoryController.php
  - app/Models/RecognitionEvent.php
  - resources/js/components/AlertDetailModal.vue
  - resources/js/components/AlertFeedItem.vue
  - resources/js/pages/alerts/Index.vue
  - resources/js/pages/Dashboard.vue
  - resources/js/pages/events/Index.vue
  - resources/js/types/global.d.ts
  - resources/js/types/recognition.ts
  - tests/Feature/Camera/CameraCrudTest.php
  - tests/Feature/EventHistory/EventHistoryControllerTest.php
  - tests/Feature/Recognition/AlertControllerTest.php
findings:
  critical: 0
  warning: 4
  info: 5
  total: 9
status: issues_found
---

# Phase 10: Code Review Report

**Reviewed:** 2026-04-14T00:00:00Z
**Depth:** standard
**Files Reviewed:** 15
**Status:** issues_found

## Summary

Reviewed 15 source files covering the real-time alert pipeline, event history, broadcasting, frontend components, TypeScript types, and test coverage. The overall architecture is solid — auth protection is consistently applied, sort injection is properly guarded, and broadcast payloads are well-typed.

Four warnings were found: a potential null-pointer crash in `AlertController::acknowledge()` on the JSON response path, a type mismatch between the `personnel` relationship shape exposed by the server vs. consumed by the client, a missing `is_real_time` filter on the event history page that causes replay events to be inconsistently classified in tests vs. the alert feed, and a `setTimeout` leak risk in `AlertFeedItem.vue`. Five informational issues cover duplicate code, a missing `.env.example` entry, and minor quality items.

---

## Warnings

### WR-01: Null dereference on `auth()->user()` in `acknowledge()` response

**File:** `app/Http/Controllers/AlertController.php:46`

**Issue:** The `acknowledge()` method calls `auth()->user()->name` directly on line 46 to build the JSON response. While Laravel's authentication middleware ensures the user is authenticated before the controller runs, `auth()->user()` returns `Authenticatable|null`. If a request somehow bypasses the middleware guard (e.g., a misconfigured route, or a future refactor that strips the middleware), this will produce a fatal `Call to a member function name() on null`. The `acknowledge_at` cast on line 45 has the same implicit assumption but on the model, which is safer — the concern here is the raw `auth()->user()->name` call.

**Fix:**
```php
public function acknowledge(RecognitionEvent $event): JsonResponse
{
    $user = auth()->user();

    $event->update([
        'acknowledged_by' => $user->id,
        'acknowledged_at' => now(),
    ]);

    return response()->json([
        'acknowledged_at' => $event->acknowledged_at->toISOString(),
        'acknowledged_by' => $user->id,
        'acknowledger_name' => $user->name,
    ]);
}
```

---

### WR-02: `RecognitionEvent` type — `personnel.photo_path` served but `photo_url` consumed

**File:** `resources/js/types/recognition.ts:32`

**Issue:** The `RecognitionEvent` interface defines the nested `personnel` shape with `photo_url: string | null` (line 32). However, `AlertController::index()` eager-loads `personnel:id,name,custom_id,person_type,photo_path`, which selects the raw `photo_path` column — not the computed `photo_url` accessor. When the serialized event is sent to Inertia, the `personnel` relation will contain `photo_path` (possibly hidden by the `#[Hidden]` attribute on the `Personnel` model) and `photo_url` (the accessor). The TypeScript type omits `photo_path` entirely and only declares `photo_url`, so any code accessing `personnel.photo_url` on a recognition event from the server is accessing an accessor value appended via `$appends` on the `Personnel` model — which is correct, but the type should reflect this explicitly and also confirm `photo_path` is not accidentally exposed.

**Immediate risk:** If `Personnel` does not include `photo_url` in `$appends` (it does, based on `Personnel.php`), the TypeScript type would be wrong and the image would silently fail to load. Verify that `Personnel::$appends` includes `photo_url` and keep the type in sync.

**Fix:** Add a comment in the type to document the server-side accessor dependency, and ensure the `Personnel` model's `$appends` always includes `photo_url`:
```typescript
personnel?: {
    id: number;
    name: string;
    custom_id: string;
    person_type: number;
    /** Computed accessor from Personnel model — requires photo_url in $appends */
    photo_url: string | null;
} | null;
```
On the PHP side, confirm `Personnel` model has `protected $appends = ['photo_url'];` (not just the attribute method).

---

### WR-03: `mapPayloadToEvent` duplicated verbatim across two pages

**File:** `resources/js/pages/alerts/Index.vue:87-128` and `resources/js/pages/Dashboard.vue:116-154`

**Issue:** The `mapPayloadToEvent` function is copy-pasted identically in both `alerts/Index.vue` and `Dashboard.vue`. Any fix to the mapping logic (e.g., adding a new field to `RecognitionAlertPayload`) must be applied in two places. This is a maintenance hazard — they will inevitably diverge. This is classified as a Warning rather than Info because both files share the same real-time alerting path; a divergence would cause inconsistent UI state.

**Fix:** Extract to a shared composable or utility:
```typescript
// resources/js/composables/usePayloadMapper.ts
import type { RecognitionAlertPayload, RecognitionEvent } from '@/types';

export function mapPayloadToEvent(payload: RecognitionAlertPayload): RecognitionEvent {
    return {
        // ... shared mapping
    };
}
```
Then import in both pages.

---

### WR-04: `setTimeout` in `AlertFeedItem.vue` is not cleared on unmount

**File:** `resources/js/components/AlertFeedItem.vue:62-69` (timer) + `AlertFeedItem.vue` (no cancel mechanism for ad-hoc timeouts)

**Issue:** The `nowTimer` interval is correctly cleared in `onUnmounted`. However, the `highlightedId` timeout in `alerts/Index.vue` (line 152-154) and `Dashboard.vue` (line 196-199) use a bare `setTimeout(() => { highlightedId.value = null }, 300)` with no cancel handle stored. If the parent component unmounts within 300ms of receiving an alert (e.g., user navigates away), the callback fires on an unmounted component, writing to a stale `ref`. In Vue 3 with Inertia SPA navigation, this is a real path. While Vue 3 generally handles stale ref writes gracefully, it produces a warning in dev mode and can interfere with Inertia's page caching.

**Fix:**
```typescript
// In alerts/Index.vue and Dashboard.vue
let highlightTimer: ReturnType<typeof setTimeout> | null = null;

onUnmounted(() => {
    if (highlightTimer) clearTimeout(highlightTimer);
});

// In the Echo callback:
if (highlightTimer) clearTimeout(highlightTimer);
highlightTimer = setTimeout(() => {
    highlightedId.value = null;
}, 300);
```

---

## Info

### IN-01: `.env.example` missing `VITE_MAPBOX_ACCESS_TOKEN`

**File:** `.env.example:94-97`

**Issue:** `MAPBOX_ACCESS_TOKEN`, `MAPBOX_DARK_STYLE`, and `MAPBOX_LIGHT_STYLE` are present in `.env.example` (lines 94-97) as server-side values. However, the Dashboard passes Mapbox config via Inertia props (server-rendered), not via `import.meta.env`. This is the correct and secure approach, since the token stays server-side. No action needed on the env side, but `global.d.ts` still declares `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` (lines 12-13) as required `readonly` entries in `ImportMetaEnv`. Since Pusher support is commented out in `.env.example`, accessing these will yield `undefined` in practice, but TypeScript treats them as `string`. This is a minor type lie.

**Fix:** Mark optional Pusher keys as optional in `global.d.ts`:
```typescript
readonly VITE_PUSHER_APP_KEY?: string;
readonly VITE_PUSHER_APP_CLUSTER?: string;
```

---

### IN-02: `acknowledgedAt` non-null assertion in templates

**File:** `resources/js/components/AlertDetailModal.vue:201`, `resources/js/components/AlertFeedItem.vue:167`

**Issue:** Both components use `event.acknowledged_at!` (non-null assertion) inside a `v-if="isAcknowledged"` guard that checks `!!props.event.acknowledged_at`. The assertion is safe given the guard, but the pattern relies on the programmer's knowledge that the `!` is safe. A type narrowing helper or a scoped variable would make this explicit and safer under future refactors.

**Fix:** Use a local variable inside the template block:
```vue
<template v-if="isAcknowledged">
    <!-- acknowledged_at is guaranteed non-null here -->
    <p>Acknowledged at {{ formatAbsoluteTime(event.acknowledged_at as string) }}</p>
</template>
```
Or, preferably, create a computed `acknowledgedAt` that returns `string | null` and check it directly.

---

### IN-03: Magic number `person_type === 1` repeated without named constant

**File:** `resources/js/components/AlertDetailModal.vue:49,57`

**Issue:** The block-list check uses the raw literal `1` for `person_type` in two places in `AlertDetailModal.vue`, matching the PHP `AlertSeverity::fromEvent()` logic. If the person-type integer encoding changes, this must be updated in multiple places across PHP and TypeScript.

**Fix:** Define a shared constant in the TypeScript types:
```typescript
// resources/js/types/recognition.ts
export const PERSON_TYPE_BLOCK = 1;
export const PERSON_TYPE_ALLOW = 0;
```
Then use `PERSON_TYPE_BLOCK` in `AlertDetailModal.vue`.

---

### IN-04: `EventHistoryController` search uses table-qualified column `recognition_events.custom_id` inconsistently

**File:** `app/Http/Controllers/EventHistoryController.php:36`

**Issue:** The search query qualifies `recognition_events.custom_id` with the table name (line 36) but `name_from_camera` is unqualified (line 35). Since the query is on `RecognitionEvent::query()` with no explicit join, both columns resolve to the same table and the qualification is unnecessary. The inconsistency is not a bug but adds confusion and could mask an actual ambiguous column error if a join is added later.

**Fix:**
```php
$q->where('name_from_camera', 'like', "%{$search}%")
    ->orWhere('custom_id', 'like', "%{$search}%")
    ->orWhereHas('personnel', function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('custom_id', 'like', "%{$search}%");
    });
```

---

### IN-05: `CameraCrudTest` — no test for unauthenticated access to write routes

**File:** `tests/Feature/Camera/CameraCrudTest.php:256-259`

**Issue:** The authentication test on line 256 only checks the `cameras.index` GET route. The `store`, `update`, and `destroy` routes are not tested for unauthenticated access. While the authentication middleware presumably protects all camera routes via a route group, the test coverage gap means a misconfigured route (e.g., a future route added outside the auth group) would go undetected.

**Fix:** Expand the authentication test to cover all write routes:
```php
test('requires authentication for all camera write routes', function () {
    $camera = Camera::factory()->create();

    $this->post(route('cameras.store'), [])->assertRedirect(route('login'));
    $this->put(route('cameras.update', $camera), [])->assertRedirect(route('login'));
    $this->delete(route('cameras.destroy', $camera))->assertRedirect(route('login'));
});
```

---

_Reviewed: 2026-04-14T00:00:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

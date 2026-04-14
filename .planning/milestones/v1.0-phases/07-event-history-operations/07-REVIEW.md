---
phase: 07-event-history-operations
reviewed: 2026-04-11T00:00:00Z
depth: standard
files_reviewed: 15
files_reviewed_list:
  - app/Console/Commands/CleanupRetentionImagesCommand.php
  - app/Http/Controllers/EventHistoryController.php
  - database/migrations/2026_04_11_071900_add_captured_at_index_to_recognition_events_table.php
  - resources/js/components/AppHeader.vue
  - resources/js/components/AppSidebar.vue
  - resources/js/components/EventHistoryFilters.vue
  - resources/js/components/EventHistoryPagination.vue
  - resources/js/components/EventHistoryTable.vue
  - resources/js/components/ui/pagination/index.ts
  - resources/js/components/ui/pagination/Pagination.vue
  - resources/js/pages/events/Index.vue
  - routes/console.php
  - routes/web.php
  - tests/Feature/EventHistory/EventHistoryControllerTest.php
  - tests/Feature/Operations/CleanupRetentionImagesTest.php
findings:
  critical: 0
  warning: 3
  info: 3
  total: 6
status: issues_found
---

# Phase 07: Code Review Report

**Reviewed:** 2026-04-11T00:00:00Z
**Depth:** standard
**Files Reviewed:** 15
**Status:** issues_found

## Summary

This phase delivers the Event History page (server-side filtered/sorted/paginated table), the `fras:cleanup-retention-images` scheduled command, a `captured_at` index migration, and their associated tests. The implementation is solid overall — the controller is clean, filtering is parameterized (no SQL injection risk), the command uses `chunkById` correctly, and the test suite has good coverage of filtering/sorting/pagination edge cases.

Three warnings were found: a column-nullification that runs even when the file does not exist on disk (silent data loss in partial states), a `$count` variable that counts events processed rather than files actually deleted (misleading log output), and a potential `null` dereference when calling `event.similarity.toFixed(1)` for events where similarity could be null in edge cases. Three info-level findings cover an unused import in the page component, a missing `photo_path` field in the `RecognitionEvent` TypeScript type vs the server eager-load, and leftover starter-kit placeholder links in navigation components.

---

## Warnings

### WR-01: Column nullified even when file never existed on disk

**File:** `app/Console/Commands/CleanupRetentionImagesCommand.php:46-53`

**Issue:** The `update([$column => null])` call runs unconditionally on every event that passes the `whereNotNull($column)` filter, regardless of whether the file was found and deleted on disk. If a file has already been deleted externally (e.g., manual cleanup, disk failure) the column is still nullified — which is intentional — but if `Storage::disk('local')->exists($path)` returns `false`, the file is silently skipped yet the column is still wiped. This is actually the correct behavior for this use-case (idempotent cleanup), but the `$count++` on line 54 runs unconditionally too, making the log message misleading (see WR-02). More critically: if the disk `exists()` call itself throws (e.g., permission error), the exception propagates mid-chunk and the remaining events in that chunk are skipped without their paths being nullified, leaving partial state.

**Fix:** Wrap the inner body in a try/catch to ensure partial disk errors do not silently abort the chunk, and log individual failures:

```php
foreach ($events as $event) {
    $path = $event->{$column};

    try {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    } catch (\Throwable $e) {
        Log::warning("Retention cleanup: could not delete {$column} for event {$event->id}: {$e->getMessage()}");
    }

    $event->update([$column => null]);
    $count++;
}
```

---

### WR-02: `$count` counts events processed, not files deleted — log message is misleading

**File:** `app/Console/Commands/CleanupRetentionImagesCommand.php:39-54`

**Issue:** `$count++` increments for every event whose image path column is cleared, including events whose file did not exist on disk (the `if ($path && Storage::disk('local')->exists($path))` branch may be skipped). The log message on line 28 says "deleted N scene images" but `$count` may include events where the file was already absent. The test on line 113 of `CleanupRetentionImagesTest.php` asserts `'Retention cleanup: deleted'` which passes regardless of whether the count is accurate.

**Fix:** Track actual file deletions separately from column nullifications:

```php
$deletedFiles = 0;

foreach ($events as $event) {
    $path = $event->{$column};

    if ($path && Storage::disk('local')->exists($path)) {
        Storage::disk('local')->delete($path);
        $deletedFiles++;
    }

    $event->update([$column => null]);
    $count++;
}
```

Then update the log message to reflect both counts:

```php
$message = "Retention cleanup: nullified {$sceneCount} scene paths ({$sceneDeleted} files deleted), "
         . "{$faceCount} face paths ({$faceDeleted} files deleted)";
```

Alternatively, simplify to only count actual file deletions if that is the more useful metric for operators.

---

### WR-03: `event.similarity.toFixed(1)` called without null guard

**File:** `resources/js/components/EventHistoryTable.vue:172`

**Issue:** `event.similarity` is typed as `number` in `RecognitionEvent` (non-nullable), but the controller eager-loads events directly from the database where `similarity` could be `0` or a valid float. This is safe for normal records. However, the `RecognitionEvent` type declares `similarity: number` and the column is expected to always be set, so this is currently safe. The risk is if a future migration makes the column nullable — `toFixed` would throw a runtime error at that point. More immediately: if `event.similarity` is `0`, the output is `"0.0%"` which is correct. No immediate crash, but the pattern is fragile.

**Fix:** Add a defensive null check to make the template resilient to future schema changes:

```vue
{{ event.similarity != null ? event.similarity.toFixed(1) : '—' }}%
```

---

## Info

### IN-01: `useHttp` import used only for acknowledge/dismiss — `usePage` prop mutation is unsafe

**File:** `resources/js/pages/events/Index.vue:108-143`

**Issue:** The `handleAcknowledge` and `handleDismiss` handlers mutate `props.events.data` elements directly (lines 109-111, 130-131). Vue 3 props are not supposed to be mutated directly — this bypasses the reactivity system and modifies the Inertia-managed props object in place. While this works in practice because Inertia replaces the entire props object on the next visit, it is an anti-pattern that can cause subtle bugs if Inertia's prop merging behavior changes or if the same event object is referenced elsewhere.

**Fix:** Use a local reactive copy of the events data, or use `router.reload({ only: ['events'] })` after the HTTP action completes to let Inertia refresh the data from the server. The local copy approach:

```ts
const localEvents = ref([...props.events.data]);

// In handleAcknowledge onSuccess:
const found = localEvents.value.find((e) => e.id === event.id);
if (found) {
    found.acknowledged_at = new Date().toISOString();
}
```

---

### IN-02: Leftover starter-kit placeholder links in navigation

**File:** `resources/js/components/AppHeader.vue:93-101`
**File:** `resources/js/components/AppSidebar.vue:61-71`

**Issue:** Both navigation components contain `rightNavItems` / `footerNavItems` pointing to `https://github.com/laravel/vue-starter-kit` and `https://laravel.com/docs/starter-kits#vue`. These are starter-kit defaults unrelated to FRAS and will appear in the production UI as broken/irrelevant links for operators.

**Fix:** Remove the `rightNavItems` / `footerNavItems` arrays and their rendered `<template>` / `<a>` blocks, or replace them with FRAS-relevant links (e.g., system documentation, admin panel).

---

### IN-03: `photo_path` included in eager-load select but absent from `RecognitionEvent` TypeScript type

**File:** `app/Http/Controllers/EventHistoryController.php:28`
**File:** `resources/js/types/recognition.ts:26-33`

**Issue:** The controller eager-loads `personnel:id,name,custom_id,person_type,photo_path` (line 28), but the `RecognitionEvent` TypeScript `personnel` sub-type only declares `photo_url: string | null` (which is the accessor URL), not `photo_path`. The field is never accessed in `EventHistoryTable.vue` so this causes no runtime error, but the type definition is inconsistent with what the server actually sends. If a developer later accesses `event.personnel?.photo_path` in the frontend they will get a TypeScript error despite the field being present at runtime.

**Fix:** Either add `photo_path` to the TypeScript personnel sub-type to match the eager-load, or remove `photo_path` from the eager-load select if it is not needed by the frontend (the table uses `faceImage` route for avatars, not `photo_path`):

```ts
// Option A: add to type
personnel?: {
    id: number;
    name: string;
    custom_id: string;
    person_type: number;
    photo_path: string | null;  // raw path, server-side
    photo_url: string | null;   // accessor URL, frontend-usable
} | null;

// Option B: remove from eager-load (simpler, no frontend use)
->with(['camera:id,name', 'personnel:id,name,custom_id,person_type'])
```

---

_Reviewed: 2026-04-11T00:00:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

---
phase: 07-event-history-operations
verified: 2026-04-11T07:43:20Z
status: human_needed
score: 4/4 roadmap success criteria verified
overrides_applied: 0
human_verification:
  - test: "Navigate to Event History page via sidebar or header navigation"
    expected: "Page loads at /events showing data table with recognition events from today, Event History link appears after Live Alerts in navigation with History icon"
    why_human: "Visual layout and navigation positioning cannot be verified programmatically"
  - test: "Type a name in the Search person... input and wait 300ms"
    expected: "URL updates and table re-renders after debounce delay (not on each keystroke)"
    why_human: "Browser timing behavior cannot be verified without running the app"
  - test: "Click a table row"
    expected: "AlertDetailModal opens with that event's face crop, scene image, metadata"
    why_human: "Modal open/close behavior and correct event binding require visual inspection"
  - test: "Click a sortable column header (Severity, Similarity, Time)"
    expected: "Sort indicator chevron appears on the active column, table reorders, URL updates with sort params"
    why_human: "Visual sort indicator state and table reorder are not testable without a browser"
  - test: "Set filters with no matching events"
    expected: "No matching events empty state appears with appropriate message distinguishing no-data vs no-match-filters"
    why_human: "Empty state branch requires visual confirmation with real filter inputs"
  - test: "Replay event appears in the table"
    expected: "Row has muted opacity (opacity-60) and a Replay badge appears next to the severity badge"
    why_human: "Visual opacity and badge placement cannot be verified programmatically"
---

# Phase 7: Event History & Operations Verification Report

**Phase Goal:** Operators can search and filter past recognition events, and the system automatically manages storage growth through scheduled retention cleanup
**Verified:** 2026-04-11T07:43:20Z
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths (Roadmap Success Criteria)

| # | Truth | Status | Evidence |
|---|-------|--------|---------|
| 1 | Event history page shows a searchable log of all recognition events with face crop thumbnail, person name, camera, severity, similarity, and timestamp | VERIFIED | `EventHistoryTable.vue` renders Avatar (face crop), personName/custom_id, camera?.name, SeverityBadge, `event.similarity.toFixed(1)%`, `formatTimestamp(event.captured_at)`. 19 controller tests passing including pagination, filter, and sort assertions. |
| 2 | Filters for date range, camera, person, and severity level narrow results correctly | VERIFIED | `EventHistoryController.php` applies `whereDate` for date range (default today), `where('camera_id', $id)`, `where('severity', $sev)`, and grouped `where` with `orWhereHas('personnel')` for person search across 4 sources. Tests cover each filter case individually. `EventHistoryFilters.vue` wired to emit `filter-change` triggering `router.get`. |
| 3 | Scheduled job deletes scene images older than 30 days and face crops older than 90 days while preserving recognition_events rows | VERIFIED | `CleanupRetentionImagesCommand.php` uses `chunkById(200)`, `Storage::disk('local')->delete($path)`, `$event->update([$column => null])`. Schedule entry: `dailyAt('02:00')->withoutOverlapping()`. 11 tests pass including preservation check. `php artisan fras:cleanup-retention-images` runs without error, outputs "Retention cleanup: deleted 0 scene images, 0 face crops". |
| 4 | Retention windows are configurable in config/hds.php and changes take effect on the next scheduled run | VERIFIED | `config/hds.php` has `retention.scene_images_days` (default 30) and `retention.face_crops_days` (default 90) backed by env vars `FRAS_SCENE_RETENTION_DAYS` and `FRAS_FACE_RETENTION_DAYS`. Command reads via `config('hds.retention.scene_images_days', 30)`. Test with `config(['hds.retention.scene_images_days' => 5])` confirms custom values take effect. |

**Score:** 4/4 roadmap success criteria verified

### Plan Must-Haves (Plan 01 — Backend)

| # | Truth | Status | Evidence |
|---|-------|--------|---------|
| 1 | Event history page loads at /events with paginated recognition events | VERIFIED | Route `events.index` → `EventHistoryController@index`, `paginate(25)->withQueryString()`, Inertia renders `events/Index` |
| 2 | Filters narrow results by date range, camera, person name/custom_id, and severity | VERIFIED | Controller `when()` clauses for all four filters, 4-source search via grouped where |
| 3 | Default date range is today when no date params provided | VERIFIED | `$request->input('date_from', now()->format('Y-m-d'))`, test "default date range is today" passes |
| 4 | Sort parameter is whitelist-validated to prevent SQL injection | VERIFIED | `$allowedSorts = ['captured_at', 'similarity', 'severity']` with `in_array(..., true)` strict check, fallback to `captured_at` |
| 5 | Retention command deletes scene images older than 30 days and face crops older than 90 days | VERIFIED | Tests confirm 31-day-old scenes and 91-day-old faces are deleted; 5-day-old events are preserved |
| 6 | Retention command nullifies database path columns after deleting files | VERIFIED | `$event->update([$column => null])` in chunkById callback; column null assertions pass |
| 7 | Retention command logs summary of deleted counts | VERIFIED | `Log::info($message)` + `$this->info($message)` with "Retention cleanup: deleted X scene images, Y face crops" |
| 8 | Retention command is scheduled daily at 02:00 | VERIFIED | `routes/console.php:13`: `Schedule::command('fras:cleanup-retention-images')->dailyAt('02:00')->withoutOverlapping()` |

**Score:** 8/8 plan-01 must-haves verified

### Plan Must-Haves (Plan 02 — Frontend)

| # | Truth | Status | Evidence |
|---|-------|--------|---------|
| 1 | Event history page renders at /events with data table of recognition events | VERIFIED | `events/Index.vue` with `EventHistoryTable`, `EventHistoryFilters`, `EventHistoryPagination`. Component receives `events` from real DB query via Inertia. |
| 2 | Filter bar shows date range inputs, camera dropdown, person search input, and severity pills | VERIFIED | `EventHistoryFilters.vue` renders two `type="date"` inputs, `Select` with cameras, `Input` with placeholder "Search person...", severity `Button` group |
| 3 | Changing filters triggers Inertia visit that updates URL query parameters and table results | VERIFIED | `handleFilterChange` calls `router.get(index.url(), {...newFilters}, { preserveState: true, replace: true })` |
| 4 | Person search is debounced at 300ms | VERIFIED (code) | `watchDebounced(search, () => emitFilters(), { debounce: 300 })` present. Actual timing behavior needs human verification. |
| 5 | Table columns show face crop thumbnail, person name (with custom_id below), camera name, severity badge, similarity percentage, and timestamp | VERIFIED | All 6 columns implemented: Avatar (face crop), personName+custom_id, camera?.name, SeverityBadge, `toFixed(1)%` in font-mono, `formatTimestamp` |
| 6 | Clicking a table row opens AlertDetailModal with that event's data | VERIFIED (code) | `@click="emit('select', event)"` on TableRow, parent `handleSelect` sets `selectedEvent.value` and `modalOpen.value = true`, `AlertDetailModal v-model:open="modalOpen" :event="selectedEvent"` present. Visual confirmation human-needed. |
| 7 | Replay events show with opacity-60 and a Replay badge next to SeverityBadge | VERIFIED (code) | `:class="{ 'opacity-60': !event.is_real_time }"` on TableRow, `<span v-if="!event.is_real_time" class="...">Replay</span>` after SeverityBadge. Visual confirmation human-needed. |
| 8 | Sortable columns (Severity, Similarity, Time) show sort indicators and trigger Inertia visits | VERIFIED (code) | `toggleSort()` emits to parent `handleSort` → `router.get`. ChevronUp/ChevronDown conditionally shown based on `sort === column && direction`. Visual confirmation human-needed. |
| 9 | Pagination shows numbered pages with Showing X-Y of Z events label | VERIFIED | `EventHistoryPagination.vue`: `<p>Showing {{ from }}-{{ to }} of {{ total }} events</p>` + shadcn `Pagination` with numbered items |
| 10 | Empty state shows appropriate message for no data vs no matching filters | VERIFIED (code) | `v-if="events.data.length === 0"` shows either "No matching events" or "No recognition events yet" based on `hasActiveFilters` computed |
| 11 | Event History appears in sidebar and header navigation after Live Alerts | VERIFIED | `AppSidebar.vue` and `AppHeader.vue` both contain `{ title: 'Event History', href: eventsIndex(), icon: History }`. Visual position after Live Alerts needs human confirmation. |

**Score:** 11/11 plan-02 must-haves verified (6 require additional human visual confirmation)

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `app/Http/Controllers/EventHistoryController.php` | Event history index with filtered pagination | VERIFIED | 61 lines, real Eloquent query with all filter types, whitelist sort, paginate(25) |
| `app/Console/Commands/CleanupRetentionImagesCommand.php` | Retention cleanup artisan command | VERIFIED | `fras:cleanup-retention-images`, chunkById(200), Storage::delete, path nullify |
| `routes/web.php` | events.index route | VERIFIED | `Route::get('events', [EventHistoryController::class, 'index'])->name('events.index')` inside auth+verified group |
| `routes/console.php` | Retention job schedule | VERIFIED | `dailyAt('02:00')->withoutOverlapping()` |
| `database/migrations/2026_04_11_071900_add_captured_at_index_to_recognition_events_table.php` | captured_at index migration | VERIFIED | File exists |
| `tests/Feature/EventHistory/EventHistoryControllerTest.php` | Feature tests for event history | VERIFIED | 19 tests, all passing (187 assertions) |
| `tests/Feature/Operations/CleanupRetentionImagesTest.php` | Feature tests for retention cleanup | VERIFIED | 11 tests, all passing (26 assertions) |
| `resources/js/pages/events/Index.vue` | Event history page component | VERIFIED | Full implementation with all handlers, not a stub |
| `resources/js/components/EventHistoryFilters.vue` | Horizontal filter bar | VERIFIED | All filter controls present, watchDebounced for search |
| `resources/js/components/EventHistoryTable.vue` | Data table with sortable headers | VERIFIED | All columns, sort indicators, row click, opacity-60 for replay |
| `resources/js/components/EventHistoryPagination.vue` | Pagination with Inertia integration | VERIFIED | "Showing X-Y of Z" label, shadcn Pagination components |
| `resources/js/components/AppSidebar.vue` | Sidebar with Event History nav item | VERIFIED | `Event History` with `History` icon and `eventsIndex()` href |
| `resources/js/components/AppHeader.vue` | Header with Event History nav item | VERIFIED | `Event History` with `History` icon and `eventsIndex()` href |
| `resources/js/routes/events/index.ts` | Wayfinder route for events.index | VERIFIED | Auto-generated, exports `index` function pointing to `/events` |
| `resources/js/components/ui/table/index.ts` | shadcn table components | VERIFIED | 10 component files present |
| `resources/js/components/ui/pagination/index.ts` | shadcn pagination components | VERIFIED | 8 component files present |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `routes/web.php` | `EventHistoryController::index` | `Route::get('events', ...)` | WIRED | `php artisan route:list --name=events` confirms `events.index → EventHistoryController@index` |
| `routes/console.php` | `CleanupRetentionImagesCommand` | `Schedule::command` | WIRED | `Schedule::command('fras:cleanup-retention-images')->dailyAt('02:00')->withoutOverlapping()` |
| `EventHistoryController` | `RecognitionEvent` model | `RecognitionEvent::query()` with paginate | WIRED | Real DB query at line 27, paginate(25) at line 44, Inertia::render at line 47 |
| `resources/js/pages/events/Index.vue` | `EventHistoryController` | Wayfinder `@/routes/events` | WIRED | `import { index } from '@/routes/events'`, used in `handleFilterChange`, `handleSort`, `handlePageChange` |
| `resources/js/components/EventHistoryFilters.vue` | `resources/js/pages/events/Index.vue` | `emit('filter-change', filters)` | WIRED | `@filter-change="handleFilterChange"` in Index.vue |
| `resources/js/components/EventHistoryTable.vue` | `AlertDetailModal` | `emit('select', event)` | WIRED | `@select="handleSelect"` in Index.vue, `handleSelect` sets `selectedEvent` + `modalOpen = true` |
| `resources/js/components/AppSidebar.vue` | `events.index` route | Wayfinder import | WIRED | `import { index as eventsIndex } from '@/routes/events'`, used as `href: eventsIndex()` |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
|----------|--------------|--------|--------------------|--------|
| `events/Index.vue` | `props.events` (PaginatedEvents) | `EventHistoryController::index` → `RecognitionEvent::query()->paginate(25)` | Yes — Eloquent query against recognition_events table with no static fallback | FLOWING |
| `events/Index.vue` | `props.cameras` | `Camera::orderBy('name')->get(['id', 'name'])` | Yes — real DB query | FLOWING |
| `events/Index.vue` | `props.filters` | Request params with today defaults | Yes — request state with sensible defaults | FLOWING |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| Route registered and reachable | `php artisan route:list --name=events` | `events.index › EventHistoryController@index` | PASS |
| Retention command runs cleanly | `php artisan fras:cleanup-retention-images` | "Retention cleanup: deleted 0 scene images, 0 face crops" — exit 0 | PASS |
| EventHistoryController tests | `php artisan test --compact --filter=EventHistoryControllerTest` | 19 passed (187 assertions), 0.64s | PASS |
| CleanupRetentionImages tests | `php artisan test --compact --filter=CleanupRetentionImagesTest` | 11 passed (26 assertions), 0.32s | PASS |
| ESLint check | `npm run lint:check` | No errors | PASS |
| Prettier check | `npm run format:check` | All matched files use Prettier code style | PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|---------|
| HIST-01 | 07-01, 07-02 | Event history page shows a searchable, filterable log of all recognition events | SATISFIED | `events/Index.vue` page at `/events`, 4-source search, all filter types, 19 controller tests |
| HIST-02 | 07-01, 07-02 | Filters include date range, camera, person, severity level | SATISFIED | `EventHistoryFilters.vue` + controller `when()` clauses for all 4 filters |
| HIST-03 | 07-02 | Each event row shows face crop thumbnail, person name, camera, severity, similarity, and timestamp | SATISFIED | `EventHistoryTable.vue` renders all 6 required data points per row |
| OPS-01 | 07-01 | Scheduled job deletes scene images older than 30 days while keeping recognition_events row and face crop | SATISFIED | `cleanupImages('scene_image_path', 30)`, event row preserved (test), face_image_path separate column |
| OPS-02 | 07-01 | Scheduled job deletes face crops older than 90 days while keeping recognition_events row | SATISFIED | `cleanupImages('face_image_path', 90)`, event row preserved (test) |
| OPS-03 | 07-01 | Retention windows are configurable in config/hds.php | SATISFIED | `config/hds.php` retention array with env-backed defaults, command reads config values |

### Anti-Patterns Found

None found. Two matches for "placeholder" in `EventHistoryFilters.vue` are UI attribute values (SelectValue placeholder text, Input placeholder text) — not stub indicators.

### Human Verification Required

### 1. Navigation Placement

**Test:** Log into the application and inspect the sidebar and header navigation.
**Expected:** "Event History" link appears immediately after "Live Alerts" with a History icon, and navigates to `/events`.
**Why human:** Visual ordering in rendered DOM cannot be confirmed by static analysis alone.

### 2. Search Debounce Behavior

**Test:** Navigate to `/events`, type "test" in the Search person... input field character by character.
**Expected:** No Inertia visit fires until 300ms after the last keystroke. URL updates only after the debounce window expires.
**Why human:** Browser timing behavior for debounce cannot be verified without an interactive browser session.

### 3. Row Click Opens AlertDetailModal

**Test:** Navigate to `/events` with data present, click a table row.
**Expected:** AlertDetailModal opens showing the clicked event's face crop thumbnail, scene image, person name, severity, camera, and other metadata. The modal `open` prop is true.
**Why human:** Modal interaction requires visual confirmation that `selectedEvent` and `modalOpen` wire correctly to the modal props.

### 4. Sort Indicator Visual State

**Test:** On the event history page, click the "Severity" column header, then click it again to reverse direction.
**Expected:** On first click, a ChevronUp or ChevronDown appears next to "Severity"; the table reorders; the URL gets `sort=severity&direction=asc`. On second click, direction reverses and chevron flips.
**Why human:** Visual chevron rendering and table reorder require an interactive browser session.

### 5. Replay Event Visual Treatment

**Test:** Ensure a replay event (is_real_time=false) is present in the visible date range, then view the table.
**Expected:** The row appears with reduced opacity (opacity-60) and shows a "Replay" text badge next to the severity badge in the Severity column.
**Why human:** CSS opacity and badge rendering require visual browser inspection.

### 6. Empty State Differentiation

**Test:** On the event history page, apply a severity filter that yields no results (e.g., select a camera with zero events). Then clear all filters.
**Expected:** With active filters and no results: "No matching events" + "Try adjusting your filters..." message. With no filters and no events: "No recognition events yet" + "Events will appear here..." message.
**Why human:** Distinguishing between the two empty state branches and verifying the correct copy requires visual inspection with specific data conditions.

### Gaps Summary

No programmatic gaps found. All artifacts are present and substantive, all key links are wired, all 30 feature tests pass (19 controller + 11 retention), and all 4 roadmap success criteria are verified against real implementation.

Human verification is required for 6 visual/interactive behaviors that cannot be confirmed without a running browser session.

---

_Verified: 2026-04-11T07:43:20Z_
_Verifier: Claude (gsd-verifier)_

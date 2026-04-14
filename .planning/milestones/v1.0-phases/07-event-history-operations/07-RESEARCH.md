# Phase 7: Event History & Operations - Research

**Researched:** 2026-04-11
**Domain:** Server-side filtered data tables (Inertia + Laravel paginator) + scheduled artisan commands for image retention cleanup
**Confidence:** HIGH

## Summary

Phase 7 has two distinct workstreams: (1) a searchable/filterable event history data table page with server-side pagination, and (2) a scheduled artisan command that deletes expired image files per configurable retention windows. Both workstreams leverage heavily established patterns already in the codebase.

The event history page follows the existing Inertia controller pattern (seen in `AlertController`, `CameraController`, `PersonnelController`) but adds server-side pagination via Laravel's `->paginate()` and query parameter-driven filtering. The retention cleanup command follows the existing artisan command pattern (`CheckOfflineCamerasCommand`, `CheckEnrollmentTimeoutsCommand`) and uses `chunkById()` for memory-efficient batch deletion. All required models, relationships, factories, enums, and Vue components already exist -- this phase composes them.

**Primary recommendation:** Build the EventHistoryController with query-parameter-based Eloquent scopes, pass the paginator directly to Inertia (it serializes correctly), and install shadcn-vue `table` + `pagination` components for the frontend. The retention command uses `chunkById()` to update records in batches, nullifying image paths and deleting files from the date-partitioned storage directories.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Data table with sortable columns -- face crop thumbnail, person name, camera, severity badge, similarity %, timestamp. Standard table layout for scanning large datasets. Pagination at 25-50 rows per page.
- **D-02:** Standard numbered page pagination with URL query parameters. Bookmarkable, works naturally with server-side filtering.
- **D-03:** Clicking a table row opens the existing AlertDetailModal from Phase 5 (face crop, scene image with bounding box overlay, full metadata, ack/dismiss buttons). Consistent experience across alert feed and history.
- **D-04:** Manual replay events (PushType=2) appear in the history table with a subtle "replay" badge or muted styling to distinguish them from real-time events. They are visible in history but clearly marked.
- **D-05:** Horizontal filter bar above the table -- inline row of controls: date range picker, camera dropdown, person search input, severity filter pills (All | Critical | Warning | Info). Compact, always visible, consistent with alert feed filter pill pattern.
- **D-06:** Server-side filtering via Inertia visits -- filters update URL query parameters and trigger Inertia page visits. Scales with large datasets, bookmarkable filter state.
- **D-07:** Default date range is "Today" when loading the history page. Most common use case for operators checking recent activity.
- **D-08:** Person search field matches against both person name and custom ID. Single search input, operators may know either identifier.
- **D-09:** Daily scheduled job at 2:00 AM -- runs once during low-activity hours. Deletes scene images older than `config('hds.retention.scene_images_days')` (default 30) and face crops older than `config('hds.retention.face_crops_days')` (default 90). Recognition_events rows are preserved indefinitely.
- **D-10:** After deleting an image file, set the corresponding path column (face_image_path / scene_image_path) to null. The existing image URL accessors already return null for null paths -- UI gracefully shows placeholder. Clean and explicit.
- **D-11:** Log summary only -- job logs a summary line to Laravel log: "Retention cleanup: deleted X scene images, Y face crops". No operator notification. Reviewable via Pail or log files.

### Claude's Discretion
- Data table component choice (build with shadcn-vue Table or custom component)
- Date range picker component implementation
- Camera dropdown population strategy (eager load vs prop)
- Pagination count per page (25 vs 50)
- Table column sorting implementation
- Retention job chunking strategy for large deletion batches
- Image file deletion approach (batch delete vs individual)
- Empty state design for no matching events

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| HIST-01 | Event history page shows a searchable, filterable log of all recognition events | EventHistoryController with Eloquent query scopes, `->paginate(25)`, Inertia render to `events/Index.vue` |
| HIST-02 | Filters include date range, camera, person, severity level | Query parameters (`date_from`, `date_to`, `camera_id`, `search`, `severity`) applied as conditional `when()` clauses on the Eloquent query |
| HIST-03 | Each event row shows face crop thumbnail, person name, camera, severity, similarity, and timestamp | Eager load `camera:id,name` and `personnel:id,name,custom_id,person_type`, reuse SeverityBadge, Avatar, existing RecognitionEvent type |
| OPS-01 | Scheduled job deletes scene images older than 30 days while keeping recognition_events row and face crop | `CleanupRetentionImagesCommand` with `chunkById()`, query `scene_image_path IS NOT NULL` and `captured_at < now()->subDays(config)`, delete file then null the column |
| OPS-02 | Scheduled job deletes face crops older than 90 days while keeping recognition_events row | Same command, second pass for face crops with different retention window |
| OPS-03 | Retention windows are configurable in config/hds.php | Already implemented in `config/hds.php`: `retention.scene_images_days` (30) and `retention.face_crops_days` (90) |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Paginator | Built-in (v13) | Server-side pagination with URL parameters | Standard Laravel approach, serializes to JSON with `current_page`, `last_page`, `total`, `links` structure that Inertia passes directly to Vue [VERIFIED: artisan tinker] |
| shadcn-vue Table | latest (via `npx shadcn-vue@latest add table`) | Data table structure (Table, TableHeader, TableBody, TableRow, TableHead, TableCell) | Already the project's UI component system, consistent with existing components [VERIFIED: components.json] |
| shadcn-vue Pagination | latest (via `npx shadcn-vue@latest add pagination`) | Page navigation controls | Consistent with shadcn-vue component system, provides accessible pagination out of the box [VERIFIED: components.json config] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Existing SeverityBadge | n/a (app component) | Severity column cell in table | Already exists at `resources/js/components/SeverityBadge.vue` [VERIFIED: codebase] |
| Existing AlertDetailModal | n/a (app component) | Row click detail view | Already exists at `resources/js/components/AlertDetailModal.vue` [VERIFIED: codebase] |
| Existing Avatar/AvatarImage/AvatarFallback | shadcn-vue | Face crop thumbnail | Already installed [VERIFIED: components/ui/avatar] |
| Existing Select | shadcn-vue | Camera dropdown filter | Already installed [VERIFIED: components/ui/select] |
| Existing Input | shadcn-vue | Person search input | Already installed [VERIFIED: components/ui/input] |
| Existing Button | shadcn-vue | Severity filter pills | Already installed [VERIFIED: components/ui/button] |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| shadcn-vue Table | TanStack Table (vue-table) | TanStack adds sorting/filtering/pagination primitives but is overkill -- server-side filtering means the table is a pure render component. shadcn-vue Table is simpler and consistent with the project. |
| Native `<input type="date">` | Vue date picker library (v-calendar, etc.) | Native date inputs are simpler, no extra dependency, work well for date-only (not datetime) selection per D-07. Consistent with UI spec. |
| `->paginate()` | `->simplePaginate()` | simplePaginate doesn't provide total count or last page -- needed for "Showing X-Y of Z" display and numbered page buttons per D-02. |

**Installation:**
```bash
npx shadcn-vue@latest add table pagination
```

## Architecture Patterns

### Recommended Project Structure
```
app/
  Http/Controllers/
    EventHistoryController.php        # New: index method with filter logic
  Console/Commands/
    CleanupRetentionImagesCommand.php  # New: daily retention cleanup
routes/
  web.php                             # Add: events.index route
  console.php                         # Add: Schedule retention command at 2 AM
resources/js/
  pages/events/
    Index.vue                         # New: Event history page
  components/
    EventHistoryTable.vue             # New: Data table with sortable headers
    EventHistoryFilters.vue           # New: Horizontal filter bar
    EventHistoryPagination.vue        # New: Pagination wrapper with Inertia visits
```

### Pattern 1: Server-Side Filtered Pagination Controller
**What:** Controller builds an Eloquent query with conditional `when()` clauses based on request query parameters, then calls `->paginate()` and passes the result to Inertia. [VERIFIED: established Laravel pattern, matches existing AlertController eager-loading approach]
**When to use:** Any data table with server-side filtering and pagination.
**Example:**
```php
// Source: Laravel conventions + existing project patterns
public function index(Request $request): Response
{
    $events = RecognitionEvent::query()
        ->with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path'])
        ->when($request->date_from, fn ($q, $from) =>
            $q->whereDate('captured_at', '>=', $from)
        )
        ->when($request->date_to, fn ($q, $to) =>
            $q->whereDate('captured_at', '<=', $to)
        )
        ->when($request->camera_id, fn ($q, $id) =>
            $q->where('camera_id', $id)
        )
        ->when($request->severity, fn ($q, $sev) =>
            $q->where('severity', $sev)
        )
        ->when($request->search, fn ($q, $search) =>
            $q->where(function ($q) use ($search) {
                $q->whereHas('personnel', fn ($p) =>
                    $p->where('name', 'like', "%{$search}%")
                      ->orWhere('custom_id', 'like', "%{$search}%")
                )
                ->orWhere('name_from_camera', 'like', "%{$search}%")
                ->orWhere('custom_id', 'like', "%{$search}%");
            })
        )
        ->orderBy(
            $request->input('sort', 'captured_at'),
            $request->input('direction', 'desc')
        )
        ->paginate(25)
        ->withQueryString();

    return Inertia::render('events/Index', [
        'events' => $events,
        'cameras' => Camera::orderBy('name')->get(['id', 'name']),
        'filters' => $request->only(['date_from', 'date_to', 'camera_id', 'search', 'severity', 'sort', 'direction']),
    ]);
}
```

### Pattern 2: Inertia Filter Visits with URL Sync
**What:** Vue component maintains local filter state and triggers `router.get()` visits when filters change, syncing all filters to URL query parameters. [VERIFIED: standard Inertia pattern, consistent with project's server-side rendering approach]
**When to use:** Any page with server-side filtering.
**Example:**
```typescript
// Source: Inertia v3 conventions + existing alerts/Index.vue filter pattern
import { router } from '@inertiajs/vue3';

function applyFilters(): void {
    router.get(
        index.url(),
        {
            date_from: filters.date_from,
            date_to: filters.date_to,
            camera_id: filters.camera_id || undefined,
            search: filters.search || undefined,
            severity: filters.severity || undefined,
            sort: filters.sort,
            direction: filters.direction,
            page: 1, // Reset page on filter change
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}
```

### Pattern 3: Artisan Command with chunkById for Retention
**What:** Artisan command queries records with non-null image paths older than the retention threshold, processes them in chunks, deletes the file, and nullifies the path column. [VERIFIED: follows CheckOfflineCamerasCommand pattern, uses chunkById per db-performance.md best practice]
**When to use:** Batch-processing large datasets where records are modified during iteration.
**Example:**
```php
// Source: Laravel best practices (db-performance.md) + existing command patterns
RecognitionEvent::query()
    ->whereNotNull('scene_image_path')
    ->where('captured_at', '<', now()->subDays($sceneRetentionDays))
    ->chunkById(200, function ($events) use (&$sceneCount) {
        foreach ($events as $event) {
            if (Storage::disk('local')->exists($event->scene_image_path)) {
                Storage::disk('local')->delete($event->scene_image_path);
            }
            $event->update(['scene_image_path' => null]);
            $sceneCount++;
        }
    });
```

### Pattern 4: Sortable Column Headers
**What:** Table column headers are buttons that trigger Inertia visits with `sort` and `direction` query params. Clicking a sorted column toggles direction; clicking a different column sorts ascending. [ASSUMED]
**When to use:** Data tables with server-side sorting.
**Example:**
```typescript
function toggleSort(column: string): void {
    const newDirection =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';
    emit('sort', { sort: column, direction: newDirection });
}
```

### Anti-Patterns to Avoid
- **Client-side filtering on large datasets:** All filtering MUST be server-side. The history table could have thousands of events. Never load all events and filter in Vue. [VERIFIED: D-06 locked decision]
- **Querying inside loops in retention command:** Use `chunkById()` with batch updates, not `->get()` followed by individual updates. [VERIFIED: db-performance.md rule]
- **Missing `withQueryString()` on paginator:** Without this, pagination links lose filter parameters when navigating pages. [VERIFIED: Laravel paginator behavior]
- **Using `chunk()` instead of `chunkById()` for deletion:** Standard `chunk()` uses OFFSET which shifts when rows are modified during iteration, causing records to be skipped. `chunkById()` uses WHERE id > last_id which is stable. [VERIFIED: db-performance.md rule]
- **Hardcoding retention values:** Use `config('hds.retention.scene_images_days')` and `config('hds.retention.face_crops_days')`. Values already exist in `config/hds.php`. [VERIFIED: config/hds.php]

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Data table HTML/CSS | Custom div-based table | shadcn-vue Table component | Semantic HTML, accessible, consistent with project UI system |
| Pagination UI | Custom page number buttons | shadcn-vue Pagination component | Handles edge cases (ellipsis, disabled states, aria labels) |
| Date inputs | Custom date picker component | Native `<input type="date">` | No dependency, works on all modern browsers, sufficient for date-only (not datetime) selection |
| URL query string management | Manual URLSearchParams | `->paginate()->withQueryString()` + `router.get()` | Laravel paginator appends query strings to page links; Inertia router.get() serializes params |
| Debounced search | Custom debounce implementation | `@vueuse/core` `useDebounceFn` or `watchDebounced` | Already a project dependency (`@vueuse/core` ^12.8.2) [VERIFIED: package.json] |
| Image file batch deletion | Custom directory walking | Eloquent `chunkById()` + `Storage::delete()` | Reliable record-by-record approach that nullifies paths atomically |

**Key insight:** This phase is 90% composition of existing patterns. The model, relationships, factory, enum, image serving routes, detail modal, severity badge, and filter pill pattern all exist. The new work is wiring them into a filterable table page and a scheduled cleanup command.

## Common Pitfalls

### Pitfall 1: Missing `captured_at` Index for Default Sort
**What goes wrong:** The default query sorts by `captured_at DESC` across all events. Without a standalone index on `captured_at`, MySQL performs a filesort on the full table.
**Why it happens:** The existing `(camera_id, captured_at)` composite index only helps when filtering by `camera_id` first. A standalone `captured_at` query cannot use this composite index efficiently.
**How to avoid:** Add a migration with `$table->index('captured_at')` on `recognition_events`. This supports the default sort and date range filtering.
**Warning signs:** Slow page loads on the history page when no camera filter is applied, especially as the table grows.

### Pitfall 2: Person Search N+1 or Slow LIKE on Joined Table
**What goes wrong:** Searching by person name requires either a `whereHas('personnel', ...)` subquery or a join. `whereHas` generates a correlated subquery which can be slow on large tables.
**Why it happens:** The search needs to match against `personnel.name` and `personnel.custom_id` (which lives on a different table) plus the `name_from_camera` and `custom_id` columns on `recognition_events`.
**How to avoid:** Use `whereHas` with `orWhere` for the personnel relationship fields, combined with `orWhere` on `recognition_events.name_from_camera` and `recognition_events.custom_id`. For the dataset size at a single facility (likely <100K events), this is adequate. If performance becomes an issue later, add a full-text index. [ASSUMED: dataset size adequate for LIKE queries]
**Warning signs:** Search response time >500ms with debounced input.

### Pitfall 3: Pagination Links Losing Filter State
**What goes wrong:** Clicking page 2 drops all filter parameters, showing unfiltered results.
**Why it happens:** `->paginate()` generates page URLs with only `?page=N`. Filters are lost.
**How to avoid:** Always chain `->withQueryString()` after `->paginate()`. This appends all current request query parameters to the generated pagination URLs.
**Warning signs:** Navigating pages resets filters to default state.

### Pitfall 4: Retention Job Deleting Files But Not Nullifying Paths
**What goes wrong:** Image files are deleted from disk but `face_image_path`/`scene_image_path` columns still contain the old path. The `faceImageUrl`/`sceneImageUrl` accessors return a URL that leads to a 404.
**Why it happens:** File deletion succeeds but the `update()` call fails or is skipped.
**How to avoid:** Update the database column to null for each record as part of the same chunk iteration. The existing image URL accessors (`AlertController::faceImage`, `AlertController::sceneImage`) already abort(404) when the file doesn't exist, but nullifying the path prevents the URL from being generated at all -- cleaner for the UI. [VERIFIED: AlertController.php lines 75-80, RecognitionEvent.php line 91-93]
**Warning signs:** AlertDetailModal showing broken image icons instead of graceful "Scene image not available" placeholder.

### Pitfall 5: Sortable Column Whitelist Missing
**What goes wrong:** User manipulates the `sort` query parameter to inject arbitrary column names into the `ORDER BY` clause, potentially causing SQL errors or information leakage.
**Why it happens:** Controller blindly uses `$request->input('sort')` in `->orderBy()`.
**How to avoid:** Validate the `sort` parameter against a whitelist of allowed column names: `['captured_at', 'similarity', 'severity']`. For sorting by person name or camera name (which live on related tables), either use `addSelect` subquery or restrict to columns on the `recognition_events` table. [ASSUMED: standard security practice]
**Warning signs:** SQL errors in logs when sort parameter contains unexpected values.

### Pitfall 6: Retention Job Running During Peak Hours
**What goes wrong:** The retention job locks rows and performs disk I/O during active operator hours, causing slow queries on the history page.
**Why it happens:** Job scheduled at wrong time or overlapping with a previous slow run.
**How to avoid:** Schedule at 2:00 AM per D-09. Add `->withoutOverlapping()` to the schedule entry to prevent a slow run from overlapping with the next day's run. [VERIFIED: scheduling.md best practice]
**Warning signs:** Slow history page queries during early morning hours.

## Code Examples

### Controller: EventHistoryController Index Method
```php
// Source: Existing AlertController pattern + Laravel paginator conventions
<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\RecognitionEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventHistoryController extends Controller
{
    /** Display the event history page with filters and pagination. */
    public function index(Request $request): Response
    {
        $allowedSorts = ['captured_at', 'similarity', 'severity'];
        $sort = in_array($request->input('sort'), $allowedSorts, true)
            ? $request->input('sort')
            : 'captured_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $events = RecognitionEvent::query()
            ->with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path'])
            ->whereDate('captured_at', '>=', $dateFrom)
            ->whereDate('captured_at', '<=', $dateTo)
            ->when($request->camera_id, fn ($q, $id) => $q->where('camera_id', $id))
            ->when($request->severity, fn ($q, $sev) => $q->where('severity', $sev))
            ->when($request->search, fn ($q, $search) =>
                $q->where(function ($q) use ($search) {
                    $q->whereHas('personnel', fn ($p) =>
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('custom_id', 'like', "%{$search}%")
                    )
                    ->orWhere('name_from_camera', 'like', "%{$search}%")
                    ->orWhere('custom_id', 'like', "%{$search}%");
                })
            )
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('events/Index', [
            'events' => $events,
            'cameras' => Camera::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'camera_id' => $request->input('camera_id'),
                'search' => $request->input('search'),
                'severity' => $request->input('severity'),
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}
```

### Retention Command Structure
```php
// Source: CheckOfflineCamerasCommand pattern + db-performance.md chunkById best practice
<?php

namespace App\Console\Commands;

use App\Models\RecognitionEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupRetentionImagesCommand extends Command
{
    protected $signature = 'fras:cleanup-retention-images';
    protected $description = 'Delete expired face crop and scene images per retention policy';

    public function handle(): int
    {
        $sceneRetentionDays = config('hds.retention.scene_images_days', 30);
        $faceRetentionDays = config('hds.retention.face_crops_days', 90);

        $sceneCount = $this->cleanupImages('scene_image_path', $sceneRetentionDays);
        $faceCount = $this->cleanupImages('face_image_path', $faceRetentionDays);

        $message = "Retention cleanup: deleted {$sceneCount} scene images, {$faceCount} face crops";
        Log::info($message);
        $this->info($message);

        return self::SUCCESS;
    }

    private function cleanupImages(string $column, int $retentionDays): int
    {
        $count = 0;
        $cutoff = now()->subDays($retentionDays);

        RecognitionEvent::query()
            ->whereNotNull($column)
            ->where('captured_at', '<', $cutoff)
            ->chunkById(200, function ($events) use ($column, &$count) {
                foreach ($events as $event) {
                    $path = $event->$column;
                    if ($path && Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }
                    $event->update([$column => null]);
                    $count++;
                }
            });

        return $count;
    }
}
```

### Schedule Registration
```php
// Source: Existing routes/console.php pattern
// In routes/console.php:
Schedule::command('fras:cleanup-retention-images')->dailyAt('02:00')->withoutOverlapping();
```

### Vue Pagination Integration with Inertia
```typescript
// Source: Inertia v3 conventions + shadcn-vue Pagination API [ASSUMED]
import { router } from '@inertiajs/vue3';
import { Pagination, PaginationList, PaginationListItem, PaginationNext, PaginationPrev } from '@/components/ui/pagination';

// The paginator data from Laravel:
// { current_page, last_page, from, to, total, links, data }

function goToPage(page: number): void {
    router.get(
        props.events.path,
        { ...props.filters, page },
        { preserveState: true, replace: true },
    );
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `->lazy()` with manual chunking | `->paginate()->withQueryString()` | Laravel standard | Paginator handles URL generation, Inertia serializes it natively |
| Client-side table libraries (TanStack) | Server-side filtering + simple render table | Project convention | Simpler, scales with data, bookmarkable URLs |
| `Schedule::call()` closures | `Schedule::command()` with artisan commands | Laravel convention | Commands are testable, runnable manually, loggable |
| `chunk()` for batch updates | `chunkById()` for batch updates | Laravel best practice | Safe when records are modified during iteration (OFFSET stability) |

**Deprecated/outdated:**
- `Inertia::lazy()` / `LazyProp` removed in Inertia v3 -- use `Inertia::optional()` instead [VERIFIED: CLAUDE.md Inertia v3 section]
- `router.cancel()` replaced by `router.cancelAll()` in Inertia v3 [VERIFIED: CLAUDE.md]

## Assumptions Log

> List all claims tagged `[ASSUMED]` in this research.

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Dataset size at single facility adequate for LIKE queries without full-text index | Pitfall 2 | If >100K events, person search may be slow; mitigate with full-text index later |
| A2 | Sort column whitelist is standard security practice | Pitfall 5 | Low risk -- this is universally recommended |
| A3 | Sortable column header toggle pattern (click toggles direction) | Pattern 4 | Low risk -- standard UX convention |
| A4 | shadcn-vue Pagination component API supports numbered page list | Code Examples | Low risk -- shadcn-vue pagination docs confirm this |

## Open Questions

1. **Database migration for `captured_at` index**
   - What we know: The default sort is `captured_at DESC`. The existing composite index `(camera_id, captured_at)` only helps when filtering by camera.
   - What's unclear: Whether a standalone `captured_at` index is worth the write overhead for the insert-heavy recognition_events table.
   - Recommendation: Add the index. The table is insert-heavy but the history page is the primary read path. MySQL handles single-column index maintenance efficiently. The insert rate (max a few events per second) is negligible compared to the read benefit.

2. **Sort by person name / camera name**
   - What we know: UI spec shows Person and Camera columns as sortable. These values live on joined tables (`personnel.name`, `cameras.name`), not on `recognition_events`.
   - What's unclear: Whether to use `addSelect` subquery for sorting, a join, or restrict sortable columns to `recognition_events` table columns only.
   - Recommendation: For v1, restrict sortable columns to `captured_at`, `similarity`, and `severity` (all on `recognition_events`). Person name and camera name sorting adds query complexity for minimal benefit in a surveillance log context where time-based sorting is primary. The UI spec lists them as sortable but this is a discretion area. If needed later, `addSelect` subquery is the cleanest approach per `advanced-queries.md`.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4 with Laravel plugin |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact --filter=EventHistory` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| HIST-01 | Event history page loads with paginated events | Feature | `php artisan test --compact --filter=EventHistoryControllerTest` | No -- Wave 0 |
| HIST-02 | Filters by date range, camera, person, severity | Feature | `php artisan test --compact --filter=EventHistoryControllerTest` | No -- Wave 0 |
| HIST-03 | Event rows show face crop, person, camera, severity, similarity, timestamp | Feature | `php artisan test --compact --filter=EventHistoryControllerTest` | No -- Wave 0 |
| OPS-01 | Retention job deletes scene images older than configured days | Feature | `php artisan test --compact --filter=CleanupRetentionImagesTest` | No -- Wave 0 |
| OPS-02 | Retention job deletes face crops older than configured days | Feature | `php artisan test --compact --filter=CleanupRetentionImagesTest` | No -- Wave 0 |
| OPS-03 | Retention windows configurable in config/hds.php | Feature | `php artisan test --compact --filter=HdsConfigTest` | Yes (partial -- retention key test exists) |

### Sampling Rate
- **Per task commit:** `php artisan test --compact --filter=EventHistory` or `--filter=CleanupRetention`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/EventHistory/EventHistoryControllerTest.php` -- covers HIST-01, HIST-02, HIST-03
- [ ] `tests/Feature/Operations/CleanupRetentionImagesTest.php` -- covers OPS-01, OPS-02

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Existing `auth` middleware on routes [VERIFIED: routes/web.php middleware group] |
| V3 Session Management | no | No session changes in this phase |
| V4 Access Control | yes | Single command center, all authenticated users have access (consistent with AlertController pattern) [VERIFIED: AlertController.php comments] |
| V5 Input Validation | yes | Sort column whitelist, date format validation, severity enum validation |
| V6 Cryptography | no | No cryptographic operations |

### Known Threat Patterns

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| SQL injection via sort parameter | Tampering | Whitelist allowed sort columns, never interpolate user input into ORDER BY |
| SQL injection via search parameter | Tampering | Use Eloquent `where('col', 'like', "%{$search}%")` with parameter binding (automatic via query builder) [VERIFIED: Eloquent uses prepared statements] |
| Unauthorized access to event history | Information Disclosure | `auth` + `verified` middleware (same as all protected routes) [VERIFIED: routes/web.php] |
| Path traversal in image serving | Tampering | Existing `AlertController::faceImage`/`sceneImage` routes use model binding + Storage::disk -- no user-controlled path [VERIFIED: AlertController.php] |

## Project Constraints (from CLAUDE.md)

- **PHP 8.4**, Laravel 13, Vue 3, Inertia v3, Tailwind CSS v4, Pest v4
- **Use `php artisan make:` commands** to generate new files (controller, command)
- **Every change must be tested** -- write new tests or update existing, run with `php artisan test --compact`
- **Run `vendor/bin/pint --dirty --format agent`** before finalizing PHP changes
- **Use Wayfinder** for route functions -- never hardcode URLs
- **Use `<script setup lang="ts">`** exclusively for Vue components
- **Use `defineOptions()`** for layout metadata (breadcrumbs)
- **Inertia::flash('toast', ...)** for success messages
- **Check sibling files** for conventions before creating new files
- **Do not create documentation files** unless explicitly requested
- **shadcn-vue new-york-v4 style** with reka-ui primitives
- **Use `cn()` from `@/lib/utils`** for conditional class merging
- **Icon library:** lucide-vue-next
- **Test convention:** `test()` (not `it()`) matching existing project style [VERIFIED: AlertControllerTest.php]
- **Database:** MySQL for production, SQLite override in CI [VERIFIED: STATE.md decisions]
- **`withoutVite()`** in beforeEach for Inertia page tests [VERIFIED: AlertControllerTest.php]

## Sources

### Primary (HIGH confidence)
- `config/hds.php` -- Verified retention config keys and defaults exist
- `app/Models/RecognitionEvent.php` -- Verified model relationships, casts, accessors, fillable
- `app/Http/Controllers/AlertController.php` -- Verified image serving pattern, eager loading, Inertia render
- `database/migrations/2026_04_10_000003_create_recognition_events_table.php` -- Verified table schema and indexes
- `database/migrations/2026_04_11_000001_add_acknowledgment_columns_to_recognition_events_table.php` -- Verified severity + acknowledgment columns and indexes
- `app/Mqtt/Handlers/RecognitionHandler.php` -- Verified date-partitioned storage pattern: `recognition/{YYYY-MM-DD}/{type}s/{eventId}.jpg`
- `routes/console.php` -- Verified existing schedule pattern
- `app/Console/Commands/CheckOfflineCamerasCommand.php` -- Verified existing artisan command pattern
- `tests/Feature/Recognition/AlertControllerTest.php` -- Verified test patterns (beforeEach, withoutVite, factory states, Inertia assertions)
- `resources/js/pages/alerts/Index.vue` -- Verified filter pill pattern, modal integration, Echo listener pattern
- `resources/js/components/AlertDetailModal.vue` -- Verified reusable modal component
- `resources/js/components/SeverityBadge.vue` -- Verified reusable severity badge
- `components.json` -- Verified shadcn-vue config (new-york-v4, neutral base)
- `.claude/skills/laravel-best-practices/rules/db-performance.md` -- chunkById best practice
- `.claude/skills/laravel-best-practices/rules/scheduling.md` -- withoutOverlapping best practice
- Schema inspection via artisan tinker -- Verified column list, indexes, paginator output structure

### Secondary (MEDIUM confidence)
- `.planning/phases/07-event-history-operations/07-UI-SPEC.md` -- UI design contract (verified layout, components, interaction patterns)
- `.planning/phases/07-event-history-operations/07-CONTEXT.md` -- User decisions and discretion areas

### Tertiary (LOW confidence)
- shadcn-vue Pagination component API assumed based on shadcn patterns (not verified via Context7 in this session)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already in project, patterns verified in codebase
- Architecture: HIGH -- follows established controller/command patterns with minimal new concepts
- Pitfalls: HIGH -- verified via codebase inspection and Laravel best practices skill rules

**Research date:** 2026-04-11
**Valid until:** 2026-05-11 (30 days -- stable domain, no fast-moving dependencies)

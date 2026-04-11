---
phase: 07-event-history-operations
plan: 01
subsystem: api, operations
tags: [eloquent, pagination, filtering, artisan-command, retention, storage, scheduling]

# Dependency graph
requires:
  - phase: 05-recognition-alerting
    provides: RecognitionEvent model, factory states, AlertSeverity enum, recognition_events table
provides:
  - EventHistoryController with server-side filtered pagination (paginate 25, whitelist sort, date range, camera, severity, search)
  - CleanupRetentionImagesCommand with chunkById retention cleanup (scene 30d, face 90d configurable)
  - events.index route under auth+verified middleware
  - Daily 02:00 schedule entry with withoutOverlapping for retention cleanup
  - Standalone captured_at index on recognition_events for sort performance
affects: [07-02-PLAN, frontend-event-history]

# Tech tracking
tech-stack:
  added: []
  patterns: [whitelist-validated-sort, chunkById-batch-processing, date-range-default-today]

key-files:
  created:
    - app/Http/Controllers/EventHistoryController.php
    - app/Console/Commands/CleanupRetentionImagesCommand.php
    - database/migrations/2026_04_11_071900_add_captured_at_index_to_recognition_events_table.php
    - tests/Feature/EventHistory/EventHistoryControllerTest.php
    - tests/Feature/Operations/CleanupRetentionImagesTest.php
    - resources/js/pages/events/Index.vue
  modified:
    - routes/web.php
    - routes/console.php

key-decisions:
  - "Whitelist-validated sort columns ['captured_at', 'similarity', 'severity'] with in_array strict check prevents SQL injection"
  - "Search matches 4 sources via grouped where callback: personnel.name, personnel.custom_id, recognition_events.name_from_camera, recognition_events.custom_id"
  - "History includes ALL events (replay + ignored) unlike alert feed which filters to real-time non-ignored only"
  - "chunkById(200) for retention cleanup ensures memory-efficient iteration over potentially large datasets"
  - "Retention command nullifies database path columns after deleting files, preserving event rows for history"

patterns-established:
  - "Whitelist sort validation: in_array($request->input('sort'), $allowedSorts, true) with fallback"
  - "Date range default to today: now()->format('Y-m-d') when no params provided"
  - "Retention cleanup pattern: chunkById -> Storage::disk('local')->delete -> update column null"

requirements-completed: [HIST-01, HIST-02, OPS-01, OPS-02, OPS-03]

# Metrics
duration: 5min
completed: 2026-04-11
---

# Phase 7 Plan 1: Event History & Retention Backend Summary

**EventHistoryController with whitelist-validated sort, 4-source search, date-range-defaulted pagination plus CleanupRetentionImagesCommand with chunkById retention cleanup scheduled daily at 02:00**

## Performance

- **Duration:** 5 min
- **Started:** 2026-04-11T07:17:39Z
- **Completed:** 2026-04-11T07:23:38Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments
- EventHistoryController with server-side filtered pagination (25/page), whitelist-validated sort, date range defaulting to today, camera/severity/search filtering
- Search matches across 4 sources: personnel name, personnel custom_id, recognition_events.name_from_camera, recognition_events.custom_id
- CleanupRetentionImagesCommand deletes scene images >30 days and face crops >90 days with configurable retention, nullifies DB paths, preserves event rows
- Migration adds standalone captured_at index for default sort performance
- 30 comprehensive feature tests (19 controller + 11 command) all passing

## Task Commits

Each task was committed atomically:

1. **Task 1: EventHistoryController with server-side filtered pagination, migration, route, and tests** - `37823d8` (test)
2. **Task 2: CleanupRetentionImagesCommand with chunkById, schedule entry, and tests** - `2daa399` (feat)

_Note: TDD tasks have test + implementation in single commits (tests written first, implementation added to pass)_

## Files Created/Modified
- `app/Http/Controllers/EventHistoryController.php` - Event history index with filtered pagination, whitelist sort, search across 4 sources
- `app/Console/Commands/CleanupRetentionImagesCommand.php` - Retention cleanup with chunkById, configurable days, Storage::disk delete + DB nullify
- `database/migrations/2026_04_11_071900_add_captured_at_index_to_recognition_events_table.php` - Standalone captured_at index for sort performance
- `routes/web.php` - Added events.index route under auth+verified middleware
- `routes/console.php` - Added dailyAt('02:00')->withoutOverlapping() schedule for retention cleanup
- `resources/js/pages/events/Index.vue` - Stub Vue page component for Inertia test resolution (full implementation in Plan 02)
- `tests/Feature/EventHistory/EventHistoryControllerTest.php` - 19 feature tests for filtering, sorting, pagination, auth
- `tests/Feature/Operations/CleanupRetentionImagesTest.php` - 11 feature tests for deletion, preservation, config override

## Decisions Made
- Whitelist-validated sort columns ['captured_at', 'similarity', 'severity'] with `in_array(..., true)` strict check and fallback to 'captured_at' -- prevents SQL injection (T-7-01)
- Direction validation as binary choice: `=== 'asc' ? 'asc' : 'desc'` -- no user string in ORDER BY (T-7-06)
- Search uses grouped `where()` callback with `orWhere`/`orWhereHas` to keep search AND-ed with other filters
- History includes ALL events (replay + ignored) unlike AlertController which filters to real-time non-ignored -- deliberate per plan D-04
- chunkById(200) for retention cleanup ensures memory efficiency under large datasets (T-7-05)
- Retention command nullifies path columns after file deletion, preserving event rows for indefinite history

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- EventHistoryController API ready for frontend consumption in Plan 07-02
- Retention cleanup operational, scheduled via Laravel scheduler
- All 30 tests passing, route registered and verified

---
*Phase: 07-event-history-operations*
*Completed: 2026-04-11*

# Phase 7: Event History & Operations - Context

**Gathered:** 2026-04-11
**Status:** Ready for planning

<domain>
## Phase Boundary

Operators can search and filter past recognition events in a dedicated history page with a data table, server-side filtering by date/camera/person/severity, and standard pagination. The system also manages storage growth through a scheduled daily retention cleanup job that deletes expired image files while preserving recognition_events rows.

</domain>

<decisions>
## Implementation Decisions

### Event History Table Design
- **D-01:** Data table with sortable columns — face crop thumbnail, person name, camera, severity badge, similarity %, timestamp. Standard table layout for scanning large datasets. Pagination at 25-50 rows per page.
- **D-02:** Standard numbered page pagination with URL query parameters. Bookmarkable, works naturally with server-side filtering.
- **D-03:** Clicking a table row opens the existing AlertDetailModal from Phase 5 (face crop, scene image with bounding box overlay, full metadata, ack/dismiss buttons). Consistent experience across alert feed and history.
- **D-04:** Manual replay events (PushType=2) appear in the history table with a subtle "replay" badge or muted styling to distinguish them from real-time events. They are visible in history but clearly marked.

### Filter & Search UX
- **D-05:** Horizontal filter bar above the table — inline row of controls: date range picker, camera dropdown, person search input, severity filter pills (All | Critical | Warning | Info). Compact, always visible, consistent with alert feed filter pill pattern.
- **D-06:** Server-side filtering via Inertia visits — filters update URL query parameters and trigger Inertia page visits. Scales with large datasets, bookmarkable filter state.
- **D-07:** Default date range is "Today" when loading the history page. Most common use case for operators checking recent activity.
- **D-08:** Person search field matches against both person name and custom ID. Single search input, operators may know either identifier.

### Retention Job Behavior
- **D-09:** Daily scheduled job at 2:00 AM — runs once during low-activity hours. Deletes scene images older than `config('hds.retention.scene_images_days')` (default 30) and face crops older than `config('hds.retention.face_crops_days')` (default 90). Recognition_events rows are preserved indefinitely.
- **D-10:** After deleting an image file, set the corresponding path column (face_image_path / scene_image_path) to null. The existing image URL accessors already return null for null paths — UI gracefully shows placeholder. Clean and explicit.
- **D-11:** Log summary only — job logs a summary line to Laravel log: "Retention cleanup: deleted X scene images, Y face crops". No operator notification. Reviewable via Pail or log files.

### Claude's Discretion
- Data table component choice (build with shadcn-vue Table or custom component)
- Date range picker component implementation
- Camera dropdown population strategy (eager load vs prop)
- Pagination count per page (25 vs 50)
- Table column sorting implementation
- Retention job chunking strategy for large deletion batches
- Image file deletion approach (batch delete vs individual)
- Empty state design for no matching events

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — RecPush payload schema, PushType field (1=real-time, 2=replay), verify_status values for severity mapping

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 7 requirements: HIST-01 through HIST-03, OPS-01 through OPS-03
- `.planning/ROADMAP.md` — Phase 7 success criteria (4 criteria that must be TRUE)

### Configuration
- `config/hds.php` — Retention windows (scene_images_days: 30, face_crops_days: 90), already configured

### Prior Phase Context
- `.planning/phases/05-recognition-alerting/05-CONTEXT.md` — AlertFeedItem pattern (D-01), AlertDetailModal (D-05), severity classification (D-11/D-12), date-partitioned storage (D-06), replay event handling (D-13), alert feed cap at 50 (D-03)
- `.planning/phases/06-dashboard-map/06-CONTEXT.md` — Dashboard layout patterns, severity filtering pattern

### Existing Code (patterns to follow and extend)
- `app/Http/Controllers/AlertController.php` — Existing alert controller with index, acknowledge, dismiss, image serving routes
- `app/Models/RecognitionEvent.php` — Model with severity cast, relationships (camera, personnel, acknowledgedBy), image URL accessors
- `app/Enums/AlertSeverity.php` — Severity enum for filter values
- `database/factories/RecognitionEventFactory.php` — Factory with states for testing
- `resources/js/pages/alerts/Index.vue` — Alert feed page with Echo listener, severity filter pills, AlertDetailModal integration
- `resources/js/components/AlertDetailModal.vue` — Detail modal to reuse for history row clicks
- `resources/js/components/AlertFeedItem.vue` — Compact row pattern reference
- `resources/js/components/SeverityBadge.vue` — Severity badge component for table cells
- `resources/js/types/index.ts` — RecognitionEvent TypeScript type
- `tests/Feature/Infrastructure/HdsConfigTest.php` — Existing test for retention config

### Codebase Maps
- `.planning/codebase/CONVENTIONS.md` — Naming patterns, code style, design patterns
- `.planning/codebase/STRUCTURE.md` — Where to add new code

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AlertDetailModal.vue`: Full detail modal with face crop, scene image, bounding box overlay, metadata — reuse directly for history row clicks
- `SeverityBadge.vue`: Severity-colored badge component for table severity column
- `AlertController.php`: Image serving routes (`faceImage`, `sceneImage`) already handle auth-protected image access
- `RecognitionEvent` model: All relationships, casts, and accessors already in place
- `RecognitionEventFactory`: Factory with `withFaceImage()`, `withSceneImage()` states for testing
- shadcn-vue `Table` component available in `components/ui/table/`
- Filter pill pattern from `alerts/Index.vue` (severity toggle buttons)
- `useAlertSound` composable pattern for potential reuse

### Established Patterns
- Inertia `defineOptions({ layout: { breadcrumbs: [...] } })` for page layout and breadcrumbs
- Server-side queries with `with()` eager loading for relationships
- Wayfinder-generated typed route functions for all controller methods
- Echo listener pattern for real-time updates (not needed for history but maintains consistency)
- Date-partitioned storage: `recognition/{YYYY-MM-DD}/faces/` and `recognition/{YYYY-MM-DD}/scenes/`

### Integration Points
- `routes/web.php` — New routes for event history page (index with filters)
- `app/Http/Controllers/` — New EventHistoryController (or extend AlertController)
- `resources/js/pages/` — New `events/Index.vue` history page
- `app/Console/Commands/` — New `CleanupRetentionImagesCommand` artisan command
- `app/Console/Kernel.php` or `routes/console.php` — Schedule the retention job daily at 2 AM
- Sidebar navigation — Add "Event History" link

</code_context>

<specifics>
## Specific Ideas

- History table should feel like a forensic investigation tool — scannable columns, precise filters, bookmarkable URLs for sharing specific filtered views
- Replay events with subtle visual distinction (badge or muted text) so operators don't confuse them with real-time events during investigation
- Retention job is intentionally simple — daily batch delete, log summary, no operator notification. The retention config in hds.php is the control surface.
- After retention cleanup, null image paths cause the existing accessors to return null, and the AlertDetailModal should already handle missing images gracefully (gray placeholder from Phase 5 D-07)

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 07-event-history-operations*
*Context gathered: 2026-04-11*

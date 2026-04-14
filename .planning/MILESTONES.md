# Milestones

## v1.0 FRAS MVP (Shipped: 2026-04-14)

**Phases completed:** 10 phases, 31 plans, 64 tasks

**Key accomplishments:**

- MySQL database with 4 FRAS tables, config/hds.php unified configuration, php-mqtt/laravel-client + Reverb + Intervention Image + Echo packages installed
- fras:mqtt-listen artisan command with TopicRouter dispatching to 4 handler stubs, Supervisor production configs, and 5-process dev orchestration via concurrently
- Laravel Reverb WebSocket broadcasting with Echo client, private fras.alerts channel auth, and TestBroadcastEvent round-trip validation
- Camera Eloquent model with resource controller, form request validation, CameraStatusChanged broadcast event, factory/seeder, and 18 passing feature tests
- MQTT HeartbeatHandler and OnlineOfflineHandler replacing stubs, plus scheduled offline detection command running every 30 seconds
- Complete camera management UI with Mapbox GL JS maps, real-time Echo status updates, and full CRUD pages
- Personnel CRUD API with PhotoProcessor service (resize/compress/hash via Intervention Image v4), 27 tests passing, TypeScript type contract
- Create page
- CameraEnrollmentService with MQTT payload building, batch chunking, WithoutOverlapping job, and auto-dispatch hooks in PersonnelController and CameraController
- 1. [Rule 1 - Bug] Fixed Eloquent auto-timestamp overwriting test backdated updated_at
- EnrollmentController with retry/resyncAll endpoints, Personnel Show enrollment sidebar with Echo real-time updates, per-camera retry buttons, and delete dialog camera warning
- Backend changes:
- AlertSeverity enum classifying block-list/refused/allow/stranger events, RecognitionEvent model with factory, RecognitionAlert broadcast event, and TypeScript type definitions
- Full RecPush MQTT handler parsing camera payloads with firmware quirk handling, base64 image storage to date-partitioned directories, severity classification, and broadcast dispatch for real-time alerts
- AlertController with severity-filtered feed page, acknowledge/dismiss actions, auth-protected image serving, and sidebar/header Live Alerts navigation
- 1. [Rule 2 - Missing] Added DialogDescription for accessibility
- DashboardController serving cameras/stats/events with full-viewport three-panel layout shell, top nav, status bar, and connection banner
- Multi-marker Mapbox map with camera popups, pulse ring animation on recognition events, theme-synced style toggle, and sound/empty-state integration
- Left rail with TodayStats 2x2 grid and camera list, right alert feed with severity/camera dual-axis filtering, completing the three-panel command center
- EventHistoryController with whitelist-validated sort, 4-source search, date-range-defaulted pagination plus CleanupRetentionImagesCommand with chunkById retention cleanup scheduled daily at 02:00
- Event history page with filterable data table (date range, camera select, debounced search, severity pills), sortable columns, replay badges, numbered pagination, and AlertDetailModal integration via Inertia server-side visits
- Inter font, slate/steel blue CSS custom property palette for light and dark modes, HSL inline backgrounds, and accent blue Inertia progress bar
- Dark mode glow effects on severity/status indicators, dense data grid table styling, glassmorphism overlays, and font-semibold weight consistency across 14 components
- Commit:
- FRAS-branded dark ops portal Welcome page, dense data grid styling on event history, severity-colored alert modal borders, and zero font-medium remaining across entire frontend
- Fortify registration disabled (404 on /register), UserController CRUD with profile/password validation traits and self-delete prevention
- Vue pages for user CRUD (Index with dense table, Create with password fields, Edit with optional password and self-delete prevention) and Users nav items in sidebar and dashboard top nav
- Fixed CameraStatusChanged broadcast name mismatch preventing real-time camera status updates, added commented-out Pusher Cloud config to .env.example with matching TypeScript declarations
- Acknowledger operator name wired from backend accessor through Inertia props to AlertFeedItem, AlertDetailModal, and event history with optimistic updates

---

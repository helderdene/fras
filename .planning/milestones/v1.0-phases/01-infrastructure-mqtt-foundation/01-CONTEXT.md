# Phase 1: Infrastructure & MQTT Foundation - Context

**Gathered:** 2026-04-10
**Status:** Ready for planning

<domain>
## Phase Boundary

This phase delivers all foundational services required by subsequent phases: MySQL database with all FRAS tables, MQTT client library and listener command skeleton, Laravel Reverb WebSocket broadcasting, Supervisor production configs, and a single `composer run dev` command that orchestrates everything. No UI pages or business logic — just the plumbing.

</domain>

<decisions>
## Implementation Decisions

### Database Strategy
- **D-01:** MySQL everywhere — dev environment uses MySQL via Herd's built-in MySQL service. No SQLite/MySQL divergence.
- **D-02:** Single database — all tables (existing users/sessions/cache/jobs + new FRAS tables) in one MySQL database.
- **D-03:** All 4 core FRAS tables created upfront in Phase 1: cameras, personnel, camera_enrollments, recognition_events. Schema based on spec. Later phases may add columns.
- **D-04:** Update `.env.example` and `.env` to default to MySQL connection with FRAS database name.

### MQTT Architecture
- **D-05:** Single `fras:mqtt-listen` artisan command subscribes to all camera topics using wildcard patterns. Messages routed to handler classes based on topic pattern (topic router pattern).
- **D-06:** Auto-reconnect in process — leverage php-mqtt/laravel-client's built-in reconnect with configurable backoff. Re-subscribe to all topics on reconnect. Supervisor only restarts on process crash.
- **D-07:** MQTT configuration (broker host, port, credentials, client ID, topic prefixes) lives in `config/hds.php` under an `mqtt` key — not in a separate config file.
- **D-08:** Mosquitto broker is assumed to be available on the network. Phase 1 provides a setup guide/script in docs but does not automate broker provisioning.

### Reverb & Broadcasting
- **D-09:** Single private WebSocket channel (`fras.alerts`) for all recognition events. Private channel requires Fortify auth — only logged-in users receive events.
- **D-10:** Phase 1 includes a full round-trip broadcast test: fire an event via Reverb and confirm a Laravel Echo client receives it. Validates success criteria #3.
- **D-11:** Laravel Echo configured with Pusher adapter (Reverb is Pusher-compatible). Echo setup in `resources/js/app.ts` or a dedicated bootstrap file.

### Dev Orchestration
- **D-12:** Extend existing `composer run dev` concurrently command to include Reverb and MQTT listener processes. All processes (queue, pail, vite, reverb, mqtt-listener) in one terminal with color-coded output.
- **D-13:** Remove `php artisan serve` from dev command — Herd serves the app at `https://fras.test`. Redundant process eliminated.
- **D-14:** Create Supervisor `.conf` files in `deploy/supervisor/` for production: MQTT listener, Reverb server, and queue worker. Checked into the repo, ready for deployment.

### FRAS Config
- **D-15:** `config/hds.php` is the unified FRAS configuration file. Covers: MQTT connection, retention windows, enrollment limits, alert thresholds, photo constraints, Mapbox tokens. All settings env-overridable.

### Claude's Discretion
- Table column types and indexes for the 4 FRAS migrations — follow the spec and optimize for query patterns
- php-mqtt/laravel-client version selection — use latest stable compatible with Laravel 13
- Intervention Image v3 installation — include in Phase 1 composer require since it's a stack addition
- Reverb installation and config — follow Laravel's official setup
- Handler class naming and namespace conventions — follow existing app patterns

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### FRAS Specification
- `docs/HDS-FRAS-Spec-v1.1.md` — Complete system specification including MQTT topic patterns, JSON payload schemas, database schema, enrollment protocol, recognition event structure, and firmware quirks (Appendix C)

### Project Planning
- `.planning/REQUIREMENTS.md` — Phase 1 requirements: INFRA-01 through INFRA-05
- `.planning/ROADMAP.md` — Phase 1 success criteria (5 criteria that must be TRUE)

### Existing Codebase
- `.planning/codebase/STACK.md` — Current technology stack, build commands, database config
- `.planning/codebase/INTEGRATIONS.md` — Current integration state (broadcasting=log, queue=database, no MQTT)
- `.planning/codebase/STRUCTURE.md` — Directory layout and where to add new code

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `composer.json` scripts section: existing `concurrently` setup for dev command — extend, don't rewrite
- `config/database.php`: MySQL connection already configured, just needs to be activated
- `config/queue.php`: Database queue driver already working — queue worker process just needs adding to Supervisor
- `.env.example`: Template for environment variables — needs MQTT, Reverb, and MySQL additions

### Established Patterns
- Config files follow Laravel conventions with env() defaults — `config/hds.php` should match this pattern
- Artisan commands created via `php artisan make:command`
- Service providers for registering bindings and bootstrapping services

### Integration Points
- `.env` / `.env.example` — New env vars for MySQL, MQTT, Reverb, Mapbox
- `composer.json` — New packages (php-mqtt/laravel-client, Intervention Image, Laravel Reverb) and updated dev script
- `bootstrap/app.php` — Broadcasting service provider registration if needed
- `config/broadcasting.php` — Reverb connection configuration
- `resources/js/app.ts` — Laravel Echo initialization for WebSocket client

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches following Laravel conventions and the FRAS spec.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-infrastructure-mqtt-foundation*
*Context gathered: 2026-04-10*

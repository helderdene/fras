---
phase: 01-infrastructure-mqtt-foundation
plan: 01
subsystem: infra
tags: [mysql, mqtt, php-mqtt, laravel-reverb, intervention-image, laravel-echo, mapbox, migrations]

# Dependency graph
requires: []
provides:
  - "4 FRAS database tables: cameras, personnel, recognition_events, camera_enrollments"
  - "config/hds.php unified configuration with 6 sections (mqtt, retention, enrollment, photo, alerts, mapbox)"
  - "PHP packages: php-mqtt/laravel-client, laravel/reverb, intervention/image-laravel"
  - "JS packages: laravel-echo, @laravel/echo-vue, pusher-js"
  - "MySQL as default database connection (SQLite override in CI)"
  - "MQTT and Mapbox env vars in .env.example"
affects: [01-02-PLAN, 01-03-PLAN, 02-mqtt-listener, 03-recognition-events, 04-enrollment-sync, 05-dashboard-frontend]

# Tech tracking
tech-stack:
  added: [php-mqtt/laravel-client, laravel/reverb, intervention/image-laravel, laravel-echo, "@laravel/echo-vue", pusher-js]
  patterns: [unified-config-file, mysql-with-sqlite-ci-override, anonymous-migration-classes]

key-files:
  created:
    - config/hds.php
    - database/migrations/2026_04_10_000001_create_cameras_table.php
    - database/migrations/2026_04_10_000002_create_personnel_table.php
    - database/migrations/2026_04_10_000003_create_recognition_events_table.php
    - database/migrations/2026_04_10_000004_create_camera_enrollments_table.php
    - tests/Feature/Infrastructure/FrasMigrationTest.php
    - tests/Feature/Infrastructure/HdsConfigTest.php
  modified:
    - composer.json
    - package.json
    - .env.example
    - .github/workflows/tests.yml
    - tests/Pest.php

key-decisions:
  - "HdsConfigTest placed in Feature (not Unit) directory because config() helper requires booted Laravel app"
  - "MySQL root with empty password as .env default; user configures credentials per environment"
  - ".gitignore committed alongside task files to ensure .env stays excluded from version control"

patterns-established:
  - "config/hds.php: single centralized config file for all FRAS settings with env() defaults"
  - "CI SQLite override: DB_CONNECTION=sqlite and DB_DATABASE=:memory: in GitHub Actions env block"
  - "RefreshDatabase enabled globally for all Feature tests via tests/Pest.php"

requirements-completed: [INFRA-01]

# Metrics
duration: 5min
completed: 2026-04-10
---

# Phase 01 Plan 01: Infrastructure & Dependencies Summary

**MySQL database with 4 FRAS tables, config/hds.php unified configuration, php-mqtt/laravel-client + Reverb + Intervention Image + Echo packages installed**

## Performance

- **Duration:** 5 min
- **Started:** 2026-04-10T06:53:00Z
- **Completed:** 2026-04-10T06:58:02Z
- **Tasks:** 2
- **Files modified:** 14

## Accomplishments
- Installed all PHP packages (php-mqtt/laravel-client, laravel/reverb, intervention/image-laravel) and JS packages (laravel-echo, @laravel/echo-vue, pusher-js)
- Switched default database from SQLite to MySQL with CI SQLite override for continued test compatibility
- Created 4 FRAS migration files with correct schemas, foreign keys, cascade deletes, and composite indexes
- Created config/hds.php with 6 sections covering MQTT, retention, enrollment, photo constraints, alerts, and Mapbox
- All 53 tests pass (44 existing + 4 migration + 9 config = 53 after Pint fixes)

## Task Commits

Each task was committed atomically:

1. **Task 1: Install packages, switch to MySQL, create 4 FRAS migrations** - `0a5ac00` (feat)
2. **Task 2: Create unified config/hds.php configuration file** - `17f29aa` (feat)

## Files Created/Modified
- `composer.json` - Added php-mqtt/laravel-client, laravel/reverb, intervention/image-laravel
- `package.json` - Added laravel-echo, @laravel/echo-vue, pusher-js
- `.env.example` - Switched to MySQL, added MQTT_* and MAPBOX_* vars
- `.github/workflows/tests.yml` - Added DB_CONNECTION: sqlite override for CI
- `tests/Pest.php` - Enabled RefreshDatabase for Feature tests
- `config/database.php` - Existing MySQL config (committed for completeness)
- `config/hds.php` - Unified FRAS configuration with 6 sections
- `database/migrations/2026_04_10_000001_create_cameras_table.php` - Cameras schema with device_id unique, GPS coordinates
- `database/migrations/2026_04_10_000002_create_personnel_table.php` - Personnel schema with custom_id unique, person_type
- `database/migrations/2026_04_10_000003_create_recognition_events_table.php` - Recognition events with FK to cameras/personnel, composite indexes
- `database/migrations/2026_04_10_000004_create_camera_enrollments_table.php` - Camera enrollments with cascade deletes, unique constraint
- `tests/Feature/Infrastructure/FrasMigrationTest.php` - 4 tests verifying table schemas
- `tests/Feature/Infrastructure/HdsConfigTest.php` - 9 tests verifying config sections, types, defaults

## Decisions Made
- **HdsConfigTest in Feature directory:** Plan specified `tests/Unit/Infrastructure/` but `config()` requires the Laravel app container which is only available in Feature tests (Unit tests use base PHPUnit TestCase). Moved to `tests/Feature/Infrastructure/` for correctness.
- **MySQL root with empty password default:** Standard Laravel convention; users configure DB_PASSWORD per environment.
- **.gitignore included in first commit:** Ensures `.env` (with real credentials) is never accidentally committed.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Moved HdsConfigTest from Unit to Feature directory**
- **Found during:** Task 2 (config test creation)
- **Issue:** Unit tests don't boot the Laravel application container, so `config()` helper throws "Target class [config] does not exist"
- **Fix:** Moved test file from `tests/Unit/Infrastructure/HdsConfigTest.php` to `tests/Feature/Infrastructure/HdsConfigTest.php`
- **Files modified:** tests/Feature/Infrastructure/HdsConfigTest.php
- **Verification:** All 9 config tests pass
- **Committed in:** 17f29aa (Task 2 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Test location change only; no functional difference. All acceptance criteria met.

## Issues Encountered
- MySQL root access denied locally (user's MySQL requires a password). Not blocking -- tests use SQLite via RefreshDatabase. User needs to set DB_PASSWORD in .env for local MySQL usage.

## User Setup Required
- Set `DB_PASSWORD` in `.env` to match local MySQL credentials
- Ensure MySQL service is running and `fras` database exists (`CREATE DATABASE fras`)
- MQTT_* and MAPBOX_* vars in `.env` need real values when connecting to actual hardware

## Next Phase Readiness
- All 4 FRAS tables ready for model creation (Plan 02)
- config/hds.php ready for MQTT listener and all FRAS subsystems
- All packages installed for Reverb broadcasting setup (Plan 03)
- CI pipeline updated and passing with SQLite override

---
*Phase: 01-infrastructure-mqtt-foundation*
*Completed: 2026-04-10*

# Phase 1: Infrastructure & MQTT Foundation - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-10
**Phase:** 1-Infrastructure & MQTT Foundation
**Areas discussed:** Database strategy, MQTT architecture, Reverb & broadcasting, Dev orchestration

---

## Database Strategy

| Option | Description | Selected |
|--------|-------------|----------|
| MySQL everywhere | Switch dev to MySQL via Herd's built-in MySQL service. Avoids SQLite/MySQL divergence. | ✓ |
| SQLite for dev, MySQL for prod | Keep SQLite locally for speed, MySQL only in production. | |
| MySQL via Docker (Sail) | Use Laravel Sail's MySQL container for dev. | |

**User's choice:** MySQL everywhere
**Notes:** Herd's built-in MySQL service avoids divergence between dev and prod

| Option | Description | Selected |
|--------|-------------|----------|
| Single database | All tables in one MySQL database. Foreign keys work natively. | ✓ |
| Separate databases | Existing tables in 'hds' DB, FRAS tables in 'fras' DB. | |

**User's choice:** Single database
**Notes:** Simpler config, cross-table foreign keys work naturally

| Option | Description | Selected |
|--------|-------------|----------|
| Full FRAS config | MQTT, retention, enrollment limits, alert thresholds, photo constraints, Mapbox tokens — all in one place | ✓ |
| Retention only for now | Start minimal with just retention windows | |
| You decide | Claude discretion on config structure | |

**User's choice:** Full FRAS config
**Notes:** Unified config/hds.php covering all FRAS-specific settings

| Option | Description | Selected |
|--------|-------------|----------|
| All tables upfront | Create cameras, personnel, enrollments, recognition_events in Phase 1 | ✓ |
| Only infrastructure tables | Phase 1 creates only what it tests directly | |

**User's choice:** All tables upfront
**Notes:** Matches success criteria #1 which explicitly requires these tables exist

---

## MQTT Architecture

| Option | Description | Selected |
|--------|-------------|----------|
| Single command, topic router | One fras:mqtt-listen command, routes messages to handlers by topic pattern | ✓ |
| Multiple commands per topic | Separate artisan commands per topic group | |
| Queue-bridged listener | Thin MQTT listener dispatches to Laravel jobs | |

**User's choice:** Single command, topic router
**Notes:** One Supervisor process, handler classes for each topic pattern

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-reconnect in process | php-mqtt/laravel-client auto-reconnect with backoff, re-subscribe on reconnect | ✓ |
| Crash and Supervisor restart | Let process exit on disconnect, Supervisor restarts | |
| You decide | Claude picks based on library capabilities | |

**User's choice:** Auto-reconnect in process
**Notes:** Supervisor only restarts on actual crashes

| Option | Description | Selected |
|--------|-------------|----------|
| In config/hds.php | MQTT settings as section within unified FRAS config | ✓ |
| Separate config/mqtt.php | Dedicated MQTT config file | |
| Use php-mqtt defaults | php-mqtt's own config + FRAS topics in hds.php | |

**User's choice:** In config/hds.php
**Notes:** All FRAS config in one place

| Option | Description | Selected |
|--------|-------------|----------|
| Document only | Assume Mosquitto available, provide setup guide | ✓ |
| Docker Mosquitto for dev | docker-compose.yml with Mosquitto | |
| Herd service if available | Check Herd capability, fallback to Docker | |

**User's choice:** Document only
**Notes:** Broker runs on the server, not managed by Laravel

---

## Reverb & Broadcasting

| Option | Description | Selected |
|--------|-------------|----------|
| Single FRAS channel | One channel 'fras.alerts' for all recognition events | ✓ |
| Per-camera channels | Each camera gets own channel | |
| Severity-based channels | Separate channels by severity level | |

**User's choice:** Single FRAS channel
**Notes:** Simple for single-site, single-admin setup

| Option | Description | Selected |
|--------|-------------|----------|
| Private channel | Requires Fortify auth, only logged-in users receive events | ✓ |
| Public channel | Open channel, no WS auth | |

**User's choice:** Private channel
**Notes:** Matches single-admin security model

| Option | Description | Selected |
|--------|-------------|----------|
| Full round-trip test | Broadcast event via Reverb, confirm Echo client receives it | ✓ |
| Server start only | Just verify Reverb starts and accepts connections | |

**User's choice:** Full round-trip test
**Notes:** Validates success criteria #3

---

## Dev Orchestration

| Option | Description | Selected |
|--------|-------------|----------|
| Extend existing concurrently | Add Reverb and MQTT listener to existing concurrently command | ✓ |
| Separate terminal commands | Keep existing dev, add composer run fras | |
| Replace with Herd services | Use Herd for background services | |

**User's choice:** Extend existing concurrently
**Notes:** All 5+ processes in one terminal with color-coded output

| Option | Description | Selected |
|--------|-------------|----------|
| Create conf files | Supervisor .conf files in deploy/supervisor/, checked into repo | ✓ |
| Document only | List processes in docs, create files during deployment | |
| You decide | Claude decides based on deployment story | |

**User's choice:** Create conf files
**Notes:** Ready for production deployment

| Option | Description | Selected |
|--------|-------------|----------|
| Remove artisan serve | Herd serves at fras.test, remove redundant process | ✓ |
| Keep artisan serve | Keep for non-Herd environments | |

**User's choice:** Remove artisan serve
**Notes:** Herd is the dev server, artisan serve is redundant

---

## Claude's Discretion

- Table column types and indexes for FRAS migrations
- php-mqtt/laravel-client version selection
- Intervention Image v3 installation timing
- Reverb installation and config approach
- Handler class naming and namespace conventions

## Deferred Ideas

None — discussion stayed within phase scope

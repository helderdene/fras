# HDS-FRAS — Face Recognition Alert System

## What This Is

A web-based Face Recognition Alert System that integrates with AI Intelligent IP Cameras over MQTT. Operators monitor camera locations on a map, receive real-time alerts when personnel are recognized, and manage personnel enrollment across cameras from a central admin interface. Built as an extension of an existing Laravel 13 + Vue 3 + Inertia v3 application for HDSystem (HyperDrive System), deployed at a single-site facility in Butuan City, Philippines.

## Core Value

Operators see every matched-face recognition event in real time on a map-based dashboard with severity-classified alerts, so critical events (block-list matches) are never missed.

## Requirements

### Validated

- ✓ User authentication (login, register, password reset, email verification, 2FA) — existing via Fortify
- ✓ User profile and settings management — existing
- ✓ Dark/light theme with system preference detection — existing
- ✓ Inertia SPA navigation with Wayfinder typed routes — existing
- ✓ CI pipeline (lint, format, type-check, tests) — existing
- ✓ MQTT listener: long-running artisan command subscribing to camera topics (Rec, Ack, basic, heartbeat) — Validated in Phase 1: Infrastructure & MQTT Foundation
- ✓ MySQL database with FRAS tables (cameras, personnel, recognition_events, camera_enrollments) — Validated in Phase 1
- ✓ Reverb WebSocket broadcasting with private channel auth — Validated in Phase 1
- ✓ Supervisor production process configs (MQTT, Reverb, queue) — Validated in Phase 1
- ✓ Dev orchestration: single command starts all 5 processes — Validated in Phase 1
- ✓ Camera management: register cameras with device ID, name, location, GPS coordinates — Validated in Phase 2: Camera Management & Liveness
- ✓ Camera liveness: track online/offline state via MQTT heartbeat and Online/Offline events — Validated in Phase 2
- ✓ Camera map view: display cameras as pins on a Mapbox GL JS map with status indicators — Validated in Phase 2
- ✓ Camera offline detection: mark cameras offline when heartbeat absent >90 seconds — Validated in Phase 2
- ✓ Personnel CRUD: create, edit, delete personnel with photo, custom ID, person type (allow/block) — Validated in Phase 3
- ✓ Photo preprocessing: resize to 1080p max, compress to <1MB JPEG, compute MD5 hash — Validated in Phase 3
- ✓ Personnel admin: list with sync status dots, detail with per-camera enrollment status — Validated in Phase 3
- ✓ Enrollment sync: push personnel to all cameras via MQTT EditPersonsNew, track per-camera status — Validated in Phase 4
- ✓ Enrollment ACK handling: correlate EditPersonsNew-Ack responses, update camera_enrollments — Validated in Phase 4
- ✓ Delete sync: remove personnel from cameras via MQTT DeletePersons — Validated in Phase 4
- ✓ RecPush event processing: parse recognition events, save face/scene images, insert recognition_events rows — Validated in Phase 5
- ✓ Alert classification: critical (block-list), warning (refused), info (normal allowed) — Validated in Phase 5
- ✓ Real-time broadcast: push RecognitionAlert events to browsers via Laravel Reverb WebSocket — Validated in Phase 5
- ✓ Alert feed: reverse-chronological with severity coloring, click for detail modal with face crop and metadata — Validated in Phase 5
- ✓ Audio alert: play sound on critical (block-list) recognition events — Validated in Phase 5
- ✓ Dashboard: full-viewport layout with camera list rail, map center, live alert feed rail, status bar — Validated in Phase 6
- ✓ Map interaction: camera markers pulse/flash on recognition events, dark/light map style toggle — Validated in Phase 6
- ✓ Event history: searchable, filterable recognition log page — Validated in Phase 7
- ✓ Storage retention: scheduled cleanup — scene images after 30 days, face crops after 90 days — Validated in Phase 7
- ✓ Full visual redesign: slate/steel blue palette, Inter font, glassmorphism, glow effects, dense data grids, FRAS branding — Validated in Phase 8
- ✓ Public registration disabled, admin-only user creation and management — Validated in Phase 9

### Active

(Fresh requirements will be defined with `/gsd-new-milestone`)

### Out of Scope

- Stranger detection alerts (Snap topic events) — deferred to v1.1
- Multi-site or multi-tenant deployments — deferred to v2
- Behavioral analytics events (tripwire, area intrusion, smoke/fire, PPE) — deferred to v2+
- Mobile native applications — web is mobile-responsive but no native build
- Bulk personnel import via CSV/Excel — deferred to v1.1
- Temporary visitor passes with auto-expiry — deferred to v2
- Continuous transmission ACK loop (PushAck) — cameras configured with ResumefromBreakpoint disabled
- Multiple user roles or permissions — all users are admins for v1; admin user management added in Phase 9
- Audit logs of admin actions — deferred to v1.1
- MQTT TLS (mqtts://) — plain MQTT on internal network only
- Encrypted at-rest storage — not required for v1
- Two-factor authentication for FRAS-specific users — existing Fortify 2FA covers the single admin

## Current State

**v1.0 FRAS MVP shipped 2026-04-14.** 10 phases, 31 plans, 58 requirements delivered in 5 days.

- ~16.9k PHP, ~11.4k Vue, ~1.8k TypeScript (30k+ LOC)
- 293 passing tests (3 pre-existing supervisor config failures unrelated to FRAS)
- Full SOC-aesthetic UI redesign with glassmorphism, glow effects, dense data grids
- Real-time MQTT-to-browser pipeline: camera -> MQTT -> Laravel -> Reverb -> Echo -> Vue

## Context

- **Shipped codebase:** Laravel 13 + Vue 3 + Inertia v3 + Tailwind v4 + shadcn-vue + php-mqtt/laravel-client + Intervention Image v4 + Laravel Reverb + Mapbox GL JS v3 + Laravel Echo.
- **Camera hardware:** AI Intelligent IP Cameras with onboard face recognition, MQTT v3.1.1 protocol (QoS 0), JSON payloads. Firmware verified against test device (Cloud ID 1026700).
- **Deployment target:** Single Linux server running Laravel, Mosquitto MQTT broker, MySQL, and Reverb. Up to 8 cameras, up to 200 enrolled personnel.
- **Firmware quirks:** `personName` vs `persionName` field name discrepancy, empty `customId` for camera-UI-enrolled people, missing `scene` field, numeric fields as strings. All documented in spec Appendix C.
- **Database:** MySQL in production, SQLite for development/testing.

## Constraints

- **Camera protocol:** MQTT v3.1.1, QoS 0, JSON payloads. Max 1000 personnel per enrollment batch. Only one batch in-flight per camera.
- **Photo limits:** Enrollment photos must be <=1MB, <=1080p. Camera fetches photos via HTTP URL (picURI must be network-reachable from camera).
- **Network:** Camera subnet must reach the Laravel server for MQTT and photo download. No NAT translation awareness on cameras.
- **Concurrency:** WithoutOverlapping middleware required — one enrollment job per camera at a time.
- **Storage:** Face crops up to 1MB, scene images up to 2MB per event. Retention policy required to manage disk growth.
- **Map:** Mapbox GL JS with custom HelderDene account styles (dark + light). Free tier sufficient for single command center.
- **Stack additions:** php-mqtt/laravel-client, Intervention Image v3, Laravel Reverb, Mapbox GL JS v3, Laravel Echo with Pusher adapter.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Build into existing Laravel+Vue app | Reuse auth, theming, UI components, CI pipeline | Validated |
| Vue 3 (not React) | Existing codebase is Vue 3; spec aligns | Validated |
| Mapbox GL JS (not MapLibre) | Custom HelderDene styles already authored; free tier sufficient | Validated |
| MySQL for production | FRAS needs relational integrity, JSON columns, and scale beyond SQLite | Validated |
| EditPersonsNew over AddPersons | Upsert semantics — simpler enrollment logic | Validated |
| Single admin user for v1 | Minimal security model appropriate for single-site internal network | Validated |
| Public personnel photos (no auth) | Camera must fetch via HTTP URL; accepted trade-off for v1 | Validated |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? -> Move to Out of Scope with reason
2. Requirements validated? -> Move to Validated with phase reference
3. New requirements emerged? -> Add to Active
4. Decisions to log? -> Add to Key Decisions
5. "What This Is" still accurate? -> Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check -- still the right priority?
3. Audit Out of Scope -- reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-04-14 after v1.0 milestone completion*

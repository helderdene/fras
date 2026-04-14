---
phase: 04-enrollment-sync
plan: 02
subsystem: enrollment
tags: [mqtt, ack-correlation, broadcast, timeout, reverb, cache, scheduled-command]

# Dependency graph
requires:
  - phase: 04-enrollment-sync
    plan: 01
    provides: CameraEnrollmentService, EnrollPersonnelBatch job, CameraEnrollment model, cache correlation keys
provides:
  - AckHandler full implementation with cache-based ACK correlation
  - EnrollmentStatusChanged broadcast event on fras.alerts private channel
  - CheckEnrollmentTimeoutsCommand scheduled every minute
  - OnlineOfflineHandler dispatches pending enrollments on camera online transition
affects: [04-03-PLAN, 04-04-PLAN, 06-dashboard-map]

# Tech stack
added: []
patterns:
  - "Cache::pull for atomic one-time ACK correlation (prevents replay)"
  - "ShouldBroadcast event on PrivateChannel for real-time enrollment status"
  - "Scheduled command with configurable timeout via config('hds.enrollment.ack_timeout_minutes')"
  - "Online-handler extension: dispatch pending enrollments on state transition"

# Key files
created:
  - app/Events/EnrollmentStatusChanged.php
  - app/Console/Commands/CheckEnrollmentTimeoutsCommand.php
  - tests/Feature/Enrollment/AckHandlerTest.php
  - tests/Feature/Enrollment/EnrollmentStatusTest.php
modified:
  - app/Mqtt/Handlers/AckHandler.php
  - app/Mqtt/Handlers/OnlineOfflineHandler.php
  - routes/console.php

# Decisions
key-decisions:
  - "Cache::pull atomic retrieval prevents ACK replay attacks (T-4-08 mitigation)"
  - "DB::table raw query in tests to bypass Eloquent auto-timestamps for updated_at backdating"
  - "EnrollmentStatusChanged follows CameraStatusChanged broadcast pattern for consistency"

# Metrics
duration: 5min
completed: "2026-04-10T12:42:40Z"
tasks_completed: 2
tasks_total: 2
files_created: 4
files_modified: 3
tests_added: 16
tests_total: 161
---

# Phase 04 Plan 02: ACK Correlation & Enrollment Feedback Loop Summary

ACK handler correlates camera enrollment responses via cache-based messageId lookup, transitions enrollment status to enrolled/failed with error code translation, broadcasts EnrollmentStatusChanged on fras.alerts. Timeout command catches unresponsive cameras. Online handler auto-dispatches pending enrollments.

## What Was Built

### Task 1: AckHandler + EnrollmentStatusChanged Event
- **AckHandler** (`app/Mqtt/Handlers/AckHandler.php`): Replaced stub with full implementation. Extracts camera device_id from MQTT topic (`mqtt/face/{device_id}/Ack`), correlates ACK via `Cache::pull("enrollment-ack:{camera_id}:{messageId}")`, processes `AddSucInfo` (enrolled with timestamp + photo_hash) and `AddErrInfo` (failed with translated error code), dispatches `EnrollmentStatusChanged` for each transition.
- **EnrollmentStatusChanged** (`app/Events/EnrollmentStatusChanged.php`): Broadcast event implementing `ShouldBroadcast` on `PrivateChannel('fras.alerts')`. Constructor parameters: `personnel_id`, `camera_id`, `status`, `enrolled_at`, `last_error`. Follows `CameraStatusChanged` pattern.
- **8 tests** covering success/failure ACK processing, event dispatch, unknown/missing messageId edge cases, topic parsing, and broadcast channel verification.

### Task 2: Timeout Command + Online Handler Extension
- **CheckEnrollmentTimeoutsCommand** (`app/Console/Commands/CheckEnrollmentTimeoutsCommand.php`): Queries pending enrollments with `updated_at` older than `config('hds.enrollment.ack_timeout_minutes')`, marks them failed with descriptive timeout message, dispatches `EnrollmentStatusChanged` for each. Scheduled `everyMinute()` in `routes/console.php`.
- **OnlineOfflineHandler extension** (`app/Mqtt/Handlers/OnlineOfflineHandler.php`): After camera transitions from offline to online (`$isOnline && !$wasOnline`), queries pending `CameraEnrollment` records, chunks by `batch_size`, dispatches `EnrollPersonnelBatch` jobs. Guards prevent dispatch on offline or no-state-change events.
- **8 tests** covering timeout marking, enrolled record preservation, event dispatch on timeout, online dispatch, and guard conditions for offline/already-online.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Eloquent auto-timestamp overwriting test backdated updated_at**
- **Found during:** Task 2 GREEN phase
- **Issue:** `CameraEnrollment::withoutTimestamps()` did not prevent Eloquent from refreshing `updated_at` when calling `$enrollment->update(['updated_at' => ...])`, causing timeout tests to fail because records appeared fresh.
- **Fix:** Used `DB::table('camera_enrollments')->where('id', $enrollment->id)->update(...)` to bypass Eloquent timestamp handling entirely in tests.
- **Files modified:** `tests/Feature/Enrollment/EnrollmentStatusTest.php`
- **Commit:** cdeb44c

## Threat Mitigations Applied

| Threat ID | Mitigation |
|-----------|------------|
| T-4-08 (Tampering) | `Cache::pull` atomically retrieves and deletes correlation entry, preventing ACK replay |
| T-4-09 (Repudiation) | All status transitions logged via `Log::info` with camera_id and messageId |

## Self-Check: PASSED

All 7 files verified present. Both commit hashes found. All 10 content acceptance criteria confirmed.

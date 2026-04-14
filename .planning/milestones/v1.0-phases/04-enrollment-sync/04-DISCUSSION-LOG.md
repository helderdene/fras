# Phase 4: Enrollment Sync - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-10
**Phase:** 04-enrollment-sync
**Areas discussed:** Enrollment trigger, ACK & timeout handling, Retry & re-sync UX, Bulk status dashboard

---

## Enrollment Trigger

| Option | Description | Selected |
|--------|-------------|----------|
| Auto on every save (Recommended) | Creating or updating a personnel record always dispatches enrollment to all cameras. EditPersonsNew is idempotent. | ✓ |
| Only on relevant changes | Track changed fields and only dispatch if photo, name, custom_id, or person_type changed. | |
| Manual push only | Admin explicitly clicks a 'Sync' button after making changes. | |

**User's choice:** Auto on every save
**Notes:** Simple, no missed syncs. EditPersonsNew upsert semantics make redundant pushes safe.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-enroll all (Recommended) | Registering a camera dispatches enrollment jobs for all existing personnel automatically. | ✓ |
| Manual trigger required | New camera starts empty. Admin must manually sync. | |

**User's choice:** Auto-enroll all
**Notes:** Operator doesn't need to remember to sync after adding a camera.

---

## ACK & Timeout Handling

| Option | Description | Selected |
|--------|-------------|----------|
| Cache-based message IDs (Recommended) | Unique message ID per batch, stored in Laravel cache with TTL. AckHandler looks up cache. | ✓ |
| Database column for message ID | Add message_id column to camera_enrollments. More durable but adds DB writes. | |
| Redis pub/sub | Use Redis channels. More complex, requires Redis. | |

**User's choice:** Cache-based message IDs
**Notes:** Lightweight, TTL-based, matches the ack_timeout_minutes config.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Mark as failed with timeout error (Recommended) | Scheduled command checks pending enrollments older than timeout. Marks failed. Admin retries manually. | ✓ |
| Auto-retry once then fail | On timeout, auto re-dispatch once. Doubles traffic for offline cameras. | |
| Leave as pending indefinitely | Never auto-fail. Risk of stale pending state. | |

**User's choice:** Mark as failed with timeout error
**Notes:** Simple, predictable. No auto-retry on timeout.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Skip offline, mark pending (Recommended) | Don't dispatch to offline cameras. Create pending row. Auto-dispatch when camera comes online. | ✓ |
| Dispatch anyway, let it timeout | Send MQTT regardless. Message goes nowhere if offline, eventually times out. | |
| Queue for later delivery | Store intent, scheduled job retries for online cameras. | |

**User's choice:** Skip offline, mark pending
**Notes:** OnlineOfflineHandler triggers pending enrollment dispatch when camera comes back online.

---

## Retry & Re-sync UX

| Option | Description | Selected |
|--------|-------------|----------|
| Re-push single personnel (Recommended) | Retry button re-dispatches for just that one personnel to that one camera. | ✓ |
| Re-push batch for camera | Retry re-dispatches ALL failed enrollments for that camera. | |
| Re-push all failed everywhere | Single button re-dispatches all failed across all cameras. | |

**User's choice:** Re-push single personnel
**Notes:** Targeted, minimal MQTT traffic.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Force re-push to all cameras (Recommended) | Re-dispatches to ALL cameras regardless of current status. Resets to pending. | ✓ |
| Only re-push failed/pending | Skip already-enrolled cameras. Less traffic but won't fix silent corruption. | |
| Re-push with photo hash check | Compare hashes, only re-push where different. Most efficient but adds logic. | |

**User's choice:** Force re-push to all cameras
**Notes:** Ensures camera-side data matches server. Nuclear sync option.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Real-time via Reverb (Recommended) | Broadcast enrollment status changes on fras.alerts channel. Echo updates SyncStatusDot instantly. | ✓ |
| Poll on interval | Show page polls every 10-15 seconds. Simpler but less responsive. | |
| Manual refresh only | Sidebar updates only on page load. Simplest but poor UX. | |

**User's choice:** Real-time via Reverb
**Notes:** Consistent with camera liveness real-time pattern from Phase 2.

---

## Bulk Status Dashboard

| Option | Description | Selected |
|--------|-------------|----------|
| Section on personnel Index (Recommended) | Summary panel at top of personnel list showing per-camera enrollment counts. | ✓ |
| Dedicated enrollment page | Standalone page at /enrollment with camera-by-camera breakdown. | |
| Camera detail sidebar | Each camera detail page shows enrollment counts. Distributed. | |

**User's choice:** Section on personnel Index
**Notes:** Keeps enrollment visibility close to personnel management without a new page.

---

| Option | Description | Selected |
|--------|-------------|----------|
| View-only counts (Recommended) | Per-camera cards showing X/Y enrolled, Z failed. Clickable to camera detail. | ✓ |
| Bulk retry per camera | Each card has 'Retry all failed' button. | |
| Global sync all button | Single button forces full re-enrollment everywhere. | |

**User's choice:** View-only counts
**Notes:** Summary panel is informational. Retry actions are per-personnel on Show page.

---

## Claude's Discretion

- Enrollment job class structure
- WithoutOverlapping lock key naming
- Cache key format and TTL details
- Status enum/constants
- Migration update for status column
- Error code mapping implementation
- Bulk summary component design
- EnrollmentStatusChanged event structure
- OnlineOfflineHandler integration for pending dispatch
- Scheduled timeout check frequency

## Deferred Ideas

None — discussion stayed within phase scope

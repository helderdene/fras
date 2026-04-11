# Phase 5: Recognition & Alerting - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-11
**Phase:** 05-recognition-alerting
**Areas discussed:** Alert feed design, Detail modal & images, Audio & acknowledgment, Event classification

---

## Alert Feed Design

| Option | Description | Selected |
|--------|-------------|----------|
| Compact list rows | Dense rows with avatar, name, camera, severity tag, similarity score, and timestamp. Left border colored by severity. | ✓ |
| Card-based feed | Larger cards with face crop prominently displayed, more whitespace. Fewer alerts visible. | |
| Hybrid (cards for critical) | Critical alerts get card treatment, info/warning use compact rows. | |

**User's choice:** Compact list rows
**Notes:** Recommended for command center monitoring — maximizes visible alerts on screen.

---

### Animation

| Option | Description | Selected |
|--------|-------------|----------|
| Slide in from top | New alerts slide down from top, pushing existing alerts down. Brief highlight flash. | ✓ |
| Fade in at top | New alerts fade in. Subtler entrance, less motion. | |
| You decide | Claude picks the best animation. | |

**User's choice:** Slide in from top
**Notes:** Standard real-time feed behavior.

---

### Feed Depth

| Option | Description | Selected |
|--------|-------------|----------|
| Cap at ~50 recent | Most recent ~50 alerts. Older alerts in Event History. | ✓ |
| Unlimited scroll | All today's events with virtual scrolling. | |
| You decide | Claude determines the right cap. | |

**User's choice:** Cap at ~50 recent
**Notes:** Keeps feed performant and focused on current activity.

---

### Feed Filtering

| Option | Description | Selected |
|--------|-------------|----------|
| Severity toggle filters | Small toggle pills: All / Critical / Warning / Info. | ✓ |
| No filtering | Feed shows everything. Full filtering in Phase 7. | |
| You decide | Claude determines if filtering adds value. | |

**User's choice:** Severity toggle filters
**Notes:** Quick way to focus during busy periods.

---

## Detail Modal & Images

| Option | Description | Selected |
|--------|-------------|----------|
| Side-by-side images | Face crop left, scene image right with bbox overlay. Metadata below. Wide modal. | ✓ |
| Stacked layout | Face crop top, scene image below. Narrow modal. | |
| You decide | Claude picks the layout. | |

**User's choice:** Side-by-side images
**Notes:** Recommended for command center — wide modal with both images visible at once.

---

### Image Storage

| Option | Description | Selected |
|--------|-------------|----------|
| Date-partitioned dirs | storage/app/recognition/{YYYY-MM-DD}/faces/ and scenes/. | ✓ |
| Camera-partitioned dirs | storage/app/recognition/{camera_device_id}/faces/ and scenes/. | |
| You decide | Claude picks the structure. | |

**User's choice:** Date-partitioned dirs
**Notes:** Groups by date for easy Phase 7 retention cleanup.

---

### Missing Scene Image

| Option | Description | Selected |
|--------|-------------|----------|
| Placeholder with message | Gray placeholder box with 'Scene image not available' text. | ✓ |
| Hide scene panel entirely | Modal switches to single-column when no scene image. | |
| You decide | Claude handles the empty state. | |

**User's choice:** Placeholder with message
**Notes:** Consistent modal layout regardless of scene image availability.

---

## Audio & Acknowledgment

### Audio Behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Single chime per event | Short alert chime per critical event. Mute toggle available. | ✓ |
| Continuous until acknowledged | Sound loops until operator acknowledges. | |
| You decide | Claude picks the audio behavior. | |

**User's choice:** Single chime per event
**Notes:** Professional, non-intrusive for command center use.

---

### Acknowledge/Dismiss Flow

| Option | Description | Selected |
|--------|-------------|----------|
| Inline buttons on feed row | Small Ack/Dismiss icon buttons on hover. Single-click actions. | ✓ |
| Only in detail modal | Ack/dismiss only in the modal. Requires clicking into alert first. | |
| Both feed + modal | Available in both places. | |

**User's choice:** Inline buttons on feed row
**Notes:** Rapid response — operator can ack without opening modal.

---

### Browser Autoplay

| Option | Description | Selected |
|--------|-------------|----------|
| Explicit enable button | 'Enable Alert Sound' bell icon in feed header/status bar. User gesture to unlock audio. | ✓ |
| Silent attempt | Try to play on first event, show notification if blocked. | |
| You decide | Claude handles autoplay restrictions. | |

**User's choice:** Explicit enable button
**Notes:** Reliable approach — operator knows audio is active.

---

## Event Classification

### Severity Mapping

| Option | Description | Selected |
|--------|-------------|----------|
| Spec-based mapping | Critical=block-list, Warning=refused, Info=allow-list, Ignored=stranger/no-match. | ✓ |
| Simpler binary mapping | Critical=any block-list, Info=everything else. | |
| You decide | Claude determines based on spec. | |

**User's choice:** Spec-based mapping
**Notes:** Full mapping from verify_status + person_type combinations.

---

### Classification Implementation

| Option | Description | Selected |
|--------|-------------|----------|
| PHP enum | AlertSeverity enum with static fromEvent() method. Type-safe, testable. | ✓ |
| Model method | getSeverityAttribute() accessor on RecognitionEvent model. | |
| You decide | Claude picks the implementation approach. | |

**User's choice:** PHP enum
**Notes:** Clean, type-safe, single source of truth for classification logic.

---

### Manual Replay Events

| Option | Description | Selected |
|--------|-------------|----------|
| Completely invisible | Stored in DB, never surfaced in alert feed or audio. Only in Event History. | ✓ |
| Shown with replay badge | Appear in feed with 'Replay' badge, muted styling, no audio. | |
| You decide | Claude decides based on operator workflow. | |

**User's choice:** Completely invisible
**Notes:** Replays would create noise in the live feed. Phase 7 Event History provides access.

---

## Claude's Discretion

- RecPush payload parsing details and firmware quirk handling
- Base64 image decoding and storage implementation
- RecognitionEvent model structure and relationships
- RecognitionAlert broadcast event payload shape
- Alert chime sound file selection
- Bounding box overlay rendering approach
- Feed row component internal structure
- Personnel lookup strategy
- RecognitionHandler implementation structure

## Deferred Ideas

None — discussion stayed within phase scope.

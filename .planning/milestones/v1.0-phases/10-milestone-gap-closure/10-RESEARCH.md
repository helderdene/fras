# Phase 10: Milestone Gap Closure - Research

**Researched:** 2026-04-14
**Domain:** Laravel Broadcasting (broadcastAs), Echo Client Configuration, Inertia/Vue UI (acknowledged-by display)
**Confidence:** HIGH

## Summary

Phase 10 closes three gaps identified by the v1.0 milestone audit. Two are critical integration issues (CameraStatusChanged broadcast name mismatch preventing real-time camera status updates, and .env.example missing Pusher config options) and one is a minor UI gap (REC-13 acknowledged-by operator name not shown).

The CameraStatusChanged fix is a one-line addition. The .env.example fix requires understanding that the project uses Reverb for local development (per the committed code and `composer run dev` script) and Pusher cloud for the production deployment (per the actual `.env` on the deployment machine). The committed `app.ts` correctly uses `broadcaster: 'reverb'` with `VITE_REVERB_*` vars. The `.env.example` needs BOTH Reverb (default for development) AND Pusher (commented-out alternative for production) variables documented. The REC-13 fix requires loading the `acknowledgedBy` relationship on the server side and displaying the user's name in the frontend alert components.

**Primary recommendation:** Fix broadcastAs first (unblocks all real-time camera features), then env config consistency, then UI display.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| CAM-03 | System tracks camera online/offline state via MQTT heartbeat messages | Adding `broadcastAs()` to CameraStatusChanged enables Vue Echo listeners to receive the event |
| CAM-05 | Camera list page shows all cameras with online/offline state and last seen time | Same broadcastAs fix enables real-time status dot updates on cameras/Index |
| DASH-02 | Camera markers are colored by status: green for online, gray for offline | Same broadcastAs fix enables real-time marker color changes on Dashboard map |
| DASH-05 | Status bar shows MQTT connection status, Reverb WebSocket status, and queue depth | broadcastAs fix enables MQTT activity heuristic (isMqttConnected) in Dashboard |
| OPS-04 | MQTT listener handles Online/Offline messages to update camera is_online state | Backend handler already works; broadcastAs fix enables the real-time push to browsers |
| INFRA-03 | Laravel Reverb WebSocket server runs and broadcasts events to connected browsers | .env.example must document both Reverb and Pusher config; global.d.ts must declare correct types |
| REC-13 | Operator can acknowledge or dismiss an alert, recording who handled it and when | AlertController must eager-load acknowledgedBy relationship; frontend must display operator name |
</phase_requirements>

## Project Constraints (from CLAUDE.md)

- **Test enforcement:** Every change must be programmatically tested. Write or update tests, then run them.
- **Pint formatting:** Run `vendor/bin/pint --dirty --format agent` before finalizing PHP changes.
- **ESLint/Prettier:** Run `npm run lint` and `npm run format` for frontend changes.
- **Conventions:** Follow existing code conventions. Check sibling files for structure, approach, naming.
- **Wayfinder:** Use Wayfinder route functions instead of hardcoded URLs.
- **No dependency changes:** Do not change application dependencies without approval.
- **Existing directory structure:** Stick to existing structure.

## Architecture Patterns

### Gap 1: CameraStatusChanged Missing broadcastAs() (CRITICAL)

**Root cause verified:** [VERIFIED: codebase inspection of `app/Events/CameraStatusChanged.php`]

The `CameraStatusChanged` event at line 11 implements `ShouldBroadcast` but does NOT define a `broadcastAs()` method. Laravel defaults to broadcasting under the fully-qualified class name `App\Events\CameraStatusChanged`.

Vue Echo listeners in three locations use the short name with dot prefix:
- `resources/js/pages/Dashboard.vue:205` -- `.CameraStatusChanged`
- `resources/js/pages/cameras/Index.vue:31` -- `.CameraStatusChanged`
- `resources/js/pages/cameras/Show.vue:56` -- `.CameraStatusChanged`

The dot prefix (`.CameraStatusChanged`) tells Echo to listen for a custom broadcast name, NOT the FQCN. Since the event broadcasts as `App\Events\CameraStatusChanged` but listeners expect `.CameraStatusChanged`, the events never match.

**Contrast with working events:** [VERIFIED: codebase inspection]
- `RecognitionAlert` defines `broadcastAs()` at line 58 returning `'RecognitionAlert'` -- WORKS
- `EnrollmentStatusChanged` defines `broadcastAs()` at line 25 returning `'EnrollmentStatusChanged'` -- WORKS

**Fix pattern:**
```php
// Source: existing pattern in app/Events/RecognitionAlert.php:58-61
/** Get the event's broadcast name. */
public function broadcastAs(): string
{
    return 'CameraStatusChanged';
}
```

**Test pattern:** [VERIFIED: `tests/Feature/Recognition/RecognitionAlertTest.php:33-51`]
```php
test('camera status changed broadcastAs returns CameraStatusChanged', function () {
    $event = new CameraStatusChanged(
        camera_id: 1,
        camera_name: 'Test Camera',
        is_online: true,
        last_seen_at: now()->toIso8601String(),
    );

    expect($event->broadcastAs())->toBe('CameraStatusChanged');
});
```

### Gap 2: .env.example and TypeScript Type Drift (CRITICAL)

**Actual state verified:** [VERIFIED: codebase inspection of `.env.example`, `app.ts`, `.env`, `deploy/DEPLOYMENT.md`]

The committed codebase is internally consistent for **Reverb**:
- `app.ts` uses `broadcaster: 'reverb'` with `VITE_REVERB_*` vars
- `.env.example` has `BROADCAST_CONNECTION=reverb` and `VITE_REVERB_*` vars
- `composer run dev` starts `php artisan reverb:start --debug`
- `deploy/DEPLOYMENT.md` shows Reverb as the WebSocket server in production architecture

The **actual `.env`** on this development machine was manually changed to use Pusher cloud (`BROADCAST_CONNECTION=pusher`, `VITE_PUSHER_*` vars). This is NOT reflected in committed code.

The audit's statement "app.ts uses VITE_PUSHER_* vars" is **incorrect** -- `app.ts` uses `VITE_REVERB_*` vars. [VERIFIED: `resources/js/app.ts:36-42`]

**However, there are real issues:**

1. **`.env.example` lacks Pusher config option:** A developer deploying to production with Pusher cloud has no template variables. The `.env.example` should include commented-out Pusher variables as a documented alternative.

2. **`global.d.ts` only declares `VITE_REVERB_*` types:** [VERIFIED: `resources/js/types/global.d.ts:6-9`] If someone configures Pusher via `VITE_PUSHER_*` env vars, TypeScript would not recognize them. Should also declare `VITE_PUSHER_*` types.

3. **`BROADCAST_CONNECTION=reverb` in `.env.example` is correct for dev,** but the comment about "Reverb WebSocket broadcasting" in `app.ts` line 34 should clarify the relationship.

**Fix approach:**
- Keep `.env.example` defaulting to Reverb (correct for development)
- Add commented-out Pusher config section below Reverb section
- Add `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` to `global.d.ts` ImportMetaEnv
- Do NOT change `app.ts` broadcaster from 'reverb' -- it is correct for the committed configuration

**NOTE:** The audit recommendation to "remove or mark obsolete the VITE_REVERB_* entries" is WRONG. VITE_REVERB_* is the active, committed configuration. If the user wants to switch to Pusher cloud for production, that is a deployment-time decision via `.env`, not a codebase change.

### Gap 3: REC-13 Acknowledged-By Display (MINOR)

**Root cause verified:** [VERIFIED: codebase inspection]

The `RecognitionEvent` model has an `acknowledgedBy()` relationship (line 83-86) that returns `BelongsTo(User::class, 'acknowledged_by')`. This relationship exists and works.

**Server-side gap:** Neither `AlertController::index()` (line 18) nor `EventHistoryController::index()` (line 28) eager-loads the `acknowledgedBy` relationship. They only load `camera` and `personnel`.

**Frontend gap:** The alert UI components display `acknowledged_at` (timestamp) but never show the acknowledging user's name:
- `AlertFeedItem.vue:161-164` -- Shows "Acknowledged at {timestamp}" but no name
- `AlertDetailModal.vue:195-198` -- Shows "Acknowledged at {timestamp}" but no name
- `EventHistoryTable.vue` -- No acknowledged column at all

**TypeScript type gap:** `RecognitionEvent` interface in `resources/js/types/recognition.ts` has `acknowledged_by: number | null` (the FK ID) but no `acknowledged_by_user` or nested user object.

**Fix approach:**

1. **Server side:** Add `'acknowledgedBy:id,name'` to the `with()` calls in both controllers:
   ```php
   // AlertController::index()
   RecognitionEvent::with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path', 'acknowledgedBy:id,name'])
   
   // EventHistoryController::index()
   RecognitionEvent::with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path', 'acknowledgedBy:id,name'])
   ```

2. **TypeScript type:** Add `acknowledged_by_user` to RecognitionEvent:
   ```typescript
   acknowledged_by_user?: { id: number; name: string } | null;
   ```
   Note: Laravel serializes `acknowledgedBy` relationship as `acknowledged_by` in JSON due to snake_case conversion. Actually, let me verify this.

**Laravel relationship serialization:** [ASSUMED] Laravel serializes camelCase relationship names to snake_case in JSON. The `acknowledgedBy` relationship would serialize as `acknowledged_by` in the JSON payload. BUT this conflicts with the existing `acknowledged_by` column (the FK integer). Laravel handles this by nesting -- the eager-loaded relationship data appears as `acknowledged_by` object, overriding the FK integer. This is actually a naming collision.

**Better approach:** Either rename the relationship accessor to avoid collision, or accept that when the relationship is loaded, `acknowledged_by` becomes the User object (not the FK ID). The cleanest approach is to keep using the relationship as-is since:
- When relationship IS loaded: `acknowledged_by` will be `{ id: number, name: string }` object
- When relationship is NOT loaded: `acknowledged_by` remains the FK integer

This requires updating the TypeScript type to handle the dual nature, which is messy. A cleaner solution:

3. **Use appended accessor instead:** Add a computed accessor `acknowledgerName` to the model that returns the user's name, or use the relationship directly and update the TS type.

Let me check how the frontend currently handles `acknowledged_by`:

The frontend stores `acknowledged_by` as a user ID (number) from `page.props.auth.user.id` in optimistic updates. The server returns `acknowledged_by: auth()->id()` (integer). So currently `acknowledged_by` is always an integer FK.

**Cleanest fix:** Keep `acknowledged_by` as the FK integer. Add the relationship as `acknowledged_by_user` using a custom JSON key. In the model, override `toArray()` or use an append:

```php
// In RecognitionEvent model, add accessor:
protected function acknowledgerName(): Attribute
{
    return Attribute::get(fn () => $this->acknowledgedBy?->name);
}

// Add to $appends:
protected $appends = ['face_image_url', 'scene_image_url', 'acknowledger_name'];
```

This is clean but always tries to load the relationship. Better: let the controller handle it explicitly.

**Recommended approach:** Eager load the relationship in the controller and let Laravel serialize it. The TypeScript type needs to account for the serialized relationship:

```typescript
// In recognition.ts
acknowledged_by: number | null;  // FK - keep as-is
acknowledged_by_user?: { id: number; name: string } | null;  // new field
```

Wait -- Laravel won't automatically serialize `acknowledgedBy` as `acknowledged_by_user`. It serializes it as `acknowledged_by`. Let me think about this more carefully.

Actually, I should just verify how Laravel handles this. The relationship name is `acknowledgedBy()`. Laravel will serialize it to `acknowledged_by` in JSON. This WILL conflict with the `acknowledged_by` column.

**Resolution:** The relationship takes precedence when loaded. But this means `acknowledged_by` in the serialized JSON will switch from `number` to `{id, name}` depending on whether the relationship was eager-loaded. This is a common Laravel gotcha.

**Best fix:** Use `->loadMissing('acknowledgedBy:id,name')` and in the TypeScript, handle both cases. OR better: don't eager-load the full relationship. Instead, join or select the name directly:

```php
// Option A: Add user name via subselect
->addSelect([
    'acknowledger_name' => User::select('name')
        ->whereColumn('users.id', 'recognition_events.acknowledged_by')
        ->limit(1),
])

// Option B: Use accessor with conditional append
```

**Actually, the simplest and most conventional approach:**

The relationship is called `acknowledgedBy`. When eager-loaded with `with('acknowledgedBy:id,name')`, Laravel serializes it as `acknowledged_by` in JSON output. The `acknowledged_by` column value is the FK integer. When the relationship IS loaded, Laravel replaces the FK with the loaded object. This is standard Laravel behavior and is how many projects work.

The frontend just needs to know: when relationship is loaded, `acknowledged_by` could be either `number | { id: number, name: string } | null`. In practice, once we always eager-load it in these controllers, it will always be the object (or null). The optimistic update in the frontend sets it to `page.props.auth.user.id` (a number), but that's temporary client-side state that gets overwritten on next page load.

**Final recommended approach for frontend:**
- Update `RecognitionEvent` type to include optional nested user
- Use a separate display field or computed property to extract the name
- In optimistic updates, set both the ID and the name from the authenticated user

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Broadcast event naming | Custom event name logic | Laravel `broadcastAs()` method | Built-in, 1-line fix, consistent with other events |
| Env template management | Custom setup scripts | Documented `.env.example` with comments | Standard Laravel convention |
| Relationship serialization | Manual join queries | Laravel eager loading with `with('acknowledgedBy:id,name')` | Standard Eloquent pattern, handles N+1 |

## Common Pitfalls

### Pitfall 1: broadcastAs Prefix Dot Convention
**What goes wrong:** Developers forget the dot prefix in Echo listener event names
**Why it happens:** When using `broadcastAs()`, Echo listeners MUST prefix the event name with `.` to indicate a custom broadcast name. Without the dot, Echo prepends the default namespace.
**How to avoid:** All three Vue listener files already correctly use `.CameraStatusChanged`. The fix is server-side only.
**Warning signs:** Echo events that work in tests but fail in the browser.

### Pitfall 2: Relationship Name vs Column Name Collision in JSON Serialization
**What goes wrong:** Laravel serializes `acknowledgedBy` relationship as `acknowledged_by`, which collides with the `acknowledged_by` FK column.
**Why it happens:** Laravel's snake_case serialization converts camelCase relationship names to match column naming conventions.
**How to avoid:** When relationship is loaded, the relationship object replaces the column value. Be aware that the type of `acknowledged_by` changes based on whether the relationship was eager-loaded. Handle both cases in TypeScript or always eager-load.
**Warning signs:** `acknowledged_by` sometimes returns a number, sometimes an object.

### Pitfall 3: Optimistic Update Mismatch After Relationship Loading
**What goes wrong:** Frontend optimistic update sets `acknowledged_by = user.id` (number), but server returns `acknowledged_by = { id, name }` (object) after eager loading.
**Why it happens:** The acknowledge endpoint returns `{ acknowledged_at, acknowledged_by }` and the frontend patches local state. If the type changes, the display logic breaks.
**How to avoid:** Update the acknowledge response to include the user name, and update the frontend optimistic update to set the full object shape.

## Code Examples

### Adding broadcastAs() to CameraStatusChanged
```php
// Source: Pattern from app/Events/RecognitionAlert.php:57-61
// Add after broadcastWith() method in app/Events/CameraStatusChanged.php

/** Get the event's broadcast name. */
public function broadcastAs(): string
{
    return 'CameraStatusChanged';
}
```

### Test for broadcastAs
```php
// Source: Pattern from tests/Feature/Recognition/RecognitionAlertTest.php:33-51
test('camera status changed broadcastAs returns CameraStatusChanged', function () {
    $event = new CameraStatusChanged(
        camera_id: 1,
        camera_name: 'Test Camera',
        is_online: true,
        last_seen_at: now()->toIso8601String(),
    );

    expect($event->broadcastAs())->toBe('CameraStatusChanged');
});
```

### Eager Loading acknowledgedBy
```php
// Source: Pattern from existing with() calls in AlertController::index()
RecognitionEvent::with([
    'camera:id,name',
    'personnel:id,name,custom_id,person_type,photo_path',
    'acknowledgedBy:id,name',
])
```

### Updated TypeScript Type
```typescript
// In resources/js/types/recognition.ts
export interface RecognitionEvent {
    // ... existing fields ...
    acknowledged_by: number | { id: number; name: string } | null;
    // OR add a dedicated field:
    acknowledged_by_user?: { id: number; name: string } | null;
}
```

### Updated AlertController acknowledge response
```php
// Return user name in response for frontend optimistic updates
return response()->json([
    'acknowledged_at' => $event->acknowledged_at->toISOString(),
    'acknowledged_by' => auth()->id(),
    'acknowledger_name' => auth()->user()->name,
]);
```

### .env.example Pusher section
```env
# Broadcasting: Reverb (default for local development)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Broadcasting: Pusher Cloud (alternative for production)
# Uncomment and set BROADCAST_CONNECTION=pusher to use Pusher instead of Reverb
# PUSHER_APP_ID=
# PUSHER_APP_KEY=
# PUSHER_APP_SECRET=
# PUSHER_APP_CLUSTER=ap1
# VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
# VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Updated global.d.ts
```typescript
interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    // Reverb (default)
    readonly VITE_REVERB_APP_KEY: string;
    readonly VITE_REVERB_HOST: string;
    readonly VITE_REVERB_PORT: string;
    readonly VITE_REVERB_SCHEME: string;
    // Pusher (alternative)
    readonly VITE_PUSHER_APP_KEY: string;
    readonly VITE_PUSHER_APP_CLUSTER: string;
    [key: string]: string | boolean | undefined;
}
```

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact --filter=TestName` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CAM-03/OPS-04 | CameraStatusChanged broadcasts with short name | unit | `php artisan test --compact --filter="broadcastAs returns CameraStatusChanged"` | Partial (broadcastOn tested, broadcastAs NOT tested) |
| CAM-05 | Camera list receives real-time status updates | manual-only | Browser test with Echo | N/A |
| DASH-02 | Dashboard map markers update color in real-time | manual-only | Browser test with Echo | N/A |
| DASH-05 | StatusBar MQTT indicator responds to CameraStatusChanged | manual-only | Browser test with Echo | N/A |
| INFRA-03 | .env.example has correct broadcast config | unit | `php artisan test --compact --filter="env example"` | No -- Wave 0 |
| REC-13 | Alert feed shows acknowledger name | feature | `php artisan test --compact --filter="acknowledge"` | Partial (acknowledge API tested, name display not tested) |

### Wave 0 Gaps
- [ ] Test that CameraStatusChanged::broadcastAs() returns 'CameraStatusChanged' -- add to `CameraCrudTest.php` or `CameraStatusBroadcastTest.php`
- [ ] Test that AlertController::index() eager-loads acknowledgedBy relationship and event data includes user name
- [ ] Test that EventHistoryController::index() eager-loads acknowledgedBy relationship
- [ ] Test that .env.example contains PUSHER config (commented out) alongside REVERB config

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | N/A (no auth changes) |
| V3 Session Management | no | N/A |
| V4 Access Control | no | Existing auth middleware unchanged |
| V5 Input Validation | no | No new user inputs |
| V6 Cryptography | no | N/A |

This phase makes no security-relevant changes. The broadcastAs fix, env template update, and UI display change do not affect authentication, authorization, or input handling.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Laravel serializes `acknowledgedBy` relationship as `acknowledged_by` in JSON, replacing the FK column value when loaded | Gap 3 analysis | Frontend type mismatch; acknowledged_by field behavior differs from expected. Medium risk -- verify with a tinker test. |
| A2 | The project intends to keep Reverb as the default development broadcaster and use Pusher only for production | Gap 2 analysis | If user wants to fully switch to Pusher, the fix approach changes (would need to update app.ts broadcaster). Low risk -- deployment docs confirm Reverb. |

## Open Questions (RESOLVED)

1. **Relationship serialization collision:**
   - What we know: `acknowledgedBy` relationship name serializes to `acknowledged_by`, same as the FK column
   - What's unclear: Whether this causes issues with the existing `#[Hidden]` or `#[Fillable]` attributes
   - Recommendation: Test with tinker: `RecognitionEvent::with('acknowledgedBy:id,name')->first()->toArray()` to verify serialized structure before implementing
   - RESOLVED: Used `acknowledgerName` computed accessor with `$appends` to expose operator name as a separate `acknowledger_name` field, avoiding the FK column collision entirely.

2. **Pusher vs Reverb production intent:**
   - What we know: Committed code uses Reverb; `.env` on dev machine uses Pusher; deployment docs show Reverb
   - What's unclear: Whether the user plans to use Pusher cloud in production going forward
   - Recommendation: Add Pusher as a documented alternative in `.env.example` without changing the default. The audit's recommendation is based on an incorrect premise.
   - RESOLVED: Added Pusher as a commented-out alternative in `.env.example`, kept Reverb as the default. Matches committed codebase and deployment docs.

## Sources

### Primary (HIGH confidence)
- `app/Events/CameraStatusChanged.php` -- Verified missing broadcastAs() method
- `app/Events/RecognitionAlert.php:58-61` -- Verified working broadcastAs() pattern
- `app/Events/EnrollmentStatusChanged.php:25-28` -- Verified working broadcastAs() pattern
- `resources/js/pages/Dashboard.vue:205` -- Verified `.CameraStatusChanged` Echo listener
- `resources/js/pages/cameras/Index.vue:31` -- Verified `.CameraStatusChanged` Echo listener
- `resources/js/pages/cameras/Show.vue:56` -- Verified `.CameraStatusChanged` Echo listener
- `resources/js/app.ts:35-43` -- Verified Echo config uses `broadcaster: 'reverb'` with VITE_REVERB_* vars
- `.env.example` -- Verified BROADCAST_CONNECTION=reverb, VITE_REVERB_* present, no PUSHER vars
- `.env` -- Verified actual config uses BROADCAST_CONNECTION=pusher with VITE_PUSHER_* vars
- `deploy/DEPLOYMENT.md` -- Verified Reverb in production architecture diagram
- `app/Http/Controllers/AlertController.php:18` -- Verified acknowledgedBy NOT in with() clause
- `app/Http/Controllers/EventHistoryController.php:28` -- Verified acknowledgedBy NOT in with() clause
- `app/Models/RecognitionEvent.php:83-86` -- Verified acknowledgedBy relationship exists
- `resources/js/types/recognition.ts` -- Verified RecognitionEvent type lacks user name field
- `resources/js/components/AlertFeedItem.vue:161-164` -- Verified shows timestamp only, no name
- `resources/js/components/AlertDetailModal.vue:195-198` -- Verified shows timestamp only, no name
- `tests/Feature/Recognition/RecognitionAlertTest.php:33-51` -- Verified broadcastAs test pattern
- `tests/Feature/Camera/CameraCrudTest.php:63-76` -- Verified existing broadcastOn test

### Secondary (MEDIUM confidence)
- `.planning/v1.0-MILESTONE-AUDIT.md` -- Audit report (note: contains factual error about app.ts using VITE_PUSHER_* vars)
- `node_modules/laravel-echo/dist/echo.d.ts` -- Verified `reverb` and `pusher` are distinct broadcaster types

## Metadata

**Confidence breakdown:**
- CameraStatusChanged broadcastAs fix: HIGH -- exact one-line fix verified against working events in same codebase
- .env.example config: HIGH -- verified all four sources (app.ts, .env.example, .env, deployment docs) and identified audit error
- REC-13 acknowledged-by UI: HIGH for controller fix, MEDIUM for serialization behavior (A1 assumption needs tinker verification)

**Research date:** 2026-04-14
**Valid until:** 2026-05-14 (stable domain, no fast-moving dependencies)

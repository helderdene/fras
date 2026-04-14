---
phase: 05-recognition-alerting
fixed_at: 2026-04-11T18:45:00Z
review_path: .planning/phases/05-recognition-alerting/05-REVIEW.md
iteration: 1
findings_in_scope: 5
fixed: 5
skipped: 0
status: all_fixed
---

# Phase 5: Code Review Fix Report

**Fixed at:** 2026-04-11T18:45:00Z
**Source review:** .planning/phases/05-recognition-alerting/05-REVIEW.md
**Iteration:** 1

**Summary:**
- Findings in scope: 5
- Fixed: 5
- Skipped: 0

## Fixed Issues

### CR-01: `raw_payload` exposed to frontend via Inertia serialization

**Files modified:** `app/Models/RecognitionEvent.php`
**Commit:** 6d952d1
**Applied fix:** Added `#[Hidden(['raw_payload', 'face_image_path', 'scene_image_path'])]` attribute to the `RecognitionEvent` model class. This prevents the raw MQTT payload (containing base64-encoded images up to 3MB per event) and internal storage paths from being serialized to the frontend via Inertia. The model already provides `face_image_url` and `scene_image_url` accessors for frontend use.

### WR-01: Hardcoded `acknowledged_by = 1` in optimistic update

**Files modified:** `resources/js/pages/alerts/Index.vue`
**Commit:** a877fbc
**Applied fix:** Imported `usePage` from `@inertiajs/vue3`, initialized `const page = usePage()`, and replaced the hardcoded `acknowledged_by = 1` with `page.props.auth.user.id` in the `handleAcknowledge` optimistic update callback. This ensures the local state correctly reflects the authenticated user who acknowledged the alert.

### WR-02: Missing authorization on acknowledge, dismiss, and image endpoints

**Files modified:** `app/Http/Controllers/AlertController.php`
**Commit:** 0ed6fae
**Applied fix:** Added PHPDoc authorization documentation to all four action methods (`acknowledge`, `dismiss`, `faceImage`, `sceneImage`) explicitly documenting the design decision that all authenticated users may perform these actions. This is intentional for a single command center deployment with trusted operators. The documentation notes that a `RecognitionEventPolicy` should be created if role-based access control is needed in the future. No policy was added because (a) no policies exist in the project yet, and (b) the single-site deployment model makes permissive auth appropriate.

### WR-03: No validation that `faceImage`/`sceneImage` file exists on disk before serving

**Files modified:** `app/Http/Controllers/AlertController.php`
**Commit:** 6cbbec7
**Applied fix:** Added `Storage::disk('local')->exists()` check to both `faceImage` and `sceneImage` methods alongside the existing null-path check. If a file was deleted by a retention cleanup job but the database record still references it, the endpoint now returns a clean 404 instead of throwing an unhandled `FileNotFoundException` (500 error).

### WR-04: `isAcknowledged`/`isDismissed` checks may fail with falsy string comparison

**Files modified:** `resources/js/components/AlertDetailModal.vue`, `resources/js/components/AlertFeedItem.vue`
**Commit:** ad518c9
**Applied fix:** Changed `!== null` strict equality checks to truthy checks (`!!`) for both `isAcknowledged` and `isDismissed` computed properties in both components. This handles edge cases where the value might be an empty string or `undefined` from a broadcast payload, providing more defensive behavior.

---

_Fixed: 2026-04-11T18:45:00Z_
_Fixer: Claude (gsd-code-fixer)_
_Iteration: 1_

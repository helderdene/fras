---
phase: 03-personnel-management
reviewed: 2026-04-10T00:00:00Z
depth: standard
files_reviewed: 20
files_reviewed_list:
  - app/Http/Controllers/PersonnelController.php
  - app/Http/Requests/Personnel/StorePersonnelRequest.php
  - app/Http/Requests/Personnel/UpdatePersonnelRequest.php
  - app/Models/Personnel.php
  - app/Services/PhotoProcessor.php
  - database/factories/PersonnelFactory.php
  - database/seeders/DatabaseSeeder.php
  - database/seeders/PersonnelSeeder.php
  - resources/js/components/AppHeader.vue
  - resources/js/components/AppSidebar.vue
  - resources/js/components/SyncStatusDot.vue
  - resources/js/pages/personnel/Create.vue
  - resources/js/pages/personnel/Edit.vue
  - resources/js/pages/personnel/Index.vue
  - resources/js/pages/personnel/Show.vue
  - resources/js/types/index.ts
  - resources/js/types/personnel.ts
  - routes/web.php
  - tests/Feature/Personnel/PersonnelCrudTest.php
  - tests/Feature/Personnel/PhotoProcessorTest.php
findings:
  critical: 1
  warning: 5
  info: 4
  total: 10
status: issues_found
---

# Phase 03: Code Review Report

**Reviewed:** 2026-04-10
**Depth:** standard
**Files Reviewed:** 20
**Status:** issues_found

## Summary

This phase implements the Personnel CRUD module — controller, form requests, model, photo processing service, Vue pages, factories, seeders, and tests. The overall structure is well-aligned with the project's established patterns: Form Requests for validation, Wayfinder for typed routes, `<script setup lang="ts">`, and Pest feature tests.

One critical bug was found: the `update` action deletes the old photo before the new upload is processed, meaning a storage failure mid-operation leaves the record permanently without a photo. Five warnings cover a missing authorization layer, an unsafe `file_get_contents` call, an `encodeUsingFileExtension` API mismatch, a soft-delete gap in the delete test, and a hardcoded sync status. Four info items address minor quality points.

---

## Critical Issues

### CR-01: Old photo deleted before new upload is confirmed — data loss on storage failure

**File:** `app/Http/Controllers/PersonnelController.php:72-74`

**Issue:** In `update()`, `PhotoProcessor::delete()` is called on the existing photo **before** `process()` stores the new one. If `Storage::disk('public')->put()` fails inside `process()` (e.g., disk full, permissions error), the old photo file is gone and `photo_path` / `photo_hash` in the database are never updated — the record ends up in an inconsistent state with a dead `photo_path` or the old path retained while the file no longer exists.

```php
// Current — delete happens before new file is confirmed stored
app(PhotoProcessor::class)->delete($personnel->photo_path);
$result = app(PhotoProcessor::class)->process($request->file('photo'));
```

**Fix:** Process (store) the new photo first, then delete the old one only after the new path is in hand:

```php
$oldPath = $personnel->photo_path;
$result = app(PhotoProcessor::class)->process($request->file('photo'));
$data = array_merge($data, $result);

// Only delete old file after new file is confirmed stored
app(PhotoProcessor::class)->delete($oldPath);
```

---

## Warnings

### WR-01: No authorization — any authenticated user can modify any personnel record

**File:** `app/Http/Controllers/PersonnelController.php:14-97`
**Also:** `app/Http/Requests/Personnel/StorePersonnelRequest.php:12`, `app/Http/Requests/Personnel/UpdatePersonnelRequest.php:12`

**Issue:** Both Form Requests return `return true;` in `authorize()`, and no policy or gate is used in the controller. All personnel mutations (`store`, `update`, `destroy`) are accessible to every authenticated user, including unverified or low-privilege users. The project already has an `auth` + `verified` middleware group on the route, which handles authentication, but there is no role/permission check for write operations on personnel records.

This is partially mitigated by the single-operator nature of the facility, but it is a gap if multiple user roles are ever added.

**Fix:** Either register a `PersonnelPolicy` and call `$this->authorize()` in each controller method, or add role-based gate checks. At minimum, document the intentional absence of granular authorization in a comment so future maintainers don't assume it exists.

---

### WR-02: `file_get_contents($file->path())` is unsafe for large uploads

**File:** `app/Services/PhotoProcessor.php:23`

**Issue:** `file_get_contents($file->path())` reads the entire uploaded file into a PHP string in memory. The upload validation allows files up to 10 MB (`max:10240`). Loading 10 MB as a PHP string before Intervention processes it can exhaust memory on constrained environments and does not use PHP's stream abstraction. Intervention Image v3 accepts a file path directly via `Image::read()`.

```php
$image = Image::decode(file_get_contents($file->path()));
```

**Fix:** Use `Image::read()` (Intervention v3 API) with the file path directly to avoid loading the full file into a string:

```php
$image = Image::read($file->path());
```

If `Image::read()` is not available on the installed version, use `Image::make($file->path())` (v2 compat) or pass the SplFileInfo object. Verify against the installed Intervention version with `search-docs`.

---

### WR-03: `encodeUsingFileExtension('jpg', quality: $quality)` — incorrect API call

**File:** `app/Services/PhotoProcessor.php:27, 30`

**Issue:** `encodeUsingFileExtension` accepts a **file extension string** to determine the encoder, but it does not accept a `quality` named argument directly in all Intervention v3 releases. The correct Intervention v3 method to encode as JPEG with a quality parameter is `toJpeg(quality: $quality)` or `encodeByExtension('jpg')->quality($quality)`. Passing an unsupported named argument silently falls back to the default quality in some versions, meaning the quality-reduction loop (`while … $quality -= 10`) may never actually reduce the output size.

```php
// Current — quality parameter may be silently ignored
$encoded = $image->encodeUsingFileExtension('jpg', quality: $quality);
```

**Fix:** Use the explicit JPEG encoder with quality control:

```php
$encoded = $image->toJpeg(quality: $quality);
```

If `toJpeg` is not available, use `$image->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: $quality))`. Confirm the exact API for the installed Intervention v3 version.

---

### WR-04: `can delete a personnel record` test does not assert the database record is removed

**File:** `tests/Feature/Personnel/PersonnelCrudTest.php:211-224`

**Issue:** The delete test asserts `Personnel::find($personnel->id)->toBeNull()`, which is correct. However, if the `Personnel` model were to use `SoftDeletes` in the future, `find()` would exclude soft-deleted records and this assertion would still pass — masking incomplete hard-delete behavior. More importantly, the test does not use `assertModelMissing()` which is the idiomatic Laravel/Pest assertion for confirming a model no longer exists in the database. This is a reliability concern.

```php
// Current
expect(Personnel::find($personnel->id))->toBeNull()
```

**Fix:** Replace with the model-aware assertion:

```php
$this->assertModelMissing($personnel);
```

---

### WR-05: Sync status is hardcoded to `'not-synced'` throughout the UI

**File:** `resources/js/pages/personnel/Index.vue:175`
**Also:** `resources/js/pages/personnel/Show.vue:236`

**Issue:** `<SyncStatusDot status="not-synced" />` is hardcoded in both the index table and the per-camera enrollment list on the show page. The `SyncStatusDot` component correctly supports `synced`, `pending`, `failed`, and `not-synced` states, but the actual enrollment status from the backend is never passed down. If enrollment is later wired up but this prop is forgotten, the UI will always show "Not synced" regardless of true state — a silent regression that could mislead operators about camera enrollment status.

**Fix:** Pass the real sync status as a prop from the backend. Add an `enrollment_status` field to the personnel/camera data shape (or a pivot relationship) and bind it:

```vue
<SyncStatusDot :status="p.enrollment_status ?? 'not-synced'" />
```

This is a known placeholder at this phase, but should be tracked as a warning since it affects the core value proposition (operators must not miss critical events).

---

## Info

### IN-01: `app()` helper used instead of constructor injection for `PhotoProcessor`

**File:** `app/Http/Controllers/PersonnelController.php:36, 73, 74, 89`

**Issue:** `app(PhotoProcessor::class)` is called inline three times (store, update×2, destroy). The CLAUDE.md architecture note and the laravel-best-practices skill both prefer constructor injection over the `app()` helper inside controller methods, because it makes dependencies explicit and testable without resolving from the container at call time.

**Fix:** Inject `PhotoProcessor` via the constructor:

```php
public function __construct(private readonly PhotoProcessor $photoProcessor) {}
```

Then replace `app(PhotoProcessor::class)->process(...)` with `$this->photoProcessor->process(...)`.

---

### IN-02: `personnel/Index.vue` checks `props.personnel.length === 0` and `filtered.length === 0` separately — minor inconsistency

**File:** `resources/js/pages/personnel/Index.vue:61, 122`

**Issue:** The empty state card (line 61) shows when `props.personnel.length === 0` (no records at all), while the "no match" row (line 122) shows when `filtered.length === 0 && search !== ''`. The condition on the empty state uses the raw `props.personnel` rather than a computed value. This is fine currently, but `filtered` already guards the `v-else` block so the pattern is slightly asymmetric. No bug, but a minor readability concern.

**Fix:** No code change required — the logic is correct. If the condition is ever extended, unify both checks under the `filtered` computed.

---

### IN-03: `DatabaseSeeder` has a commented-out block and a dead import comment

**File:** `database/seeders/DatabaseSeeder.php:6`

**Issue:** `// use Illuminate\Database\Console\Seeds\WithoutModelEvents;` is a commented-out import left over from the starter kit scaffold. It is dead code.

**Fix:** Remove the commented-out line:

```php
// Remove this line:
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
```

---

### IN-04: `AppHeader.vue` includes Repository and Documentation links pointing to the Laravel starter kit, not the FRAS project

**File:** `resources/js/components/AppHeader.vue:78-88`

**Issue:** `rightNavItems` links to `https://github.com/laravel/vue-starter-kit` and `https://laravel.com/docs/starter-kits#vue`. These are starter kit defaults, not FRAS-specific resources. They appear in the header for all authenticated users.

**Fix:** Update the links to point to the FRAS repository and any project-specific documentation, or remove the items if no external links are needed for the command-center UI.

---

_Reviewed: 2026-04-10_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

---
phase: 03-personnel-management
verified: 2026-04-10T12:00:00Z
status: human_needed
score: 4/5
overrides_applied: 0
re_verification: false
deferred:
  - truth: "Personnel list sync status dot shows green (all enrolled), amber (pending), red (failed) based on real enrollment state"
    addressed_in: "Phase 4"
    evidence: "Phase 4 SC1: 'enrollment status transitions from pending to enrolled on ACK success'; SC3: 'Failed enrollments display operator-friendly error messages'; SC5: 'Bulk enrollment status dashboard shows per-camera counts'. SyncStatusDot component supports all four states in Phase 3; Phase 4 wires the real enrollment data."
human_verification:
  - test: "Photo upload creates personnel record with preprocessed photo visible in the UI"
    expected: "Submit the Create form with a JPEG over 1080px; record appears in personnel list with an avatar thumbnail rendered from the resized/stored photo URL"
    why_human: "End-to-end multipart file upload through Inertia Form + PhotoProcessor + storage symlink cannot be verified without a running browser session"
  - test: "Edit form pre-fills all fields from existing personnel record"
    expected: "Navigate to an existing personnel record's edit page; all text inputs, select dropdowns, birthday date input, and photo dropzone display the current values"
    why_human: "Select default-value binding and PhotoDropzone edit-mode display require visual inspection in a running app"
  - test: "Drag-and-drop on PhotoDropzone changes border color and shows preview"
    expected: "Dragging a JPEG over the dropzone shows primary-color border; dropping shows full-cover thumbnail preview with X remove button"
    why_human: "Drag-and-drop UX and FileReader preview are browser-only behaviors that cannot be verified without DOM interaction"
  - test: "Delete personnel removes both the database record and the stored photo file"
    expected: "Confirm deletion dialog on Show page; after redirect to Index, the record is gone and the photo file is absent from storage/app/public/personnel/"
    why_human: "Cleanup of photo file on delete requires an actual database record with a real stored file"
---

# Phase 3: Personnel Management Verification Report

**Phase Goal:** Admin can manage a personnel roster with photos that are automatically preprocessed to meet camera enrollment constraints
**Verified:** 2026-04-10T12:00:00Z
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Admin can create, edit, and delete personnel records with name, custom ID, person type (allow/block), and photo | VERIFIED | PersonnelController has all 7 resource methods; StorePersonnelRequest requires photo, name, custom_id, person_type; UpdatePersonnelRequest makes photo nullable; destroy() calls PhotoProcessor.delete(); 17 CRUD tests pass |
| 2 | Photo upload uses a dropzone with client-side preview and displays size constraint help text | VERIFIED | PhotoDropzone.vue: dashed border dropzone, FileReader preview, "JPEG or PNG, max 1MB after processing. Photos are automatically resized." help text, inline errors for type/size violations; wired into Create.vue and Edit.vue |
| 3 | Uploaded photos are automatically resized to max 1080p, compressed to JPEG under 1MB, and have MD5 hash computed | VERIFIED | PhotoProcessor.process() reads config('hds.photo.max_dimension'), uses Image::decode + scaleDown + encodeUsingFileExtension with quality fallback loop, computes md5(); 10 PhotoProcessor tests pass |
| 4 | Personnel list shows avatar, name, custom ID, list type, and sync status dot (green/amber/red) | PARTIAL | Index.vue renders avatar (AvatarImage/AvatarFallback), name Link, custom_id (monospace), Badge (destructive/secondary), and SyncStatusDot — but all dots show hardcoded `status="not-synced"` (gray). Real green/amber/red requires enrollment data from Phase 4. |
| 5 | Personnel detail page shows edit form on the left and per-camera enrollment status sidebar on the right | VERIFIED | Show.vue renders 5-column grid: lg:col-span-3 card with 200px photo, grouped info, edit/delete actions; lg:col-span-2 enrollment sidebar listing all cameras with SyncStatusDot (not-synced placeholder). Show page is read-only info; Edit page is the form. |

**Score:** 4/5 truths fully verified (SC4 partially met; sync dot colors deferred to Phase 4)

### Deferred Items

Items not yet met but explicitly addressed in later milestone phases.

| # | Item | Addressed In | Evidence |
|---|------|-------------|----------|
| 1 | Sync status dot shows real green/amber/red based on enrollment state | Phase 4 | Phase 4 SC1: "enrollment status transitions from pending to enrolled on ACK success"; SC3: "Failed enrollments display operator-friendly error messages". SyncStatusDot component is built with all four status values; Phase 4 will wire the actual enrollment data. |

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `app/Models/Personnel.php` | Eloquent model with fillable, casts, photo_url accessor | VERIFIED | Has #[Fillable(...)], casts() for person_type/gender/birthday, photoUrl() Attribute with $appends = ['photo_url'], explicit $table = 'personnel' |
| `app/Http/Controllers/PersonnelController.php` | Resource controller with all 7 methods | VERIFIED | All 7 methods: index/create/show/edit/store/update/destroy; store/update delegate to app(PhotoProcessor::class)->process(); destroy calls ->delete() |
| `app/Services/PhotoProcessor.php` | Photo preprocessing: orient, resize, compress, hash | VERIFIED | process() uses Image::decode, orient, scaleDown, encodeUsingFileExtension with quality fallback loop, md5(); delete() handles null/missing paths |
| `app/Http/Requests/Personnel/StorePersonnelRequest.php` | Create validation with required photo | VERIFIED | photo: ['required', 'image', 'mimes:jpeg,png', 'max:10240']; all fields validated |
| `app/Http/Requests/Personnel/UpdatePersonnelRequest.php` | Update validation with optional photo | VERIFIED | photo: ['nullable', 'image', 'mimes:jpeg,png', 'max:10240']; custom_id uses Rule::unique()->ignore() |
| `resources/js/types/personnel.ts` | TypeScript Personnel interface | VERIFIED | Exports Personnel interface with all 13 fields including photo_url; barrel-exported from types/index.ts |
| `resources/js/components/SyncStatusDot.vue` | Sync status indicator with four states | VERIFIED | Props: status: 'synced' \| 'pending' \| 'failed' \| 'not-synced'; dot colors: emerald/amber/red/neutral; aria-label on dot span |
| `resources/js/components/PhotoDropzone.vue` | Drag-and-drop photo upload with preview and edit mode | VERIFIED | border-2 border-dashed dropzone; @dragenter.prevent/@drop.prevent; FileReader preview; DataTransfer API; "Please select a JPEG or PNG image." / "File is too large." errors; "Replace photo" overlay for edit mode; hidden file input with :name="name" |
| `resources/js/pages/personnel/Index.vue` | Personnel list with search and table | VERIFIED | Head title="Personnel"; search ref + computed filtered; Avatar/AvatarImage/AvatarFallback; Badge destructive/secondary; SyncStatusDot status="not-synced"; empty state; no-search-results row |
| `resources/js/pages/personnel/Show.vue` | Personnel detail with enrollment sidebar | VERIFIED | size-[200px] Avatar; setLayoutProps breadcrumbs; Enrollment Status card; SyncStatusDot status="not-synced" per camera; "No cameras registered" empty state; PersonnelController.destroy.form() delete dialog |
| `resources/js/pages/personnel/Create.vue` | Create form with 4 grouped sections | VERIFIED | PersonnelController.store.form(); PhotoDropzone name="photo"; Identity/Details/Contact sections with Separators; all field inputs named correctly |
| `resources/js/pages/personnel/Edit.vue` | Edit form pre-filled with existing data | VERIFIED | PersonnelController.update.form(props.personnel); :current-photo-url="props.personnel.photo_url ?? undefined"; :default-value on all inputs; Select :default-value with String() cast; setLayoutProps dynamic breadcrumbs |
| `tests/Feature/Personnel/PersonnelCrudTest.php` | CRUD + validation tests | VERIFIED | 229 lines; 17 tests covering list/create/show/edit/store/update/delete, photo cleanup on delete, auth requirement, validation rules |
| `tests/Feature/Personnel/PhotoProcessorTest.php` | Photo preprocessing tests | VERIFIED | 138 lines; 10 tests covering resize, no-upscale, JPEG output, MD5 hash, storage path, quality fallback, delete, null delete |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `PersonnelController.php` | `PhotoProcessor.php` | `app(PhotoProcessor::class)->process()` in store/update | WIRED | store() and update() both call app(PhotoProcessor::class)->process(); destroy() calls ->delete() |
| `PersonnelController.php` | `StorePersonnelRequest.php` | type-hint on store() | WIRED | `public function store(StorePersonnelRequest $request)` confirmed in controller |
| `routes/web.php` | `PersonnelController.php` | Route::resource | WIRED | `Route::resource('personnel', PersonnelController::class)` inside auth+verified middleware group; all 7 routes confirmed via route:list |
| `Index.vue` | `SyncStatusDot.vue` | import and `<SyncStatusDot status="not-synced" />` | WIRED | Import confirmed; used in table rows |
| `Show.vue` | `SyncStatusDot.vue` | import and `<SyncStatusDot status="not-synced" />` | WIRED | Import confirmed; used in enrollment sidebar per-camera rows |
| `AppSidebar.vue` | `@/routes/personnel` | `index as personnelIndex` Wayfinder import | WIRED | `import { index as personnelIndex } from '@/routes/personnel'`; used in mainNavItems href |
| `Create.vue` | `PhotoDropzone.vue` | component import and `<PhotoDropzone name="photo" />` | WIRED | Import confirmed; used in Photo section with name="photo" |
| `Edit.vue` | `PhotoDropzone.vue` | component import with `:current-photo-url` prop | WIRED | Import confirmed; `:current-photo-url="props.personnel.photo_url ?? undefined"` |
| `Create.vue` | `PersonnelController.store.form()` | Wayfinder form binding on Form element | WIRED | `v-bind="PersonnelController.store.form()"` confirmed |
| `Edit.vue` | `PersonnelController.update.form(personnel)` | Wayfinder form binding with model | WIRED | `v-bind="PersonnelController.update.form(props.personnel)"` confirmed |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
|----------|---------------|--------|--------------------|--------|
| `Index.vue` | `props.personnel: Personnel[]` | `PersonnelController::index()` → `Personnel::orderBy('name')->get()` | Yes — full Eloquent query with photo_url accessor appended | FLOWING |
| `Show.vue` | `props.personnel: Personnel` | `PersonnelController::show()` → model binding with `$appends=['photo_url']` | Yes | FLOWING |
| `Show.vue` | `props.cameras: {id, name}[]` | `PersonnelController::show()` → `Camera::orderBy('name')->get(['id','name'])` | Yes — real DB query | FLOWING |
| `Show.vue` | `SyncStatusDot status` | Hardcoded `"not-synced"` — no enrollment table query | No real enrollment state (Phase 4) | STATIC (deferred) |
| `Index.vue` | `SyncStatusDot status` | Hardcoded `"not-synced"` — no enrollment table query | No real enrollment state (Phase 4) | STATIC (deferred) |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| All 7 personnel routes registered | `php artisan route:list --name=personnel` | index/store/create/show/edit/update/destroy all present | PASS |
| 27 personnel tests pass | `php artisan test --compact tests/Feature/Personnel/` | 27 passed (96 assertions) in 1.07s | PASS |
| Personnel TypeScript type exported | `grep "export \* from './personnel'" resources/js/types/index.ts` | Line 4 confirmed | PASS |
| Wayfinder route functions generated | `ls resources/js/routes/personnel/index.ts` | File exists with index/store/update/destroy exports | PASS |
| Wayfinder controller actions generated | `ls resources/js/actions/App/Http/Controllers/PersonnelController.ts` | File exists with store/update/destroy methods | PASS |
| Storage symlink for photo serving | `ls -la public/storage` | Symlink → storage/app/public confirmed | PASS |
| PhotoProcessor uses config values | `grep "config('hds.photo" app/Services/PhotoProcessor.php` | max_dimension, jpeg_quality, max_size_bytes all read from config | PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| PERS-01 | 03-01 | Admin can create a personnel record with name, custom ID, person type (allow/block), and photo | SATISFIED | PersonnelController::store() + StorePersonnelRequest + PhotoProcessor; Create.vue form; 17 CRUD tests passing |
| PERS-02 | 03-01, 03-03 | Admin can edit personnel details and replace photo | SATISFIED | PersonnelController::update() + UpdatePersonnelRequest with nullable photo; Edit.vue with PhotoDropzone currentPhotoUrl prop |
| PERS-03 | 03-01 | Admin can delete a personnel record (propagates delete to all cameras) | PARTIAL | PersonnelController::destroy() deletes record and photo file; MQTT propagation to cameras deferred to Phase 4 (ENRL-09) |
| PERS-04 | 03-02 | Personnel list page shows all personnel with avatar, name, custom ID, list type, and sync status dot | SATISFIED | Index.vue: Avatar/AvatarFallback, name Link, custom_id monospace, type Badge, SyncStatusDot. Sync dot placeholder is Phase 3 design (gray "Not synced") |
| PERS-05 | 03-02 | Personnel detail page shows edit form (left) and per-camera enrollment status sidebar (right) | SATISFIED | Show.vue: 5-col grid with info+actions card (col-span-3) and enrollment sidebar (col-span-2); Edit.vue handles the edit form separately |
| PERS-06 | 03-03 | Photo upload uses a dropzone with client-side preview and displays size constraints as help text | SATISFIED | PhotoDropzone.vue: dashed border dropzone, FileReader preview, "JPEG or PNG, max 1MB after processing. Photos are automatically resized." help text |
| PERS-07 | 03-01 | System preprocesses photos: resize to max 1080p, compress to JPEG <1MB, compute MD5 hash | SATISFIED | PhotoProcessor.process(): scaleDown(max_dimension, max_dimension), quality fallback loop (while > maxBytes), md5(); 10 tests pass |
| PERS-08 | 03-02 | Sync status dot summarizes camera enrollment: green (all enrolled), amber (pending), red (failed) | PARTIAL | SyncStatusDot component supports all four status values with correct colors. Real green/amber/red requires enrollment data from Phase 4 ENRL-01/ENRL-05. Placeholder "not-synced" (gray) shown in Phase 3 per plan design. |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `resources/js/pages/personnel/Index.vue` | 175 | `<SyncStatusDot status="not-synced" />` hardcoded | INFO | Known Phase 3 placeholder; Phase 4 will bind real enrollment status. Not a bug. |
| `resources/js/pages/personnel/Show.vue` | 236 | `<SyncStatusDot status="not-synced" />` hardcoded | INFO | Same Phase 3 placeholder in enrollment sidebar. |

No blockers or warnings found. All `placeholder` occurrences in Vue files are HTML form input placeholders, not stub indicators.

### Human Verification Required

#### 1. End-to-End Photo Upload and Storage

**Test:** Navigate to `/personnel/create`. Select or drag a JPEG image over 1080px dimension. Submit the form.
**Expected:** Record appears in the personnel list (`/personnel`) with the avatar thumbnail rendered; the stored file exists at `storage/app/public/personnel/{uuid}.jpg` and the photo URL resolves in the browser.
**Why human:** Multipart file upload via Inertia Form, PhotoProcessor invocation, public disk storage write, and photo_url accessor serving through the storage symlink all require a live browser session.

#### 2. Edit Form Pre-Fills Existing Values

**Test:** Click "Edit" on an existing personnel record. Inspect the form.
**Expected:** Name, Custom ID, Birthday, ID Card, Phone, Address inputs all show the current record's values. Person Type and Gender selects show the correct option selected. If the record has a photo, the PhotoDropzone shows the existing photo with a "Replace photo" hover overlay.
**Why human:** Select `default-value` binding and PhotoDropzone edit-mode display require visual/DOM inspection in a running app.

#### 3. PhotoDropzone Drag-and-Drop UX

**Test:** On the Create or Edit page, drag a JPEG file over the dropzone area.
**Expected:** The border changes from dashed gray to solid primary color while the file is being dragged over. Dropping the file shows a full-cover thumbnail preview inside the dropzone and the X remove button appears. Selecting a non-JPEG file shows the inline error "Please select a JPEG or PNG image."
**Why human:** Drag events, border class transitions, FileReader asynchronous preview, and inline error visibility require interactive browser testing.

#### 4. Delete Personnel Removes Photo File

**Test:** Create a personnel record with a photo. Confirm the `storage/app/public/personnel/{uuid}.jpg` file exists. Navigate to the personnel's Show page, open the Delete dialog, and confirm deletion.
**Expected:** Redirected to personnel list; the record is absent. The previously noted `{uuid}.jpg` file is gone from `storage/app/public/personnel/`.
**Why human:** Requires a real database record with a real stored photo file to verify the cleanup path in PhotoProcessor::delete().

### Gaps Summary

No blocking gaps. All backend artifacts are substantive and properly wired. All 27 tests pass. The only items not fully delivered in Phase 3 are:

- **PERS-03 MQTT propagation on delete** — intentionally deferred to Phase 4 (ENRL-09). Phase 3 delivers record deletion + photo cleanup.
- **PERS-08 real enrollment colors** — SyncStatusDot component infrastructure is complete with all four status values. Real data to populate green/amber/red comes from Phase 4 enrollment tracking (ENRL-01, ENRL-05).

Both deferred items are explicitly addressed in Phase 4 success criteria. Phase 3 goal "Admin can manage a personnel roster with photos that are automatically preprocessed to meet camera enrollment constraints" is achieved.

---

_Verified: 2026-04-10T12:00:00Z_
_Verifier: Claude (gsd-verifier)_

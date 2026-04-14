# Phase 3: Personnel Management - Research

**Researched:** 2026-04-10
**Domain:** Laravel CRUD with file upload, Intervention Image preprocessing, Inertia Vue forms
**Confidence:** HIGH

## Summary

Phase 3 delivers a personnel roster CRUD (create, read, update, delete) with photo upload, server-side photo preprocessing, and a UI that mirrors the established camera management pattern. The phase builds on Phase 1 (infrastructure, config/hds.php photo constraints) and Phase 2 (camera CRUD pattern that serves as the template for personnel pages).

The technical domain is well-understood: it is a standard Laravel resource controller + Inertia Vue pages with one significant addition -- a drag-and-drop photo dropzone and server-side image processing via Intervention Image v4. The migration already exists (`2026_04_10_000002_create_personnel_table.php`), Intervention Image v4 + its Laravel adapter are already installed, and the GD extension is available. The camera CRUD pattern (CameraController, Form Requests, Vue pages, tests) provides a direct template.

**Primary recommendation:** Mirror the CameraController/pages pattern exactly for PersonnelController, adding a reusable PhotoDropzone Vue component and a PhotoProcessor service class that wraps Intervention Image v4 for resize/compress/hash operations.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Full form exposing all migration columns: name, custom_id, person_type (allow/block), photo, gender, birthday, id_card, phone, address. Personnel records serve as a complete roster.
- **D-02:** Form organized into grouped sections with labeled headers and visual separators (e.g., "Identity" for name/custom_id/type, "Contact" for phone/address, "Photo" for dropzone). Not a single flat column.
- **D-03:** Drag-and-drop dropzone with dashed border drop area, click-to-browse fallback, image thumbnail preview after selection, and inline help text showing constraints (max 1MB, JPEG/PNG accepted). Matches PERS-06 requirement. This is a new component -- no existing dropzone in the codebase.
- **D-04:** On edit, the existing photo displays as the dropzone thumbnail with a "Replace" overlay or small remove button. Dropping/selecting a new file replaces the current photo.
- **D-05:** Client-side validation with instant feedback: reject non-image files and oversized files before form submission. Inline error displayed within the dropzone area. Server validates too, but client catches obvious issues early.
- **D-06:** Table layout matching the camera list pattern. Columns: avatar thumbnail, name, custom ID, person type (allow/block badge), sync status dot. Consistent with existing camera Index page.
- **D-07:** Client-side search input above the table -- filter-as-you-type by name or custom ID. No server round-trip needed for ~200 records.
- **D-08:** Sync status dot shows gray with "Not synced" before Phase 4 enrollment exists. Becomes functional (green/amber/red) when Phase 4 adds enrollment records.
- **D-09:** Separate show and edit pages following the camera pattern (Index, Show, Create, Edit). Show page displays read-only info + enrollment sidebar. Edit button navigates to dedicated edit page.
- **D-10:** Enrollment sidebar on show page lists all registered cameras with gray "not synced" status dots beside each. Previews the structure Phase 4 will populate with real enrollment status.
- **D-11:** Large prominent photo display (~200px) at the top of the info section on the show page. This is a face recognition system -- the photo is the primary identifier.

### Claude's Discretion
- Form section grouping labels and exact field ordering within sections
- Dropzone component internal implementation (vanilla JS vs lightweight library)
- Photo preprocessing pipeline implementation (Intervention Image v3 on server -- resize, compress, MD5)
- Table column widths, responsive behavior, and empty avatar placeholder
- Person type badge styling (colors for allow vs block)
- Delete confirmation dialog text and consequences warning
- PersonnelController structure and Form Request organization
- Factory states and seeder data for development

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PERS-01 | Admin can create a personnel record with name, custom ID, person type (allow/block), and photo | PersonnelController::store with StorePersonnelRequest, Inertia Form component, PhotoDropzone |
| PERS-02 | Admin can edit personnel details and replace photo | PersonnelController::update with UpdatePersonnelRequest, Wayfinder form spoofing for PUT with file |
| PERS-03 | Admin can delete a personnel record (propagates delete to all cameras) | PersonnelController::destroy with Dialog confirmation, cascade via camera_enrollments FK |
| PERS-04 | Personnel list page shows all personnel with avatar, name, custom ID, list type, and sync status dot | Index.vue table with Avatar component, Badge for type, SyncStatusDot placeholder |
| PERS-05 | Personnel detail page shows edit form (left) and per-camera enrollment status sidebar (right) | Show.vue with 3/5 + 2/5 grid layout mirroring cameras/Show.vue pattern |
| PERS-06 | Photo upload uses a dropzone with client-side preview and displays size constraints as help text | PhotoDropzone.vue component with drag/drop, FileReader preview, inline validation |
| PERS-07 | System preprocesses photos: resize to max 1080p, compress to JPEG <1MB, compute MD5 hash | PhotoProcessor service using Intervention Image v4 scaleDown + save with quality from config/hds.php |
| PERS-08 | Sync status dot on personnel list summarizes camera enrollment: green/amber/red | SyncStatusDot.vue showing gray "Not synced" placeholder until Phase 4 adds enrollment records |
</phase_requirements>

## Project Constraints (from CLAUDE.md)

- **PHP 8.4**, Laravel 13, Vue 3, Inertia v3, Tailwind CSS v4, Pest v4
- Use `php artisan make:` commands to create files (model, controller, test, factory, seeder, request)
- Pass `--no-interaction` to all Artisan commands
- Run `vendor/bin/pint --dirty --format agent` after modifying PHP files
- Every change must be programmatically tested with Pest
- Use Wayfinder route functions (never hardcode URLs)
- Use `<script setup lang="ts">` for all Vue components
- Use `setLayoutProps` for dynamic breadcrumbs referencing props
- Use `Inertia::flash('toast', [...])` for success messages
- File uploads with PUT/PATCH must use form method spoofing (Wayfinder handles this automatically)
- Use existing UI components (shadcn-vue): Avatar, Badge, Button, Card, Dialog, Input, Label, Select, Separator
- Check sibling files for conventions before creating new files

## Standard Stack

### Core (already installed)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| intervention/image | 4.0.0 | Server-side photo resize, compress, format conversion | [VERIFIED: composer show] Industry standard PHP image processing |
| intervention/image-laravel | 4.0.0 | Laravel service provider for Intervention Image | [VERIFIED: composer show] Auto-configures ImageManager with GD driver |
| Inertia.js v3 `<Form>` | 3.0.0 | Declarative form submission with file upload support | [VERIFIED: codebase] Automatic FormData conversion for multipart |
| reka-ui Avatar | 2.6.1 | Headless Avatar component primitives | [VERIFIED: codebase] Already in use at `components/ui/avatar/` |

### Supporting (already available)
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| GD extension | PHP 8.4 | Image processing driver for Intervention | [VERIFIED: php -m] Default driver, confirmed available |
| @vueuse/core | 12.8.2 | useDropZone composable for drag-and-drop | [VERIFIED: package.json] Already installed, provides drop zone utility |
| lucide-vue-next | 0.468.0 | Icons (Upload, Users, UserPlus, Trash2, etc.) | [VERIFIED: codebase] Already used in camera pages |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Vanilla dropzone | vue-filepond / vue-dropzone | Adds dependency for simple single-file upload; vanilla + @vueuse/core sufficient |
| Intervention Image | Imagick directly | Intervention provides fluent API, handles edge cases, already installed |
| Client-side resize | Server-side only | Client resize reduces upload time but adds complexity; server is canonical for security |

## Architecture Patterns

### Recommended Project Structure
```
app/
  Http/
    Controllers/
      PersonnelController.php          # Resource controller (mirrors CameraController)
    Requests/
      Personnel/
        StorePersonnelRequest.php      # Validation for create (mirrors Camera pattern)
        UpdatePersonnelRequest.php     # Validation for update
  Models/
    Personnel.php                      # Eloquent model with #[Fillable], HasFactory
  Services/
    PhotoProcessor.php                 # Intervention Image preprocessing service
database/
  factories/
    PersonnelFactory.php               # Factory with meaningful states
  seeders/
    PersonnelSeeder.php                # Dev seed data (~10 records)
resources/
  js/
    components/
      PhotoDropzone.vue                # Reusable drag-drop photo upload component
      SyncStatusDot.vue                # Enrollment sync status indicator
    pages/
      personnel/
        Index.vue                      # List with search, table, avatars
        Show.vue                       # Detail with enrollment sidebar
        Create.vue                     # Create form with grouped sections
        Edit.vue                       # Edit form with existing photo in dropzone
    types/
      personnel.ts                     # Personnel TypeScript interface
tests/
  Feature/
    Personnel/
      PersonnelCrudTest.php            # CRUD operations + validation
      PhotoProcessorTest.php           # Photo preprocessing pipeline
```

### Pattern 1: Resource Controller (mirror CameraController)
**What:** Standard Laravel resource controller returning Inertia responses
**When to use:** All personnel CRUD operations
**Example:**
```php
// Source: app/Http/Controllers/CameraController.php (verified in codebase)
class PersonnelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('personnel/Index', [
            'personnel' => Personnel::orderBy('name')->get(),
        ]);
    }

    public function store(StorePersonnelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        if ($request->hasFile('photo')) {
            $data = array_merge($data, app(PhotoProcessor::class)->process($request->file('photo')));
        }

        Personnel::create($data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel added.')]);
        return to_route('personnel.index');
    }
}
```

### Pattern 2: Photo Preprocessing Service
**What:** Encapsulates Intervention Image v4 resize/compress/hash logic
**When to use:** On store and update when a photo file is uploaded
**Example:**
```php
// Source: https://image.intervention.io/v4 (official docs)
use Intervention\Image\Laravel\Facades\Image;

class PhotoProcessor
{
    public function process(UploadedFile $file): array
    {
        $maxDim = config('hds.photo.max_dimension');   // 1080
        $quality = config('hds.photo.jpeg_quality');     // 85
        
        $image = Image::read($file);
        $image->scaleDown(width: $maxDim, height: $maxDim);
        
        $filename = Str::uuid() . '.jpg';
        $path = 'personnel/' . $filename;
        
        $encoded = $image->encodeUsingFormat(
            \Intervention\Image\Format::JPEG, 
            quality: $quality
        );
        
        Storage::disk('public')->put($path, (string) $encoded);
        
        return [
            'photo_path' => $path,
            'photo_hash' => md5((string) $encoded),
        ];
    }
}
```

### Pattern 3: Inertia Form with File Upload (Wayfinder)
**What:** `<Form v-bind>` with native file input for photo upload
**When to use:** Create and Edit personnel forms
**Example:**
```vue
<!-- Source: Wayfinder auto-generated form bindings (verified in codebase) -->
<!-- For store (POST): works directly -->
<Form v-bind="PersonnelController.store.form()" v-slot="{ errors, processing }">
    <input type="file" name="photo" accept="image/jpeg,image/png" />
</Form>

<!-- For update (PUT with file): Wayfinder auto-spoofs to POST + _method=PUT -->
<Form v-bind="PersonnelController.update.form(personnel)" v-slot="{ errors, processing }">
    <input type="file" name="photo" accept="image/jpeg,image/png" />
</Form>
```
**Key insight:** Wayfinder's `update.form()` automatically generates `method: 'post'` with `_method: 'PUT'` in query params. [VERIFIED: CameraController.ts lines 503-511] Inertia's `<Form>` component automatically converts to FormData when file inputs are present, enabling multipart/form-data submission. No manual spoofing needed.

### Pattern 4: PhotoDropzone Component
**What:** Custom Vue component for drag-and-drop photo upload with preview
**When to use:** Create and Edit personnel forms
**Recommendation:** Build with vanilla browser APIs (dragenter/dragover/drop events) plus a hidden `<input type="file">` for click-to-browse fallback. Use FileReader API for client-side preview. The `@vueuse/core` package provides `useDropZone` composable if desired, but the raw API is straightforward for a single-file dropzone. [ASSUMED]

### Anti-Patterns to Avoid
- **Using `useForm` instead of `<Form>` component:** The project uses the `<Form v-bind="Controller.action.form()">` pattern with Wayfinder. Do not switch to `useForm` for personnel -- stay consistent.
- **Storing photos in `local` disk:** Photos must go on the `public` disk (`storage/app/public`) because cameras fetch them via HTTP URL (picURI). The `local` disk stores files in `storage/app/private` which is not web-accessible.
- **Skipping server-side validation for photos:** Client-side validation (D-05) catches obvious issues early, but the server must validate independently -- never trust client-side checks alone.
- **Processing photos in the controller:** Extract to a service class (PhotoProcessor). The controller should delegate, not contain image manipulation logic.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Image resize/compress | Custom GD function calls | Intervention Image v4 `scaleDown()` + `encodeUsingFormat()` | Handles edge cases: EXIF orientation, color profiles, animated GIF frames, memory limits |
| Form method spoofing | Manual `_method` field in templates | Wayfinder `update.form()` | Auto-generated, type-safe, already proven in CameraController pattern |
| Avatar with fallback | Custom img + initials component | shadcn-vue Avatar + AvatarFallback | Already in `components/ui/avatar/`, handles loading states |
| Person type badges | Custom styled spans | shadcn-vue Badge with variant prop | Already in `components/ui/badge/`, consistent with design system |
| Delete confirmation | Custom modal | shadcn-vue Dialog (matches Camera Show pattern) | Already used in cameras/Show.vue with exact same pattern |
| File hash computation | Custom stream reader | PHP `md5()` on encoded image string | Built-in, no library needed. Hash the Intervention encoded output, not the original upload |

**Key insight:** The camera CRUD is the proven template. Every UI pattern (table, detail page, form, delete dialog) has already been built and tested in Phase 2. Personnel pages should mirror these exactly with additions for photo and search.

## Common Pitfalls

### Pitfall 1: PUT/PATCH Requests Fail with File Uploads
**What goes wrong:** Multipart/form-data is not natively supported with PUT/PATCH in many server frameworks.
**Why it happens:** HTML forms only support GET and POST. XHR with FormData on PUT/PATCH may lose the file data.
**How to avoid:** Use POST with `_method=PUT` spoofing. Wayfinder's `update.form()` already does this automatically (verified in CameraController.ts). The `<Form>` component submits as POST with `_method` handled server-side by Laravel. [VERIFIED: codebase]
**Warning signs:** Photo field is null on update even though a file was selected.

### Pitfall 2: Missing Storage Symlink
**What goes wrong:** Photos save to `storage/app/public/personnel/` but are not accessible via `/storage/personnel/` URL.
**Why it happens:** The `public/storage` symlink does not exist yet. [VERIFIED: `ls -la public/storage` returns "No storage symlink"]
**How to avoid:** Run `php artisan storage:link` as an early task. The symlink maps `public/storage` to `storage/app/public`.
**Warning signs:** Photos appear to save correctly but return 404 when accessed via URL.

### Pitfall 3: Photo File Size Still Over 1MB After Compression
**What goes wrong:** Intervention Image compresses to quality 85 but a high-resolution photo with lots of detail may still exceed 1MB.
**Why it happens:** JPEG quality is not a linear size predictor. A 4000x3000 photo compressed to quality 85 can still be 2MB+.
**How to avoid:** After `scaleDown(1080, 1080)` and encoding at quality 85, check the resulting size. If still over `config('hds.photo.max_size_bytes')`, re-encode at progressively lower quality (e.g., 75, 65, 55) until under 1MB. The `scaleDown` to 1080p should make most photos well under 1MB at quality 85, but the fallback loop is a safety net.
**Warning signs:** Camera error code 467 ("Photo too large") on enrollment.

### Pitfall 4: EXIF Orientation Not Applied
**What goes wrong:** Photos from phones appear rotated 90/180 degrees.
**Why it happens:** Phone cameras store the image in one orientation and use EXIF metadata to indicate how to display it. Some processing tools strip EXIF without applying the rotation.
**How to avoid:** Intervention Image v4's `orient()` method reads and applies EXIF orientation, then strips the EXIF rotation tag. Call `$image->orient()` before `scaleDown()`. [CITED: https://image.intervention.io/v4]
**Warning signs:** Upload from phone shows correctly in preview but rotated in the system.

### Pitfall 5: Photo Hash Computed from Wrong Data
**What goes wrong:** MD5 hash changes between save and verification even though the photo wasn't modified.
**Why it happens:** Hashing the original upload vs. the processed/encoded output produces different hashes. The hash must be computed from the final JPEG output (after resize/compress) because that's what gets served to cameras.
**How to avoid:** Hash the `$encoded` string from Intervention's `encodeUsingFormat()` output, not the original `$file->getContent()`. Store this hash in `photo_hash` column.
**Warning signs:** Phase 4 enrollment always re-sends photos because hash comparison fails.

### Pitfall 6: File Input Not Resetting After Submission
**What goes wrong:** After creating a personnel record, navigating to "Create Another" shows the old file still selected.
**Why it happens:** Inertia's Form component may not reset native file inputs on successful submission.
**How to avoid:** In the PhotoDropzone component, watch for successful form submission (Inertia page visit) and clear the file/preview state. Or use a key on the component that changes on navigation. [ASSUMED]
**Warning signs:** User sees stale photo preview after successful creation.

### Pitfall 7: Inertia Form Component File Naming
**What goes wrong:** The `name` attribute on the file input does not match the FormRequest field name.
**Why it happens:** The `<Form>` component collects data from native form elements by their `name` attribute. If the file input is wrapped in a custom component, the name may not propagate.
**How to avoid:** Ensure the `<input type="file" name="photo">` element has `name="photo"` matching the FormRequest rule key. The custom PhotoDropzone must render a hidden file input with the correct name attribute. [VERIFIED: Inertia Form component uses native form serialization]
**Warning signs:** Server receives all text fields but photo is null.

## Code Examples

### Personnel Model
```php
// Source: Camera model pattern (app/Models/Camera.php, verified in codebase)
// + personnel migration schema (verified in codebase)

#[Fillable(['custom_id', 'name', 'person_type', 'gender', 'birthday', 'id_card', 'phone', 'address', 'photo_path', 'photo_hash'])]
class Personnel extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'person_type' => 'integer',
            'gender' => 'integer',
            'birthday' => 'date',
        ];
    }
}
```

### Photo Preprocessing Pipeline
```php
// Source: Intervention Image v4 docs (https://image.intervention.io/v4)
// + config/hds.php photo constraints (verified in codebase)

use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Format;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoProcessor
{
    public function process(UploadedFile $file): array
    {
        $maxDim = config('hds.photo.max_dimension');      // 1080
        $quality = config('hds.photo.jpeg_quality');       // 85
        $maxBytes = config('hds.photo.max_size_bytes');    // 1048576

        $image = Image::read($file);
        $image->orient();                                   // Fix EXIF rotation
        $image->scaleDown(width: $maxDim, height: $maxDim); // Never upscale

        // Encode, reducing quality if needed to meet size limit
        $encoded = $image->encodeUsingFormat(Format::JPEG, quality: $quality);
        
        while (strlen((string) $encoded) > $maxBytes && $quality > 40) {
            $quality -= 10;
            $encoded = $image->encodeUsingFormat(Format::JPEG, quality: $quality);
        }

        $filename = Str::uuid() . '.jpg';
        $path = 'personnel/' . $filename;

        Storage::disk('public')->put($path, (string) $encoded);

        return [
            'photo_path' => $path,
            'photo_hash' => md5((string) $encoded),
        ];
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
```

### Store Personnel Request Validation
```php
// Source: StoreCameraRequest pattern (verified in codebase)
// + personnel migration column constraints (verified in codebase)

class StorePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'custom_id' => ['required', 'string', 'max:48', 'unique:personnel,custom_id'],
            'name' => ['required', 'string', 'max:32'],
            'person_type' => ['required', 'integer', 'in:0,1'],
            'photo' => ['required', 'image', 'mimes:jpeg,png', 'max:10240'], // 10MB upload limit (server resizes)
            'gender' => ['nullable', 'integer', 'in:0,1'],
            'birthday' => ['nullable', 'date'],
            'id_card' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:72'],
        ];
    }
}
```

### Update Personnel Request Validation
```php
// Source: UpdateCameraRequest pattern (verified in codebase)

class UpdatePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'custom_id' => ['required', 'string', 'max:48', Rule::unique('personnel', 'custom_id')->ignore($this->route('personnel'))],
            'name' => ['required', 'string', 'max:32'],
            'person_type' => ['required', 'integer', 'in:0,1'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png', 'max:10240'], // Optional on update
            'gender' => ['nullable', 'integer', 'in:0,1'],
            'birthday' => ['nullable', 'date'],
            'id_card' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:72'],
        ];
    }
}
```

### Personnel TypeScript Type
```typescript
// Source: Camera type pattern (resources/js/types/camera.ts, verified in codebase)

export interface Personnel {
    id: number;
    custom_id: string;
    name: string;
    person_type: number; // 0=allow, 1=block
    gender: number | null;
    birthday: string | null;
    id_card: string | null;
    phone: string | null;
    address: string | null;
    photo_path: string | null;
    photo_hash: string | null;
    photo_url: string | null; // Computed from photo_path via accessor or controller
    created_at: string;
    updated_at: string;
}
```

### SyncStatusDot Component (Phase 3 Placeholder)
```vue
<!-- Source: CameraStatusDot.vue pattern (verified in codebase) -->
<script setup lang="ts">
defineProps<{
    status: 'synced' | 'pending' | 'failed' | 'not-synced';
}>();
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span
            class="size-1.5 rounded-full"
            :class="{
                'bg-emerald-500': status === 'synced',
                'bg-amber-500': status === 'pending',
                'bg-red-500': status === 'failed',
                'bg-neutral-400 dark:bg-neutral-500': status === 'not-synced',
            }"
        />
        <span class="text-sm text-muted-foreground">
            {{ status === 'synced' ? 'Synced' : status === 'pending' ? 'Pending' : status === 'failed' ? 'Failed' : 'Not synced' }}
        </span>
    </span>
</template>
```

### Personnel Index Page with Client-Side Search
```vue
<!-- Source: cameras/Index.vue pattern (verified in codebase) + D-07 client-side search -->
<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{ personnel: Personnel[] }>();
const search = ref('');

const filtered = computed(() => {
    if (!search.value) return props.personnel;
    const q = search.value.toLowerCase();
    return props.personnel.filter(
        (p) => p.name.toLowerCase().includes(q) || p.custom_id.toLowerCase().includes(q)
    );
});
</script>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Intervention Image v2 `make()->resize()->encode()` | v4 `Image::read()->scaleDown()->encodeUsingFormat()` | v4.0.0 (March 2026) | New fluent API, `scaleDown` prevents upsampling, explicit format encoding [VERIFIED: composer show + official docs] |
| Inertia `useForm` for file uploads | Inertia v3 `<Form>` component with native inputs | v3.0.0 | `<Form>` auto-serializes native file inputs into FormData; Wayfinder `.form()` provides binding [VERIFIED: codebase] |
| Manual `_method` spoofing | Wayfinder auto-spoofing in `.form()` | Wayfinder v0.1 | `update.form()` auto-generates `_method: 'PUT'` with `method: 'post'` [VERIFIED: CameraController.ts] |

**Deprecated/outdated:**
- `Intervention\Image\Facades\Image::make()`: v2 syntax. Use `Image::read()` in v4. [CITED: https://image.intervention.io/v4]
- `LazyProp` / `Inertia::lazy()`: Removed in Inertia v3. Use `Inertia::optional()` instead. [VERIFIED: CLAUDE.md]

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | @vueuse/core `useDropZone` composable is suitable for the PhotoDropzone component | Standard Stack | LOW -- vanilla drag/drop APIs are a trivial fallback |
| A2 | Intervention Image v4 `orient()` method exists and handles EXIF orientation | Pitfalls | LOW -- well-documented feature in v3, likely carried to v4; verify at implementation |
| A3 | Inertia `<Form>` component does not auto-reset native file inputs after submission | Pitfalls | LOW -- standard browser behavior; workaround is trivial |
| A4 | `encodeUsingFormat` returns a stringable `EncodedImage` object that can be passed to `Storage::put` and `md5()` | Code Examples | MEDIUM -- API shape confirmed via official docs but not runtime-tested |
| A5 | Upload limit of 10MB on the server side is sufficient to accept most phone photos before resize | Code Examples | LOW -- modern phone photos are 3-8MB; 10MB covers edge cases |

## Open Questions

1. **Photo URL accessor vs. controller logic**
   - What we know: Photos stored at `photo_path` relative to public disk. Need full URL for frontend display and for camera `picURI`.
   - What's unclear: Whether to add an Eloquent accessor (`photo_url` attribute) or compute the URL in the controller.
   - Recommendation: Add an `Attribute` accessor on the Personnel model that returns `Storage::disk('public')->url($this->photo_path)`. This keeps the URL computation DRY and automatically available in serialization. For the camera `picURI` in Phase 4, the same accessor provides the URL.

2. **Delete cascade to cameras**
   - What we know: PERS-03 says "propagates delete to all cameras". The `camera_enrollments` table has `cascadeOnDelete` FK on `personnel_id`.
   - What's unclear: Whether Phase 3 should also dispatch MQTT delete commands, or just handle the database cascade.
   - Recommendation: Phase 3 handles database cascade only (FK does this automatically). Phase 4 (ENRL-09) will add the MQTT `DeletePersons` dispatch. Phase 3's delete just removes the DB record + stored photo file.

3. **Storage symlink creation timing**
   - What we know: `public/storage` symlink does not exist yet. [VERIFIED]
   - What's unclear: Whether to create it in this phase or assume it was done during setup.
   - Recommendation: Include `php artisan storage:link` as a setup task in the first plan. It's idempotent and essential for photo URLs to work.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| GD PHP extension | Intervention Image | Yes | PHP 8.4 bundled | Imagick (not needed) |
| intervention/image | Photo preprocessing | Yes | 4.0.0 | -- |
| intervention/image-laravel | Service provider | Yes | 4.0.0 | -- |
| public/storage symlink | Photo URL serving | No | -- | Run `php artisan storage:link` |
| @vueuse/core | Drop zone composable | Yes | 12.8.2 | Vanilla drag/drop APIs |

**Missing dependencies with no fallback:**
- None -- all dependencies are installed.

**Missing dependencies with fallback:**
- `public/storage` symlink: Run `php artisan storage:link` (idempotent, one-time setup).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v4.4 |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact --filter=Personnel` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PERS-01 | Create personnel with all fields + photo | Feature | `php artisan test --compact tests/Feature/Personnel/PersonnelCrudTest.php --filter=store` | Wave 0 |
| PERS-02 | Edit personnel, replace photo | Feature | `php artisan test --compact tests/Feature/Personnel/PersonnelCrudTest.php --filter=update` | Wave 0 |
| PERS-03 | Delete personnel, verify cascade + file cleanup | Feature | `php artisan test --compact tests/Feature/Personnel/PersonnelCrudTest.php --filter=delete` | Wave 0 |
| PERS-04 | List personnel with correct props | Feature | `php artisan test --compact tests/Feature/Personnel/PersonnelCrudTest.php --filter=list` | Wave 0 |
| PERS-05 | Show personnel detail page with camera sidebar | Feature | `php artisan test --compact tests/Feature/Personnel/PersonnelCrudTest.php --filter=show` | Wave 0 |
| PERS-06 | Photo upload validation (type, size) | Feature | `php artisan test --compact tests/Feature/Personnel/PersonnelCrudTest.php --filter=photo` | Wave 0 |
| PERS-07 | Photo preprocessing (resize, compress, hash) | Feature | `php artisan test --compact tests/Feature/Personnel/PhotoProcessorTest.php` | Wave 0 |
| PERS-08 | Sync status dot component renders (placeholder) | manual-only | Visual verification -- component renders gray "Not synced" | -- |

### Sampling Rate
- **Per task commit:** `php artisan test --compact --filter=Personnel`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Personnel/PersonnelCrudTest.php` -- covers PERS-01 through PERS-06
- [ ] `tests/Feature/Personnel/PhotoProcessorTest.php` -- covers PERS-07

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Existing auth middleware (`auth`, `verified`) on personnel routes [VERIFIED: web.php pattern] |
| V3 Session Management | no | Handled by framework globally |
| V4 Access Control | yes | Single admin user; `authorize()` returns true (single-role v1) [VERIFIED: CONTEXT.md] |
| V5 Input Validation | yes | FormRequest classes with typed rules; Intervention validates image format |
| V6 Cryptography | no | MD5 hash is for change detection, not security |

### Known Threat Patterns for Personnel Management

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Malicious file upload (PHP shell as .jpg) | Tampering | `mimes:jpeg,png` validation + Intervention Image re-encoding strips non-image data [VERIFIED: FormRequest pattern] |
| Path traversal in photo_path | Tampering | Use `Str::uuid()` for filename, never use user-supplied filename [VERIFIED: code example] |
| Oversized upload DoS | Denial of Service | `max:10240` rule on photo field + PHP `upload_max_filesize` / `post_max_size` limits |
| Unauthorized access to personnel data | Info Disclosure | `auth` + `verified` middleware on all routes [VERIFIED: web.php] |
| EXIF data leakage | Info Disclosure | Intervention Image re-encoding to JPEG strips EXIF metadata |

## Sources

### Primary (HIGH confidence)
- Codebase verified: CameraController.php, StoreCameraRequest.php, UpdateCameraRequest.php, Camera model, cameras/Index.vue, cameras/Show.vue, cameras/Create.vue, cameras/Edit.vue, CameraStatusDot.vue, AppSidebar.vue, CameraController.ts (Wayfinder), personnel migration, camera_enrollments migration, config/hds.php, filesystems.php, app.ts
- `composer show intervention/image` -- v4.0.0, `intervention/image-laravel` v4.0.0
- `php -m | grep gd` -- GD extension available
- `ls -la public/storage` -- symlink does not exist

### Secondary (MEDIUM confidence)
- [Intervention Image v4 Official Docs](https://image.intervention.io/v4) -- scaleDown, encodeUsingFormat, orient methods
- [Intervention Image v4 Resize Docs](https://image.intervention.io/v4/modifying-images/resizing) -- scaleDown API signature and behavior
- [Intervention Image v4 Output Docs](https://image.intervention.io/v4/basics/image-output) -- encoding API with quality parameter
- [Inertia.js File Uploads](https://inertiajs.com/docs/v2/the-basics/file-uploads) -- FormData auto-conversion, method spoofing

### Tertiary (LOW confidence)
- None -- all findings verified against codebase or official documentation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all packages verified installed via composer/npm, versions confirmed
- Architecture: HIGH -- directly mirrors verified CameraController pattern from Phase 2
- Pitfalls: HIGH -- most pitfalls verified against codebase (missing symlink, Wayfinder spoofing); Intervention API pitfalls verified via official docs
- Photo preprocessing: MEDIUM -- Intervention v4 API verified via docs but not runtime-tested in this project yet

**Research date:** 2026-04-10
**Valid until:** 2026-05-10 (stable domain, low churn)

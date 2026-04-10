---
phase: 03-personnel-management
plan: 03
subsystem: frontend
tags: [vue, inertia-form, photo-upload, drag-drop, wayfinder, reka-ui-select]

# Dependency graph
requires:
  - phase: 03-personnel-management
    plan: 01
    provides: "PersonnelController, Personnel model, PhotoProcessor, FormRequests, TypeScript types"
  - phase: 02-camera-management-liveness
    plan: 02
    provides: "Camera Create/Edit Vue page patterns, Wayfinder form binding patterns"
provides:
  - "PhotoDropzone component with drag-and-drop, preview, validation, and edit mode"
  - "Personnel Create page with grouped form sections (Photo, Identity, Details, Contact)"
  - "Personnel Edit page with pre-filled fields and existing photo display"
affects:
  - "resources/js/pages/personnel/ -- replaces stub components from Plan 01"

# Tech stack
added: []
patterns:
  - "Reka UI Select with name prop for hidden form input in Inertia Form"
  - "DataTransfer API to programmatically set file input files for Inertia Form collection"
  - "FileReader API for client-side photo preview before upload"
  - "setLayoutProps for dynamic breadcrumbs referencing component props"

# Key files
created:
  - resources/js/components/PhotoDropzone.vue
modified:
  - resources/js/pages/personnel/Create.vue
  - resources/js/pages/personnel/Edit.vue

# Decisions
key-decisions:
  - "Reka UI SelectRoot renders BubbleSelect (hidden <select>) when name prop is provided -- no manual hidden input needed"
  - "PhotoDropzone uses vanilla drag/drop events (not @vueuse/core useDropZone) for minimal dependency"
  - "null-to-undefined coercion (photo_url ?? undefined) for optional prop type safety"

# Metrics
duration: 3min
completed: "2026-04-10"
tasks_completed: 2
tasks_total: 2
files_created: 1
files_modified: 2
---

# Phase 03 Plan 03: Photo Upload & Create/Edit Pages Summary

PhotoDropzone component with drag-and-drop, FileReader preview, client-side type/size validation, and edit-mode Replace overlay; Create page with four grouped form sections; Edit page with pre-filled fields and Wayfinder PUT spoofing.

## What Was Built

### Task 1: PhotoDropzone Component (69740e7)

Created `resources/js/components/PhotoDropzone.vue` implementing the full photo upload UX:

- **Empty state:** Dashed border dropzone with Upload icon and "Drag and drop a photo here, or click to browse" prompt
- **Drag over state:** Border changes to primary color with accent background
- **Preview state:** FileReader data URL renders full-cover thumbnail with X remove button
- **Edit mode:** Existing photo displays as thumbnail with semi-transparent "Replace photo" overlay on hover
- **Client-side validation:** Rejects non-JPEG/PNG files ("Please select a JPEG or PNG image.") and files over 10MB ("File is too large. Maximum upload size is 10MB.") with inline error and destructive border
- **Form integration:** Hidden `<input type="file" :name="name">` with DataTransfer API to programmatically set files, ensuring Inertia `<Form>` collects the file for multipart/form-data submission

### Task 2: Personnel Create & Edit Pages (686e9e7)

**Create page** (`resources/js/pages/personnel/Create.vue`):
- Grouped form sections with `<Separator>` dividers: Photo, Identity (name, custom_id, person_type), Details (gender, birthday, id_card), Contact (phone, address)
- Person type uses `<Select name="person_type" default-value="0">` -- Reka UI renders hidden `<select>` for Inertia Form
- Form submits via `PersonnelController.store.form()` Wayfinder binding
- Static breadcrumbs via `defineOptions`

**Edit page** (`resources/js/pages/personnel/Edit.vue`):
- Same structure as Create with `:default-value` props on all inputs
- PhotoDropzone shows existing photo via `:current-photo-url="props.personnel.photo_url ?? undefined"`
- Select components pre-select from `String(props.personnel.person_type)` and conditional gender
- Form submits via `PersonnelController.update.form(props.personnel)` with auto PUT spoofing
- Dynamic breadcrumbs via `setLayoutProps` (references props.personnel.name)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed null-to-undefined type mismatch on photo_url prop**
- **Found during:** Task 2 (vue-tsc verification)
- **Issue:** `props.personnel.photo_url` is `string | null` but `PhotoDropzone.currentPhotoUrl` prop expects `string | undefined`
- **Fix:** Added `?? undefined` coercion: `:current-photo-url="props.personnel.photo_url ?? undefined"`
- **Files modified:** resources/js/pages/personnel/Edit.vue
- **Commit:** 686e9e7

## Verification Results

- TypeScript compilation: PASS (vue-tsc --noEmit clean)
- Frontend build: PASS (npm run build succeeds)
- Personnel tests: PASS (27 tests, 96 assertions)
- Full test suite: PASS (135 tests, 430 assertions)
- ESLint: PASS (no errors)
- Prettier: PASS (formatted)

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| 1 | 69740e7 | feat(03-03): add PhotoDropzone component |
| 2 | 686e9e7 | feat(03-03): add personnel Create and Edit pages |

## Self-Check: PASSED

All files exist. All commits verified.

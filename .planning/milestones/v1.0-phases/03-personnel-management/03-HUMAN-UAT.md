---
status: complete
phase: 03-personnel-management
source: [03-01-SUMMARY.md, 03-02-SUMMARY.md, 03-03-SUMMARY.md, 03-VERIFICATION.md]
started: 2026-04-10
updated: 2026-04-10
---

## Current Test

[testing complete]

## Tests

### 1. Personnel list page loads
expected: Navigate to the Personnel page via sidebar. The page shows a table with columns: avatar, name, custom ID, person type (allow/block badge), and sync status dot (gray "Not synced"). If no personnel exist, an empty state message "No personnel registered" with an "Add Personnel" button appears.
result: pass

### 2. Create personnel with photo upload
expected: Click "Add Personnel". A form appears with grouped sections (Photo, Identity, Details, Contact). Drag or click to upload a JPEG/PNG photo — a preview thumbnail appears in the dropzone. Fill in name, custom ID, select person type (allow/block), and submit. Toast "Personnel added." appears. Redirected to the personnel show page.
result: pass

### 3. Photo preprocessing works
expected: Upload a photo larger than 1080p. After creation, view the personnel show page — the stored photo should be resized (max 1080px on longest side), compressed as JPEG under 1MB. The photo displays correctly at 200px on the show page.
result: pass

### 4. Edit personnel and replace photo
expected: Navigate to a personnel's show page, click "Edit". The edit form pre-fills all fields (name, custom ID, person type, gender, birthday, etc.). The existing photo shows in the dropzone with a "Replace" overlay. Upload a new photo — preview updates. Submit. Toast "Personnel updated." appears.
result: pass

### 5. Client-side search filters personnel
expected: On the personnel list page, type a name or custom ID into the search input above the table. The table filters as you type — only matching rows show. Clear the search — all rows return. If no matches, "No personnel match your search." message appears.
result: pass

### 6. Personnel detail page layout
expected: Click a personnel row or name. The show page displays: large 200px photo at top, grouped info fields (name, custom ID, person type badge, gender, birthday, etc.), and an enrollment sidebar on the right listing all cameras with gray "Not synced" status dots.
result: pass

### 7. Delete personnel removes record and file
expected: On a personnel show page, click "Delete". A confirmation dialog appears with "Delete Personnel" and "Keep Personnel" buttons. Confirm deletion. Toast "Personnel deleted." appears. Redirected to personnel list. The record is gone from the table. The stored photo file is removed from disk.
result: pass

### 8. PhotoDropzone validation
expected: On the create form, try to upload a non-image file (e.g., .txt). An inline error "Please select a JPEG or PNG image." appears within the dropzone. Try uploading a file >10MB. An inline error about file size appears. No form submission occurs for invalid files.
result: pass

## Summary

total: 8
passed: 8
issues: 0
pending: 0
skipped: 0
blocked: 0

## Gaps

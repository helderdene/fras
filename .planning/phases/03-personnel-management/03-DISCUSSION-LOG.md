# Phase 3: Personnel Management - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-10
**Phase:** 03-personnel-management
**Areas discussed:** Personnel form fields, Photo upload experience, Personnel list presentation, Detail page layout

---

## Personnel Form Fields

| Option | Description | Selected |
|--------|-------------|----------|
| Full form | Show all migration fields (name, custom ID, type, photo + gender, birthday, ID card, phone, address). Personnel records serve as a complete roster. | ✓ |
| Minimal + expandable | Show 4 required fields by default, with "More details" collapsible section for optional fields. | |
| Minimal only | Only the 4 required fields. Optional fields can be added later. | |

**User's choice:** Full form
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Grouped sections | Fields organized under labeled sections with visual separators. Clearer structure for a longer form. | ✓ |
| Single column, no sections | All fields in one flowing column. Simpler, mirrors the camera form pattern. | |
| You decide | Claude's discretion on form organization. | |

**User's choice:** Grouped sections
**Notes:** None

---

## Photo Upload Experience

| Option | Description | Selected |
|--------|-------------|----------|
| Drag-and-drop dropzone with preview | Dashed border drop area, click-to-browse fallback, thumbnail after selection, inline help text with constraints. Matches PERS-06. | ✓ |
| Simple file input with preview | Standard file picker button with thumbnail preview below after selection. | |
| You decide | Claude's discretion on component design. | |

**User's choice:** Drag-and-drop dropzone with preview
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Current photo in dropzone | Show existing photo as dropzone thumbnail with "Replace" overlay or small remove button. Dropping a new file replaces it. | ✓ |
| Photo beside dropzone | Current photo displayed as separate preview next to an empty dropzone. | |
| You decide | Claude's discretion. | |

**User's choice:** Current photo in dropzone
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Client-side validation | Instant feedback: reject non-image files and oversized files before form submission. Inline error in dropzone. | ✓ |
| Server-only validation | Submit form, let Laravel validate and return errors. Simpler but slower feedback. | |
| You decide | Claude's discretion. | |

**User's choice:** Client-side validation
**Notes:** None

---

## Personnel List Presentation

| Option | Description | Selected |
|--------|-------------|----------|
| Table | Same pattern as cameras: columns for avatar, name, custom ID, type badge, sync dot. Consistent and compact. | ✓ |
| Card grid | Cards with larger avatar thumbnails, name, type badge, sync dot. More visual. | |
| You decide | Claude's discretion. | |

**User's choice:** Table
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Client-side search | Filter-as-you-type text input above table. Filters by name or custom ID. No server round-trip. | ✓ |
| Server-side search with filters | Search input + dropdown filters. Paginated from server. | |
| No search | Just show full list sorted alphabetically. | |
| You decide | Claude's discretion. | |

**User's choice:** Client-side search
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Gray dot with "Not synced" | Neutral indicator showing enrollment hasn't happened. Becomes functional in Phase 4. | ✓ |
| No dot until Phase 4 | Omit the column entirely. Add when enrollment is built. | |
| You decide | Claude's discretion. | |

**User's choice:** Gray dot with "Not synced"
**Notes:** None

---

## Detail Page Layout

| Option | Description | Selected |
|--------|-------------|----------|
| Combined view/edit | Single page with edit form always visible on left, enrollment sidebar on right. | |
| Separate show and edit | Show page displays read-only info + enrollment sidebar. Edit button navigates to dedicated edit page. Matches camera pattern. | ✓ |
| You decide | Claude's discretion. | |

**User's choice:** Separate show and edit
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Empty state with message | Card with icon and placeholder message about enrollment. | |
| Camera list with gray dots | Show all registered cameras with gray "not synced" status. Previews Phase 4 structure. | ✓ |
| You decide | Claude's discretion. | |

**User's choice:** Camera list with gray dots
**Notes:** None

| Option | Description | Selected |
|--------|-------------|----------|
| Large photo | Prominent photo display (~200px) at top of info section. Face recognition system — photo matters. | ✓ |
| Medium avatar | Larger than list thumbnail but not dominant (~80-100px). | |
| You decide | Claude's discretion. | |

**User's choice:** Large photo
**Notes:** None

---

## Claude's Discretion

- Form section grouping labels and exact field ordering
- Dropzone component implementation details
- Photo preprocessing pipeline (Intervention Image v3)
- Table column widths and responsive behavior
- Person type badge styling
- Delete confirmation dialog
- PersonnelController structure and Form Request organization
- Factory states and seeder data

## Deferred Ideas

None — discussion stayed within phase scope

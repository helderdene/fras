# Phase 9: Disable user registration, only admin can create a user - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-11
**Phase:** 09-disable-user-registration-only-admin-can-create-a-user
**Areas discussed:** Registration removal scope, Admin user creation, User management features

---

## Registration Removal Scope

| Option | Description | Selected |
|--------|-------------|----------|
| Disable Fortify feature only (Recommended) | Comment out Features::registration() in config/fortify.php. Register route disappears, canRegister becomes false, Welcome/Login links auto-hide. Register.vue stays but is unreachable. | ✓ |
| Full cleanup | Disable Fortify feature AND delete Register.vue, remove register imports from Welcome.vue/Login.vue, remove CreateNewUser action. Complete removal of all registration code. | |
| Disable + keep Register.vue as admin form | Disable public registration but repurpose Register.vue behind auth middleware for admin use. Minimal new code. | |

**User's choice:** Disable Fortify feature only
**Notes:** Minimal change — just toggle the config. Dead code preserved.

---

## Admin User Creation

| Option | Description | Selected |
|--------|-------------|----------|
| Admin panel page (Recommended) | New Users section in sidebar nav with a create user form. Full Inertia page matching existing FRAS admin UI patterns. | ✓ |
| Artisan command only | CLI-only user creation. No UI. | |
| Seeder only | Users created via database seeder. Most minimal but requires code access. | |

**User's choice:** Admin panel page
**Notes:** None

### Follow-up: Navigation Placement

| Option | Description | Selected |
|--------|-------------|----------|
| Settings section | Under Settings alongside Profile, Security, Appearance. | |
| Main sidebar with Cameras/Personnel | Top-level nav item alongside Cameras, Personnel, Alerts, Events. | ✓ |
| You decide | Claude picks the best placement. | |

**User's choice:** Main sidebar with Cameras/Personnel
**Notes:** Users section gets top-level visibility like other admin resources.

---

## User Management Features

| Option | Description | Selected |
|--------|-------------|----------|
| Full CRUD (Recommended) | List users, create, edit (name/email), delete. Matches existing patterns. | ✓ |
| Create and delete only | Admin can add and remove users but not edit. | |
| Create only | Admin can only create users. Simplest scope. | |

**User's choice:** Full CRUD
**Notes:** None

### Follow-up: Password Reset

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, set new password | Admin can set a new password for any user from the edit form. | ✓ |
| No, users reset their own | Users use existing Forgot Password flow. | |

**User's choice:** Yes, set new password
**Notes:** Admin has full control including password changes for other users.

---

## Claude's Discretion

- Form layout and field arrangement
- User list table columns and sorting
- Delete confirmation dialog
- Self-deletion prevention logic

## Deferred Ideas

None

# Phase 9: Disable user registration, only admin can create a user - Context

**Gathered:** 2026-04-11
**Status:** Ready for planning

<domain>
## Phase Boundary

Disable public self-registration so only authenticated admins can create user accounts. Add a full user management admin panel (list, create, edit, delete) to the main application interface.

</domain>

<decisions>
## Implementation Decisions

### Registration Removal
- **D-01:** Disable registration by commenting out `Features::registration()` in `config/fortify.php`. This removes the `/register` route and sets `canRegister` to false, auto-hiding register links in Welcome.vue and Login.vue.
- **D-02:** Keep `Register.vue` and `CreateNewUser` action as dead code — do not delete them. They become unreachable but are preserved for potential future use.

### Admin User Creation
- **D-03:** Create a new admin panel page for user creation with a form (name, email, password). Full Inertia page matching existing FRAS admin UI patterns (cameras, personnel pages).
- **D-04:** Users section appears as a top-level nav item in the main app sidebar alongside Cameras, Personnel, Alerts, Events.

### User Management
- **D-05:** Full CRUD for users: list all users, create new user, edit user (name/email), delete user. Follows existing resource controller patterns (CameraController, PersonnelController).
- **D-06:** Admin can set a new password for any user directly from the edit form (password field on edit page).

### Claude's Discretion
- Form layout and field arrangement for user create/edit pages
- User list table columns and sorting
- Delete confirmation dialog implementation
- Whether to prevent admin from deleting their own account

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Authentication & Registration
- `config/fortify.php` — Fortify features config; `Features::registration()` on line 147 is the toggle to disable
- `app/Providers/FortifyServiceProvider.php` — Registers Fortify views and actions; `registerView` and `canRegister` prop passing
- `app/Actions/Fortify/CreateNewUser.php` — Existing user creation action (preserved but unused after registration disabled)

### Frontend Registration References
- `resources/js/pages/auth/Register.vue` — Registration page (becomes unreachable)
- `resources/js/pages/auth/Login.vue` — Shows register link conditionally via `canRegister` prop
- `resources/js/pages/Welcome.vue` — Shows register links conditionally via `canRegister` prop
- `routes/web.php` — Passes `canRegister` to Welcome page

### Existing Admin Patterns (for user management pages)
- `app/Http/Controllers/CameraController.php` — Resource controller CRUD pattern to follow
- `app/Http/Controllers/PersonnelController.php` — Resource controller CRUD pattern to follow
- `resources/js/pages/cameras/Index.vue` — List page pattern to follow
- `resources/js/pages/cameras/Create.vue` — Create form page pattern to follow
- `resources/js/pages/cameras/Edit.vue` — Edit form page pattern to follow
- `resources/js/pages/personnel/Index.vue` — List page with search pattern

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- Resource controller pattern (CameraController, PersonnelController) — direct template for UserController
- Form request classes in `app/Http/Requests/Settings/` — pattern for user validation
- `PasswordValidationRules` concern in `app/Concerns/` — reusable password validation rules
- `ProfileValidationRules` concern — reusable name/email validation rules
- shadcn-vue UI components (Button, Input, Card, Dialog) — already available
- Dense data grid table pattern from Phase 8 redesign

### Established Patterns
- Inertia resource CRUD: index/create/store/edit/update/destroy controller methods
- Form requests for validation with Concerns traits
- Wayfinder-generated typed route functions for frontend
- `Inertia::flash('toast', ...)` for success feedback
- `to_route()` redirects after mutations

### Integration Points
- Sidebar navigation in `resources/js/layouts/` — add Users nav item
- `routes/web.php` — add `Route::resource('users', UserController::class)`
- User model `app/Models/User.php` — already exists with factory

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches following existing FRAS admin patterns.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 09-disable-user-registration-only-admin-can-create-a-user*
*Context gathered: 2026-04-11*

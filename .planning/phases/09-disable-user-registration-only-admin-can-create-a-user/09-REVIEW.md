---
phase: 09-disable-user-registration-only-admin-can-create-a-user
reviewed: 2026-04-11T12:17:39Z
depth: standard
files_reviewed: 12
files_reviewed_list:
  - app/Http/Controllers/UserController.php
  - app/Http/Requests/User/StoreUserRequest.php
  - app/Http/Requests/User/UpdateUserRequest.php
  - config/fortify.php
  - resources/js/components/AppSidebar.vue
  - resources/js/components/DashboardTopNav.vue
  - resources/js/pages/users/Create.vue
  - resources/js/pages/users/Edit.vue
  - resources/js/pages/users/Index.vue
  - routes/web.php
  - tests/Feature/Auth/RegistrationDisabledTest.php
  - tests/Feature/User/UserCrudTest.php
findings:
  critical: 1
  warning: 3
  info: 2
  total: 6
status: issues_found
---

# Phase 09: Code Review Report

**Reviewed:** 2026-04-11T12:17:39Z
**Depth:** standard
**Files Reviewed:** 12
**Status:** issues_found

## Summary

This phase correctly disables Fortify registration and introduces an admin-only `UserController` for managing users. The Fortify config change is correct — `Features::registration()` is commented out and the tests confirming 404s on `/register` are well-formed. The CRUD controller, form requests, Vue pages, and tests are generally clean. However, the authorization layer has a critical gap: every authenticated user (not just admins) can reach all user management endpoints. There is no role check, policy, or middleware guard on `UserController`. Additionally, admin-created users land with `email_verified_at = null`, which means the route group's `verified` middleware will block them from using the application. The `DashboardTopNav` also hardcodes URLs against project convention.

---

## Critical Issues

### CR-01: No Authorization on User Management Endpoints — Any Authenticated User Can Create/Edit/Delete Users

**File:** `app/Http/Requests/User/StoreUserRequest.php:15`, `app/Http/Requests/User/UpdateUserRequest.php:15`

**Issue:** Both `authorize()` methods unconditionally return `true`. Combined with routes registered under only `auth` + `verified` middleware (no admin guard), any logged-in user can create new accounts, update or delete other users' passwords and emails. The project goal is "only admin can create a user" but no mechanism enforces "admin-only" at the HTTP layer.

**Fix:** Either add an `is_admin` column and enforce it in policies, or for a simpler single-tier approach common in closed facility systems, create a Gate or Policy and use it in the Form Request `authorize()` methods. Minimal example keeping the existing single `User` model:

```php
// In StoreUserRequest (and UpdateUserRequest, UserController::destroy)
public function authorize(): bool
{
    // Option A: All authenticated users are operators and only a designated
    // "first" admin or a role flag is allowed — add is_admin to users table.
    return $this->user()?->is_admin === true;
}
```

Or, register a Gate in `AppServiceProvider`:

```php
Gate::define('manage-users', fn (User $user) => $user->is_admin);
```

And enforce it on the route group:

```php
Route::middleware(['auth', 'verified', 'can:manage-users'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
});
```

---

## Warnings

### WR-01: Admin-Created Users Have `email_verified_at = null` — Cannot Log In via `verified` Middleware

**File:** `app/Http/Controllers/UserController.php:32`

**Issue:** `User::create($request->validated())` stores only `name`, `email`, and `password`. The `email_verified_at` column defaults to `null`. Because all dashboard routes are under the `verified` middleware (see `routes/web.php:17`), a newly created user is immediately redirected away upon first login — they cannot use the application without completing email verification. In a closed single-site facility system, sending verification emails may not be the intended flow for operator accounts provisioned by an admin.

**Fix:** Mark the user as pre-verified at creation time, since the admin is explicitly provisioning the account:

```php
/** Store a newly created user. */
public function store(StoreUserRequest $request): RedirectResponse
{
    User::create([
        ...$request->validated(),
        'email_verified_at' => now(),
    ]);

    Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

    return to_route('users.index');
}
```

If email verification IS desired for admin-created accounts, the tests should cover this behavior explicitly.

---

### WR-02: `DashboardTopNav` Uses Hardcoded URL Strings Instead of Wayfinder Functions

**File:** `resources/js/components/DashboardTopNav.vue:52-58`

**Issue:** The `navItems` array hardcodes URL strings (`'/cameras'`, `'/personnel'`, `'/users'`, `'/alerts'`, `'/events'`). The project convention explicitly states "NEVER hardcode URLs; always use Wayfinder-generated functions." If any route path changes, this component silently breaks without a TypeScript compile-time error, unlike all other navigation in the codebase (e.g., `AppSidebar.vue` correctly uses Wayfinder imports).

**Fix:** Replace hardcoded strings with Wayfinder route function calls matching the pattern used in `AppSidebar.vue`:

```typescript
import { index as camerasIndex } from '@/routes/cameras';
import { index as personnelIndex } from '@/routes/personnel';
import { index as usersIndex } from '@/routes/users';
import { index as alertsIndex } from '@/routes/alerts';
import { index as eventsIndex } from '@/routes/events';

const navItems = [
    { label: 'Cameras', url: camerasIndex() },
    { label: 'Personnel', url: personnelIndex() },
    { label: 'Users', url: usersIndex() },
    { label: 'Alerts', url: alertsIndex() },
    { label: 'Events', url: eventsIndex() },
];
```

---

### WR-03: `UserCrudTest` Does Not Test That Admin-Created Users Can Actually Log In

**File:** `tests/Feature/User/UserCrudTest.php:34-47`

**Issue:** The `'can store a user'` test asserts the user record exists in the database but does not verify `email_verified_at` is set. Given the route group's `verified` middleware and the bug in WR-01, a passing test here creates false confidence — the stored user cannot actually use the system. The test should assert the field is populated (or deliberately assert it is null if verification email flow is intended).

**Fix:** Add an assertion on `email_verified_at` to make the intended behavior explicit:

```php
test('can store a user', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertRedirect(route('users.index'));

    $newUser = User::where('email', 'newuser@example.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->email_verified_at)->not->toBeNull(); // admin-created users are pre-verified
});
```

---

## Info

### IN-01: `RegistrationDisabledTest` Does Not Use `RefreshDatabase` — Safe but Inconsistent

**File:** `tests/Feature/Auth/RegistrationDisabledTest.php:1-14`

**Issue:** The test file omits `uses(RefreshDatabase::class)`. This is harmless since these tests make no DB writes, but it is inconsistent with other feature test files in the suite. No functional impact.

**Fix:** Add `uses(RefreshDatabase::class)` if the project enforces it globally in `Pest.php`, or leave as-is if not required. Check `tests/Pest.php` for the global `uses()` declaration — if `RefreshDatabase` is bound globally to `Feature` tests, this file is already covered and no change is needed.

---

### IN-02: `AppSidebar` and `DashboardTopNav` Both Show "Users" Link to All Authenticated Users

**File:** `resources/js/components/AppSidebar.vue:48-51`, `resources/js/components/DashboardTopNav.vue:54-55`

**Issue:** The Users nav item is visible to every authenticated user regardless of role. Once CR-01 is resolved with a proper `is_admin` guard, non-admin users will see the nav link but get a 403 when they click it. This is a UX inconsistency rather than a security issue (the server correctly enforces access), but it is worth hiding the link for non-admins once the authorization model is in place.

**Fix:** Pass an `isAdmin` prop from `HandleInertiaRequests` shared props (or from the page-level Inertia data) and conditionally render the nav item:

```vue
<!-- In AppSidebar.vue, after authorization model is added -->
<NavMain :items="mainNavItems.filter(item => !item.adminOnly || page.props.auth.user.is_admin)" />
```

---

_Reviewed: 2026-04-11T12:17:39Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_

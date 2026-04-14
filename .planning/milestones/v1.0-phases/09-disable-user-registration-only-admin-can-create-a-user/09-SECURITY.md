# SECURITY.md — Phase 09: Disable User Registration / Admin User CRUD

**Phase:** 09 — disable-user-registration-only-admin-can-create-a-user
**Plans verified:** 09-01, 09-02
**ASVS Level:** 1
**Threats Closed:** 9/9
**Threats Open:** 0/9
**Unregistered Flags:** none

---

## Threat Verification

| Threat ID | Category | Disposition | Status | Evidence |
|-----------|----------|-------------|--------|----------|
| T-09-01 | Spoofing | mitigate | CLOSED | `routes/web.php:17` — `Route::middleware(['auth', 'verified'])->group(...)` wraps `Route::resource('users', UserController::class)` |
| T-09-02 | Tampering | mitigate | CLOSED | `app/Http/Requests/User/StoreUserRequest.php:12` — uses `PasswordValidationRules` + `ProfileValidationRules` traits; email uniqueness via `Rule::unique`; password strength via `Password::default()` |
| T-09-03 | Tampering | mitigate | CLOSED | `app/Http/Requests/User/UpdateUserRequest.php:28-29` — `profileRules($this->route('user')->id)` enforces unique-ignore-self; password declared `['nullable', 'string', Password::default(), 'confirmed']` |
| T-09-04 | Elevation of Privilege | mitigate | CLOSED | `app/Http/Controllers/UserController.php:66` — `if ($user->id === $request->user()->id)` guard prevents self-deletion; returns 422 with session error |
| T-09-05 | Information Disclosure | mitigate | CLOSED | `app/Models/User.php:15` — `#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]`; `index()` further restricts to `['id', 'name', 'email', 'created_at']` via explicit column select |
| T-09-06 | Tampering | mitigate | CLOSED | `config/fortify.php:147` — `// Features::registration()` commented out; Fortify returns 404 for GET/POST `/register` |
| T-09-07 | Spoofing | accept | CLOSED | Accepted — see Accepted Risks log below |
| T-09-08 | Information Disclosure | mitigate | CLOSED | `resources/js/pages/users/Index.vue:55-100` — table renders name, email, created_at columns only; `#[Hidden]` on User model suppresses password/2FA fields at serialization layer |
| T-09-09 | Tampering | mitigate | CLOSED | `resources/js/pages/users/Edit.vue:50` — `<Dialog v-if="!isOwnAccount">` hides delete trigger for own account (UI guard); backend guard at `UserController.php:66` provides defence-in-depth |

---

## Accepted Risks Log

| Threat ID | Category | Component | Rationale | Review Date |
|-----------|----------|-----------|-----------|-------------|
| T-09-07 | Spoofing | Vue pages (users/Index, users/Create, users/Edit) | All three pages are nested within AppLayout, which is only reachable after authentication and email verification via the `auth` + `verified` middleware group in `routes/web.php:17`. No user data is passed to Vue until the auth gate passes. No additional client-side auth check is required at ASVS Level 1 for an SPA that relies entirely on server-side session auth. | 2026-04-11 |

---

## Unregistered Flags

None. Neither `09-01-SUMMARY.md` nor `09-02-SUMMARY.md` contains a `## Threat Flags` section.

---

## Notes

- `config/fortify.php` retains `Features::resetPasswords()`, `Features::emailVerification()`, and `Features::twoFactorAuthentication()` — only registration is disabled.
- `app/Actions/Fortify/CreateNewUser.php` and `resources/js/pages/auth/Register.vue` are preserved as dead code per decision D-02.
- The self-delete prevention has dual coverage: backend guard (T-09-04) and UI guard (T-09-09). Either alone is sufficient at ASVS Level 1; together they satisfy defence-in-depth.
- `UpdateUserRequest` strips the password from the update payload when empty (`UserController.php:52-54`), preventing accidental password clearing.

# Codebase Concerns

**Analysis Date:** 2026-04-10

## Tech Debt

**RefreshDatabase Commented Out in Test Suite:**
- Issue: `RefreshDatabase` trait is commented out in `tests/Pest.php` line 18, causing all feature tests that create database records to fail with "no such table: users" errors against the in-memory SQLite database.
- Files: `tests/Pest.php`
- Impact: **Critical.** 32 out of 40 tests fail. The entire test suite is non-functional. CI pipeline in `.github/workflows/tests.yml` runs `./vendor/bin/pest` and will also fail.
- Fix approach: Uncomment `->use(RefreshDatabase::class)` on line 18 of `tests/Pest.php`. Alternatively, use `LazilyRefreshDatabase::class` for faster test execution.

**Email Verification Not Enforced on User Model:**
- Issue: The `MustVerifyEmail` interface is imported but commented out on the `User` model (line 5 of `app/Models/User.php`). The `ProfileController` checks `instanceof MustVerifyEmail` (line 23), but this will always return `false` since the interface is not implemented.
- Files: `app/Models/User.php` (line 5), `app/Http/Controllers/Settings/ProfileController.php` (line 23)
- Impact: Email verification is configured in Fortify features (`config/fortify.php` line 149) and routes require `verified` middleware, but users never actually need to verify their email. The `verified` middleware is applied to dashboard and settings routes in `routes/web.php` and `routes/settings.php`, but without `MustVerifyEmail` on the model, it has no practical effect -- all users pass the `verified` check.
- Fix approach: Uncomment `use Illuminate\Contracts\Auth\MustVerifyEmail;` and add `implements MustVerifyEmail` to the `User` class. This will enable the full verification flow that is already built into the frontend (`resources/js/pages/auth/VerifyEmail.vue`).

**Placeholder-Only Dashboard:**
- Issue: The Dashboard page (`resources/js/pages/Dashboard.vue`) contains only `PlaceholderPattern` components with no actual content or functionality.
- Files: `resources/js/pages/Dashboard.vue`
- Impact: Low immediate impact -- this is a starter kit. However, it means the main authenticated landing page has no useful content.
- Fix approach: Replace placeholder components with actual dashboard widgets as application features are developed.

**Stub Unit Test:**
- Issue: The only unit test (`tests/Unit/ExampleTest.php`) asserts `true === true`, providing zero value.
- Files: `tests/Unit/ExampleTest.php`
- Impact: No unit test coverage for any application logic (validation rules, concerns traits, Fortify actions).
- Fix approach: Add unit tests for `PasswordValidationRules`, `ProfileValidationRules`, `CreateNewUser`, and `ResetUserPassword` logic. Remove or replace the stub test.

**Empty `something()` Helper Function:**
- Issue: `tests/Pest.php` defines an empty `something()` helper function on lines 47-50 that does nothing.
- Files: `tests/Pest.php` (lines 47-50)
- Impact: Dead code. Minor clutter, but could confuse developers into thinking it serves a purpose.
- Fix approach: Remove the empty function.

## Security Considerations

**Full User Object Shared via Inertia:**
- Risk: The entire `User` Eloquent model is shared to the frontend on every request via `HandleInertiaRequests::share()` at `app/Http/Middleware/HandleInertiaRequests.php` line 42. While `#[Hidden]` attributes exclude `password`, `two_factor_secret`, `two_factor_recovery_codes`, and `remember_token`, any new sensitive columns added to the users table will be exposed by default unless explicitly added to the `Hidden` attribute.
- Files: `app/Http/Middleware/HandleInertiaRequests.php` (line 42), `app/Models/User.php` (line 15)
- Current mitigation: `#[Hidden]` attribute on `User` model hides sensitive fields. The `User` TypeScript type at `resources/js/types/auth.ts` includes `[key: string]: unknown` which allows any extra properties.
- Recommendations: Consider using an Eloquent API Resource or explicit property selection instead of passing the full model. This provides defense-in-depth if a sensitive column is added later. Example: `'user' => $request->user()?->only(['id', 'name', 'email', 'avatar', 'email_verified_at'])`.

**v-html Used for QR Code SVG:**
- Risk: `v-html` is used at `resources/js/components/TwoFactorSetupModal.vue` line 174 to render QR code SVG. If the SVG content from the Fortify 2FA endpoint were compromised or modified, this could enable XSS.
- Files: `resources/js/components/TwoFactorSetupModal.vue` (line 174)
- Current mitigation: The SVG comes from Laravel Fortify's trusted server-side QR code generation endpoint, which generates SVG internally via a TOTP library. Risk is very low.
- Recommendations: This is an acceptable trade-off since the source is trusted server-side code. No immediate action needed, but avoid using `v-html` with user-supplied content elsewhere.

**Unencrypted Cookies for Appearance and Sidebar State:**
- Risk: The cookies `appearance` and `sidebar_state` are explicitly excluded from encryption at `bootstrap/app.php` line 17. These contain user preference data, not secrets.
- Files: `bootstrap/app.php` (line 17)
- Current mitigation: These cookies store non-sensitive UI preference values (`system`/`dark`/`light` and `true`/`false`).
- Recommendations: No action needed. This is intentional for JavaScript accessibility of theme preferences.

**Password Confirmation Timeout is 3 Hours:**
- Risk: `config/auth.php` line 115 sets `password_timeout` to 10800 seconds (3 hours) by default. Once a user confirms their password, they can access password-protected resources for 3 hours without re-confirming.
- Files: `config/auth.php` (line 115)
- Current mitigation: The security page requires password confirmation before viewing 2FA settings. The password update route has rate limiting (6 requests per minute).
- Recommendations: Consider reducing to 900 seconds (15 minutes) for production environments, especially since 2FA management is behind this confirmation.

**No Authorization Policies:**
- Risk: There are zero authorization policies or gates in the application. No files matching `*Policy*` exist. The only authorization pattern is the `auth` and `verified` middleware on routes.
- Files: None (no policies directory exists)
- Current mitigation: The application currently only has user self-service operations (profile edit, password change, 2FA), where the `auth` middleware is sufficient since users can only act on their own data via `$request->user()`.
- Recommendations: Add policies before introducing any multi-user or resource-sharing features. Create `app/Policies/UserPolicy.php` when user administration features are needed.

## Performance Bottlenecks

**No Current Performance Issues:**
- The application is a starter kit with minimal database queries. No N+1 patterns exist because there are no relationship queries. No raw SQL is used. No eager loading is needed yet.
- The `HandleInertiaRequests` middleware runs `$request->user()` on every request, which is a single query cached by the auth guard -- this is standard and optimal.

## Fragile Areas

**Two-Factor Authentication State Management:**
- Files: `resources/js/composables/useTwoFactorAuth.ts`, `resources/js/components/TwoFactorSetupModal.vue`, `resources/js/components/TwoFactorRecoveryCodes.vue`
- Why fragile: The composable uses module-level `ref()` state (lines 21-24 of `useTwoFactorAuth.ts`) shared across all component instances. State persists between page navigations. The `onUnmounted` cleanup in `resources/js/pages/settings/Security.vue` (line 43) must be called to avoid stale state. If a user navigates away from the security page mid-setup without the unmount firing, stale QR code and setup key data could persist.
- Safe modification: Always call `clearTwoFactorAuthData()` before and after setup flows. Test state cleanup across navigation scenarios.
- Test coverage: No frontend tests exist. Backend tests in `tests/Feature/Settings/SecurityTest.php` cover page rendering and password updates but do not test the 2FA enable/disable/confirm flow end-to-end.

**Profile Deletion Without Soft Deletes:**
- Files: `app/Http/Controllers/Settings/ProfileController.php` (lines 49-61), `app/Models/User.php`
- Why fragile: User deletion is a hard delete (`$user->delete()`) with no soft delete support. There is no cascade definition for related data. If models with foreign keys to users are added later, deletions will fail or leave orphaned records.
- Safe modification: Add `SoftDeletes` trait to User model before adding relationships. Define `ON DELETE CASCADE` on foreign keys in migrations.
- Test coverage: Covered by `tests/Feature/Settings/ProfileUpdateTest.php` (line 53) -- password verification and deletion are tested.

## Configuration Concerns

**Exception Handler is Empty:**
- Issue: The exception handler at `bootstrap/app.php` line 25-26 has an empty closure. No custom error reporting, no error tracking integration, no custom rendering for Inertia error pages.
- Files: `bootstrap/app.php` (lines 25-26)
- Impact: In production, unhandled exceptions will use Laravel's default behavior. There is no external error monitoring (Sentry, Flare, Bugsnag, etc.).
- Fix approach: Add an error tracking service integration. For Inertia apps, consider adding custom exception rendering to return proper Inertia error pages for 404/500/503 responses.

**No Custom Error Pages:**
- Issue: No custom Inertia error pages exist for 404, 403, 500, or 503 responses. The application relies on Laravel/Inertia defaults.
- Files: No error page files in `resources/js/pages/`
- Impact: Users will see generic error pages that may not match the application's design.
- Fix approach: Create error page components and configure Inertia's exception handling to render them.

**SSR Enabled but Bundle Not Configured:**
- Issue: `config/inertia.php` has `ssr.enabled => true` (line 19) but the SSR bundle path is commented out (line 21). In Inertia v3, SSR works automatically via Vite in dev mode, but production deployment requires the bundle to be built.
- Files: `config/inertia.php` (lines 18-22)
- Impact: SSR may not work in production if the build step is missing. The CI pipeline runs `npm run build` but not `npm run build:ssr` (though the script exists in `package.json`).
- Fix approach: Ensure `npm run build:ssr` is run for production deployments, or verify that Inertia v3 handles this automatically via the `@inertiajs/vite` plugin.

## Test Coverage Gaps

**2FA Enable/Disable/Confirm Flow Not Tested:**
- What's not tested: Enabling 2FA, confirming 2FA setup with OTP code, disabling 2FA, and recovery code generation/regeneration flows.
- Files: `tests/Feature/Settings/SecurityTest.php` only tests page rendering and password updates. No tests for POST to `two-factor.enable`, `two-factor.confirm`, or DELETE to `two-factor.disable`.
- Risk: Changes to the 2FA flow could break without being caught. The Fortify endpoints are tested by the Fortify package itself, but the integration between the application's custom UI and Fortify is not.
- Priority: Medium -- the Fortify package provides its own tests, but integration coverage would be valuable.

**No Frontend/E2E Tests:**
- What's not tested: All Vue component interactions, form submissions via Inertia, two-factor setup modal flow, appearance switching, sidebar toggling, and navigation.
- Files: No test files in `resources/js/` or any E2E test directory.
- Risk: Frontend regressions will not be caught by the test suite. The `TwoFactorSetupModal` has complex multi-step state management that is only testable through E2E or component tests.
- Priority: Medium -- add at minimum smoke tests for critical user flows (login, registration, profile update, 2FA setup).

**Profile Update Validation Edge Cases:**
- What's not tested: Validation behavior for duplicate emails, excessively long names, invalid email formats. Only the happy path and basic email verification status change are tested.
- Files: `tests/Feature/Settings/ProfileUpdateTest.php`
- Risk: Low -- Laravel's validation is well-tested, but application-specific rules should have coverage.
- Priority: Low.

**Welcome Page Not Meaningfully Tested:**
- What's not tested: `tests/Feature/ExampleTest.php` only checks the home route returns HTTP 200. No assertion on rendered content, auth state display, or feature flag behavior.
- Files: `tests/Feature/ExampleTest.php`, `resources/js/pages/Welcome.vue`
- Risk: Low -- the Welcome page is a landing page with minimal logic.
- Priority: Low.

## Dependencies at Risk

**Wayfinder is Pre-1.0:**
- Risk: `laravel/wayfinder` at `^0.1.14` and `@laravel/vite-plugin-wayfinder` at `^0.1.3` are both pre-1.0 packages. Breaking changes may occur between minor versions.
- Impact: Generated route/action files in `resources/js/actions/` and `resources/js/routes/` could need regeneration or code changes on package updates.
- Migration plan: Pin to exact versions in `composer.json` and `package.json` if stability is critical. Follow the Wayfinder changelog closely during updates.

**Laravel MCP is Pre-1.0:**
- Risk: `laravel/mcp` at `^0.4.4` (dev dependency) is pre-1.0. This is a development tool only and does not affect production.
- Impact: Minimal -- development tooling only.
- Migration plan: None needed for production. Update as needed for development.

## Missing Critical Features

**No API Routes or API Authentication:**
- Problem: The application has no API routes (`routes/api.php` does not exist) and no API authentication mechanism (no Sanctum, Passport, or API tokens).
- Blocks: Any mobile app, external integration, or headless frontend access.

**No Role/Permission System:**
- Problem: No role or permission management exists. All authenticated users have identical access.
- Blocks: Admin panels, content management, or any feature requiring differentiated access levels.

---

*Concerns audit: 2026-04-10*

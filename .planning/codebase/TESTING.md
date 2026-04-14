# Testing Patterns

**Analysis Date:** 2026-04-10

## Test Framework

**Runner:**
- Pest v4 with `pestphp/pest-plugin-laravel` v4.1
- Config: `phpunit.xml`
- Base class: `tests/TestCase.php` (extends Laravel's `Illuminate\Foundation\Testing\TestCase`)

**Assertion Libraries:**
- Pest `expect()` API for value assertions
- Laravel HTTP testing assertions (`assertOk()`, `assertRedirect()`, `assertSessionHasErrors()`, etc.)
- Inertia testing via `Inertia\Testing\AssertableInertia` (`assertInertia()`)

**Additional Testing Tools:**
- Mockery v1.6 (available but not actively used in current tests)
- Faker via `fakerphp/faker` v1.24 (used in factories)
- Collision v8.9 for better error output

**Run Commands:**
```bash
php artisan test --compact                    # Run all tests
php artisan test --compact --filter=testName  # Run specific test
php artisan test --compact tests/Feature/     # Run feature tests only
php artisan test --compact tests/Unit/        # Run unit tests only
composer test                                 # Full test suite (clears config, runs pint check, then tests)
composer ci:check                             # Full CI: lint:check, format:check, types:check, test
```

## Test File Organization

**Location:** Separate `tests/` directory (not co-located with source)

**Naming:**
- Feature tests: `tests/Feature/{Domain}/{FeatureName}Test.php`
- Unit tests: `tests/Unit/{Name}Test.php`
- Always suffixed with `Test.php`

**Structure:**
```
tests/
├── Pest.php                              # Pest configuration (base class, expectations, helpers)
├── TestCase.php                          # Custom base test case
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php        # Login, logout, rate limiting
│   │   ├── EmailVerificationTest.php     # Email verification flow
│   │   ├── PasswordConfirmationTest.php  # Password confirmation screen
│   │   ├── PasswordResetTest.php         # Password reset flow
│   │   ├── RegistrationTest.php          # User registration
│   │   ├── TwoFactorChallengeTest.php    # 2FA challenge screen
│   │   └── VerificationNotificationTest.php  # Verification email sending
│   ├── Settings/
│   │   ├── ProfileUpdateTest.php         # Profile CRUD operations
│   │   └── SecurityTest.php              # Password update, 2FA management
│   ├── DashboardTest.php                 # Dashboard access control
│   └── ExampleTest.php                   # Basic smoke test
└── Unit/
    └── ExampleTest.php                   # Basic unit test example
```

## Test Configuration

**Pest Configuration (`tests/Pest.php`):**
```php
pest()->extend(TestCase::class)
    // ->use(RefreshDatabase::class)  // NOTE: RefreshDatabase is commented out globally
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
```

**Key observations:**
- `RefreshDatabase` is commented out at the global level in `Pest.php`
- Feature tests use the custom `TestCase` class
- Tests rely on SQLite in-memory database (configured in `phpunit.xml`)
- Each test gets a fresh database via the in-memory SQLite setup

**Custom TestCase (`tests/TestCase.php`):**
```php
abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
```
- Provides `skipUnlessFortifyHas()` helper to conditionally skip tests based on Fortify feature flags

**PHPUnit Environment (`phpunit.xml`):**
```xml
<env name="APP_ENV" value="testing"/>
<env name="BCRYPT_ROUNDS" value="4"/>         <!-- Fast hashing in tests -->
<env name="CACHE_STORE" value="array"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>    <!-- In-memory SQLite -->
<env name="MAIL_MAILER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="TELESCOPE_ENABLED" value="false"/>
<env name="NIGHTWATCH_ENABLED" value="false"/>
```

## Test Structure

**Basic Test Pattern:**
```php
test('descriptive behavior statement', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('dashboard'));

    // Assert
    $response->assertOk();
});
```

**Test Naming:**
- Use `test()` function (not `it()`)
- Descriptions are lowercase sentences describing behavior
- Pattern: `test('subject can/cannot do action', function () { ... })`
- Examples:
  - `test('users can authenticate using the login screen', ...)`
  - `test('correct password must be provided to delete account', ...)`
  - `test('guests are redirected to the login page', ...)`
  - `test('email verification status is unchanged when the email address is unchanged', ...)`

**Conditional Test Skipping:**
```php
// Skip entire test file with beforeEach
beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

// Skip individual tests
test('feature-dependent test', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    // ...
});
```

**Feature Flag Testing:**
```php
// Override Fortify features for specific tests
Features::twoFactorAuthentication([
    'confirm' => true,
    'confirmPassword' => true,
]);

// Disable features entirely
config(['fortify.features' => []]);
```

## Assertion Patterns

**HTTP Response Assertions:**
```php
$response->assertOk();                        // 200 status
$response->assertRedirect(route('login'));     // Redirect check
$response->assertTooManyRequests();            // 429 status
$response->assertSessionHasNoErrors();
$response->assertSessionHasErrors('password');
$response->assertSessionHas('login.id', $user->id);
```

**Chained Assertions:**
```php
$response
    ->assertSessionHasNoErrors()
    ->assertRedirect(route('profile.edit'));
```

**Pest Expect Assertions:**
```php
expect($user->name)->toBe('Test User');
expect($user->email)->toBe('test@example.com');
expect($user->email_verified_at)->toBeNull();
expect($user->refresh()->email_verified_at)->not->toBeNull();
expect($user->fresh())->toBeNull();                    // Deleted user
expect($user->fresh())->not->toBeNull();               // Still exists
expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
```

**Inertia Assertions:**
```php
use Inertia\Testing\AssertableInertia as Assert;

$response->assertInertia(fn (Assert $page) => $page
    ->component('settings/Security')
    ->where('canManageTwoFactor', true)
    ->where('twoFactorEnabled', false)
);

$response->assertInertia(fn (Assert $page) => $page
    ->component('settings/Security')
    ->where('canManageTwoFactor', false)
    ->missing('twoFactorEnabled')
    ->missing('requiresConfirmation')
);
```

**Authentication State Assertions:**
```php
$this->assertAuthenticated();
$this->assertGuest();
```

## Mocking

**Notification Faking:**
```php
use Illuminate\Support\Facades\Notification;

Notification::fake();
// ... perform action ...
Notification::assertSentTo($user, ResetPassword::class);
Notification::assertSentTo($user, VerifyEmail::class);
Notification::assertNothingSent();
```

**Event Faking:**
```php
use Illuminate\Support\Facades\Event;

Event::fake();
// ... perform action ...
Event::assertDispatched(Verified::class);
Event::assertNotDispatched(Verified::class);
```

**Rate Limiter Manipulation:**
```php
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::increment(
    md5('login' . implode('|', [$user->email, '127.0.0.1'])),
    amount: 5
);
```

**Session Manipulation:**
```php
$this->actingAs($user)
    ->withSession(['auth.password_confirmed_at' => time()])
    ->get(route('security.edit'));
```

**Notification Callback Assertions:**
```php
Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
    $response = $this->post(route('password.update'), [
        'token' => $notification->token,
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('login'));

    return true;
});
```

## Factory Usage Patterns

**Basic Factory Usage:**
```php
$user = User::factory()->create();                            // Verified user
$user = User::factory()->unverified()->create();              // Unverified user
$user = User::factory()->create(['name' => 'Test User']);     // With overrides
```

**Available Factory States (`database/factories/UserFactory.php`):**
- Default: verified user with `'password'` as password
- `unverified()`: `email_verified_at` set to `null`
- `withTwoFactor()`: 2FA secret, recovery codes, and confirmed timestamp set

**Manual State Setup (when factory state is insufficient):**
```php
$user->forceFill([
    'two_factor_secret' => encrypt('test-secret'),
    'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    'two_factor_confirmed_at' => now(),
])->save();
```

**Factory Default Password:**
- Password is `'password'` (hashed and cached via static property)
- Use `'password'` in test login attempts

## Database Refresh Strategy

- `RefreshDatabase` is NOT globally applied (commented out in `tests/Pest.php`)
- Tests use SQLite in-memory database (`DB_DATABASE=:memory:` in `phpunit.xml`)
- The in-memory database is recreated per test process, providing isolation
- BCRYPT_ROUNDS set to `4` for fast password hashing

## Test Coverage Areas

**Well Tested:**
- Authentication flow: login, logout, rate limiting (`tests/Feature/Auth/AuthenticationTest.php`)
- Registration with Fortify feature gating (`tests/Feature/Auth/RegistrationTest.php`)
- Password reset full flow: request link, render screen, reset with valid/invalid token (`tests/Feature/Auth/PasswordResetTest.php`)
- Email verification: render, verify with valid/invalid hash/id, already-verified redirects (`tests/Feature/Auth/EmailVerificationTest.php`)
- Verification notification: sending and not-sending when already verified (`tests/Feature/Auth/VerificationNotificationTest.php`)
- Password confirmation screen rendering and auth requirements (`tests/Feature/Auth/PasswordConfirmationTest.php`)
- Two-factor challenge: redirect when not authenticated, rendering (`tests/Feature/Auth/TwoFactorChallengeTest.php`)
- Profile CRUD: display, update, email verification status, delete with correct/incorrect password (`tests/Feature/Settings/ProfileUpdateTest.php`)
- Security settings: page rendering with/without password confirmation, password update, 2FA feature toggling (`tests/Feature/Settings/SecurityTest.php`)
- Dashboard: guest redirect, authenticated access (`tests/Feature/DashboardTest.php`)
- Home page: smoke test (`tests/Feature/ExampleTest.php`)

**Not Tested / Gaps:**
- No unit tests for business logic (only placeholder `tests/Unit/ExampleTest.php`)
- No tests for Inertia middleware (`HandleInertiaRequests`, `HandleAppearance`)
- No tests for `CreateNewUser` action directly (tested indirectly via registration)
- No tests for `ResetUserPassword` action directly (tested indirectly via password reset)
- No frontend/browser tests (no Dusk or similar)
- No API tests (no API routes exist currently)
- No architecture tests (Pest arch testing available but unused)

## Test Types

**Feature Tests:**
- All meaningful tests are Feature tests
- Test HTTP request/response cycles through full Laravel stack
- Use `$this->get()`, `$this->post()`, `$this->patch()`, `$this->put()`, `$this->delete()`
- Files: `tests/Feature/Auth/*.php`, `tests/Feature/Settings/*.php`, `tests/Feature/DashboardTest.php`

**Unit Tests:**
- Only a placeholder exists: `tests/Unit/ExampleTest.php`
- No real unit tests for isolated business logic

**E2E/Browser Tests:**
- Not used (no Dusk or browser testing setup)

## Common Patterns

**Authenticated User Test:**
```php
test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
```

**Guest Redirect Test:**
```php
test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});
```

**Form Submission Test:**
```php
test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});
```

**Validation Error Test:**
```php
test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
```

**Fortify Feature-Gated Test File:**
```php
beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));
    $response->assertOk();
});
```

**Inertia Component Assertion Test:**
```php
test('security page is displayed', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Security')
            ->where('canManageTwoFactor', true)
            ->where('twoFactorEnabled', false),
        );
});
```

**Two-Factor Authentication Login Test:**
```php
test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});
```

## How to Create New Tests

**Create a Feature test:**
```bash
php artisan make:test --pest Feature/YourDomain/YourFeatureTest
```

**Create a Unit test:**
```bash
php artisan make:test --pest --unit Unit/YourUnitTest
```

**Conventions to follow:**
1. Use `test()` function, not `it()`
2. Use lowercase descriptive names: `test('users can do something', ...)`
3. Use `User::factory()->create()` for test users (password is `'password'`)
4. Use named routes: `route('route.name')` not hardcoded URLs
5. Use `$this->actingAs($user)` for authenticated tests
6. Use `from(route('...'))` when testing redirect-back behavior
7. Use `beforeEach()` with `skipUnlessFortifyHas()` for feature-gated test files
8. Assert both success and failure paths
9. Use `expect()` for value assertions, HTTP assertions for responses
10. Import `Inertia\Testing\AssertableInertia as Assert` for Inertia page assertions

---

*Testing analysis: 2026-04-10*

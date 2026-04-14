# Codebase Structure

**Analysis Date:** 2026-04-10

## Directory Layout

```
fras/
├── app/                    # PHP application code (PSR-4: App\)
│   ├── Actions/            # Fortify action classes (business logic)
│   ├── Concerns/           # Shared traits (validation rules)
│   ├── Http/               # HTTP layer (controllers, middleware, requests)
│   ├── Models/             # Eloquent models
│   └── Providers/          # Service providers
├── bootstrap/              # Application bootstrap and cache
│   ├── app.php             # App configuration (routing, middleware, exceptions)
│   └── cache/              # Framework bootstrap cache (generated)
├── config/                 # Configuration files
├── database/               # Migrations, factories, seeders
│   ├── factories/          # Model factories
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── public/                 # Web root (index.php, compiled assets)
│   └── build/              # Vite-compiled production assets (generated)
├── resources/              # Frontend source code and views
│   ├── css/                # CSS entry point (Tailwind v4)
│   ├── js/                 # TypeScript/Vue source code
│   └── views/              # Blade templates (only app.blade.php)
├── routes/                 # Route definitions
├── storage/                # App storage, logs, framework cache
├── tests/                  # Pest test suite
│   ├── Feature/            # Feature/integration tests
│   └── Unit/               # Unit tests
└── vendor/                 # Composer dependencies (not committed)
```

## Directory Purposes

**`app/Actions/Fortify/`:**
- Purpose: Custom action classes implementing Fortify contracts
- Contains: `CreateNewUser.php`, `ResetUserPassword.php`
- Key files: These implement `CreatesNewUsers` and `ResetsUserPasswords` contracts
- Pattern: Each action validates input via `Validator::make()` and uses validation rule traits from `app/Concerns/`

**`app/Concerns/`:**
- Purpose: Reusable PHP traits for shared validation logic
- Contains: `PasswordValidationRules.php`, `ProfileValidationRules.php`
- Key files: Used by both Form Requests and Fortify Actions to share identical validation rules

**`app/Http/Controllers/`:**
- Purpose: Handle HTTP requests and return Inertia responses
- Contains: `Controller.php` (abstract base), `Settings/ProfileController.php`, `Settings/SecurityController.php`
- Pattern: Controllers organized in subdirectories by domain (e.g., `Settings/`)

**`app/Http/Middleware/`:**
- Purpose: Request/response processing pipeline
- Contains: `HandleInertiaRequests.php` (shared props), `HandleAppearance.php` (theme cookie)
- Key files: `HandleInertiaRequests.php` defines all data shared to every Inertia page

**`app/Http/Requests/Settings/`:**
- Purpose: Form Request classes for input validation
- Contains: `ProfileUpdateRequest.php`, `ProfileDeleteRequest.php`, `PasswordUpdateRequest.php`, `TwoFactorAuthenticationRequest.php`
- Pattern: One Form Request per controller action that accepts user input

**`app/Models/`:**
- Purpose: Eloquent ORM models
- Contains: `User.php` (the only model)
- Pattern: Uses PHP 8 attribute annotations (`#[Fillable]`, `#[Hidden]`) instead of `$fillable`/`$hidden` arrays

**`app/Providers/`:**
- Purpose: Service providers that bootstrap the application
- Contains: `AppServiceProvider.php` (defaults), `FortifyServiceProvider.php` (auth configuration)
- Key files: `FortifyServiceProvider.php` maps all auth Inertia views and registers Fortify actions

**`resources/js/pages/`:**
- Purpose: Inertia page components (rendered by `Inertia::render()`)
- Contains: `Welcome.vue`, `Dashboard.vue`, `auth/*.vue` (7 auth pages), `settings/*.vue` (3 settings pages)
- Pattern: Directory structure mirrors URL paths. File name matches the second argument of `Inertia::render()`

**`resources/js/layouts/`:**
- Purpose: Layout wrapper components that provide consistent page structure
- Contains: `AppLayout.vue`, `AuthLayout.vue`, `app/AppSidebarLayout.vue`, `app/AppHeaderLayout.vue`, `auth/AuthSimpleLayout.vue`, `auth/AuthSplitLayout.vue`, `auth/AuthCardLayout.vue`, `settings/Layout.vue`
- Pattern: Top-level layouts delegate to specific variants. `AppLayout.vue` -> `AppSidebarLayout.vue`, `AuthLayout.vue` -> `AuthSimpleLayout.vue`. Alternative variants exist for future use.

**`resources/js/components/`:**
- Purpose: Reusable Vue components
- Contains: App-level components (23 files), headless UI primitives in `ui/` subdirectory
- Pattern: App components are flat in `components/`. UI primitives organized by type in `components/ui/<component-name>/`

**`resources/js/components/ui/`:**
- Purpose: shadcn-vue design system primitives (based on reka-ui)
- Contains: `button/`, `card/`, `dialog/`, `dropdown-menu/`, `input/`, `label/`, `sidebar/`, `sonner/`, `spinner/`, `tooltip/`, `select/`, `separator/`, `skeleton/`, `checkbox/`, `alert/`, `avatar/`, `collapsible/`, `navigation-menu/`, `input-otp/`
- Pattern: Each UI element has its own directory with component + barrel export. Configured via `components.json` (shadcn-vue style: `new-york-v4`, base color: `neutral`)

**`resources/js/composables/`:**
- Purpose: Vue 3 composable functions (reusable stateful logic)
- Contains: `useAppearance.ts` (theme management), `useCurrentUrl.ts` (URL matching), `useInitials.ts` (name initials), `useTwoFactorAuth.ts` (2FA HTTP calls)
- Pattern: Each exports a `use*` function returning typed objects

**`resources/js/lib/`:**
- Purpose: Utility functions
- Contains: `utils.ts` (`cn()` class merge helper, `toUrl()` href normalizer), `flashToast.ts` (global Inertia flash toast listener)

**`resources/js/types/`:**
- Purpose: TypeScript type definitions
- Contains: `auth.ts` (User, Auth types), `navigation.ts` (BreadcrumbItem, NavItem), `ui.ts` (Appearance, FlashToast), `global.d.ts` (Inertia shared props, Vite env), `vue-shims.d.ts`
- Key files: `index.ts` re-exports all types. `global.d.ts` defines `InertiaConfig.sharedPageProps` shape.

**`resources/js/actions/`:**
- Purpose: Wayfinder-generated typed route functions organized by controller namespace
- Contains: `App/Http/Controllers/Settings/ProfileController.ts`, `App/Http/Controllers/Settings/SecurityController.ts`, plus Fortify and Illuminate controller bindings
- Pattern: Auto-generated. Import via `@/actions/App/Http/Controllers/Settings/ProfileController`

**`resources/js/routes/`:**
- Purpose: Wayfinder-generated typed route functions organized by named route
- Contains: `index.ts` (home, dashboard, login, logout, register), subdirectories for each route group (profile, security, appearance, password, two-factor, verification, etc.)
- Pattern: Auto-generated. Import via `@/routes/profile` for named routes

**`resources/js/wayfinder/`:**
- Purpose: Wayfinder runtime utilities (query param handling, URL defaults)
- Contains: `index.ts`
- Pattern: Auto-generated. Used internally by all `@/routes/` and `@/actions/` imports

## Key File Locations

**Entry Points:**
- `bootstrap/app.php`: Application bootstrap (routing, middleware, exceptions)
- `resources/js/app.ts`: Frontend Inertia app creation, layout resolution, theme/toast initialization
- `resources/views/app.blade.php`: Root Blade template (HTML shell, Vite assets, dark mode inline script)
- `resources/css/app.css`: CSS entry (Tailwind v4, shadcn-vue theme variables)

**Configuration:**
- `config/fortify.php`: Authentication features (registration, password reset, email verification, 2FA with confirm+confirmPassword)
- `config/inertia.php`: Inertia SSR (enabled), page component paths and extensions
- `config/app.php`: App name, environment, locale
- `config/auth.php`: Auth guards and providers
- `config/database.php`: Database connections
- `config/session.php`: Session driver and lifetime
- `vite.config.ts`: Vite plugins (Laravel, Inertia, Tailwind, Vue, Wayfinder)
- `components.json`: shadcn-vue configuration (style, aliases, icon library)
- `tsconfig.json`: TypeScript config (ESNext target, `@/*` path alias to `resources/js/*`)

**Core Logic:**
- `app/Http/Controllers/Settings/ProfileController.php`: User profile CRUD
- `app/Http/Controllers/Settings/SecurityController.php`: Password update, 2FA management display
- `app/Actions/Fortify/CreateNewUser.php`: User registration logic
- `app/Actions/Fortify/ResetUserPassword.php`: Password reset logic
- `app/Providers/FortifyServiceProvider.php`: Auth view mapping, rate limiting, action registration

**Testing:**
- `tests/Pest.php`: Pest configuration (binds TestCase, custom expectations)
- `tests/TestCase.php`: Base test case class
- `tests/Feature/Auth/`: Authentication tests (7 files)
- `tests/Feature/Settings/`: Settings tests (2 files)
- `tests/Feature/DashboardTest.php`: Dashboard access test
- `tests/Feature/ExampleTest.php`: Basic smoke test
- `tests/Unit/ExampleTest.php`: Unit test example

## Naming Conventions

**PHP Files:**
- Controllers: `PascalCase` + `Controller` suffix (e.g., `ProfileController.php`)
- Form Requests: `PascalCase` + `Request` suffix (e.g., `ProfileUpdateRequest.php`)
- Actions: `PascalCase` verb phrase (e.g., `CreateNewUser.php`, `ResetUserPassword.php`)
- Concerns/Traits: `PascalCase` + `Rules` suffix for validation (e.g., `PasswordValidationRules.php`)
- Models: `PascalCase` singular (e.g., `User.php`)
- Providers: `PascalCase` + `ServiceProvider` suffix (e.g., `FortifyServiceProvider.php`)

**Vue Files:**
- Pages: `PascalCase` (e.g., `Dashboard.vue`, `Profile.vue`, `Login.vue`)
- Layouts: `PascalCase` with `Layout` suffix (e.g., `AppLayout.vue`, `AuthLayout.vue`)
- Components: `PascalCase` (e.g., `AppSidebar.vue`, `DeleteUser.vue`, `InputError.vue`)
- UI Components: `PascalCase` matching shadcn naming (e.g., `Button.vue`, `Input.vue`, `SidebarTrigger.vue`)

**TypeScript Files:**
- Composables: `camelCase` with `use` prefix (e.g., `useAppearance.ts`, `useCurrentUrl.ts`)
- Types: `camelCase` (e.g., `auth.ts`, `navigation.ts`)
- Utilities: `camelCase` (e.g., `utils.ts`, `flashToast.ts`)
- Wayfinder generated: `PascalCase` matching controller names, or `index.ts` for route groups

**Directories:**
- PHP: `PascalCase` (e.g., `Controllers/`, `Middleware/`, `Settings/`)
- Frontend: `kebab-case` for UI component dirs (e.g., `dropdown-menu/`, `input-otp/`), `camelCase` for non-UI dirs (e.g., `composables/`, `layouts/`)
- Pages/routes: `kebab-case` matching URL segments (e.g., `auth/`, `settings/`, `two-factor/`)

## Where to Add New Code

**New Feature (full-stack):**
- Route: Add to `routes/web.php` or create a new `routes/<feature>.php` and require it from `routes/web.php`
- Controller: `app/Http/Controllers/<Feature>/` (use `php artisan make:controller`)
- Form Requests: `app/Http/Requests/<Feature>/` (use `php artisan make:request`)
- Model: `app/Models/<ModelName>.php` (use `php artisan make:model -mfs`)
- Vue Page: `resources/js/pages/<feature>/<PageName>.vue`
- Tests: `tests/Feature/<Feature>/`

**New Inertia Page:**
- Create Vue component in `resources/js/pages/<section>/<PageName>.vue`
- Add route in `routes/web.php` using `Route::inertia('<path>', '<section>/<PageName>')` for simple pages, or create a controller for pages with logic
- Layout is auto-assigned in `resources/js/app.ts` based on page name prefix. Add a new case to the switch if a new section needs a distinct layout.
- Run `npm run dev` or `npm run build` to regenerate Wayfinder routes

**New Controller:**
- Place in `app/Http/Controllers/<Domain>/` matching the domain/feature area
- Create corresponding Form Requests in `app/Http/Requests/<Domain>/`
- After creating, run Wayfinder generation to get typed frontend route functions

**New Vue Component:**
- App-specific: `resources/js/components/<ComponentName>.vue`
- Reusable UI primitive: `resources/js/components/ui/<component-name>/<ComponentName>.vue` (use shadcn-vue CLI if available)

**New Composable:**
- Place in `resources/js/composables/use<Name>.ts`
- Export a `use<Name>` function returning a typed object
- Define return type explicitly (e.g., `type Use<Name>Return = { ... }`)

**New TypeScript Type:**
- Add to existing file in `resources/js/types/` if it fits (auth, navigation, ui)
- Create new file in `resources/js/types/` for new domains
- Re-export from `resources/js/types/index.ts`

**New Validation Rules:**
- If shared between Form Requests/Actions: create a trait in `app/Concerns/`
- If specific to one request: define directly in the Form Request's `rules()` method

**New Migration:**
- Use `php artisan make:migration`
- Place in `database/migrations/` with timestamp prefix

**New Test:**
- Feature tests: `tests/Feature/<Domain>/<TestName>Test.php` (use `php artisan make:test --pest`)
- Unit tests: `tests/Unit/<TestName>Test.php` (use `php artisan make:test --pest --unit`)

## Special Directories

**`resources/js/actions/`:**
- Purpose: Wayfinder auto-generated TypeScript route functions by controller namespace
- Generated: Yes (by `@laravel/vite-plugin-wayfinder` Vite plugin)
- Committed: Yes (checked into source control)

**`resources/js/routes/`:**
- Purpose: Wayfinder auto-generated TypeScript route functions by named route
- Generated: Yes (by `@laravel/vite-plugin-wayfinder` Vite plugin)
- Committed: Yes (checked into source control)

**`resources/js/wayfinder/`:**
- Purpose: Wayfinder runtime helpers (query params, URL defaults, validation)
- Generated: Yes (by `@laravel/vite-plugin-wayfinder` Vite plugin)
- Committed: Yes

**`public/build/`:**
- Purpose: Vite-compiled production frontend assets
- Generated: Yes (by `npm run build`)
- Committed: Yes (for deployment)

**`bootstrap/cache/`:**
- Purpose: Framework bootstrap cache files
- Generated: Yes (by Laravel)
- Committed: No (gitignored)

**`storage/`:**
- Purpose: Application storage (logs, cache, sessions, uploads)
- Generated: Yes (runtime)
- Committed: Directory structure only (contents gitignored)

**`vendor/`:**
- Purpose: Composer PHP dependencies
- Generated: Yes (by `composer install`)
- Committed: No (gitignored)

**`node_modules/`:**
- Purpose: npm JavaScript dependencies
- Generated: Yes (by `npm install`)
- Committed: No (gitignored)

---

*Structure analysis: 2026-04-10*

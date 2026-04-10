<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `wayfinder-development` — Use this skill for Laravel Wayfinder which auto-generates typed functions for Laravel controllers and routes. ALWAYS use this skill when frontend code needs to call backend routes or controller actions. Trigger when: connecting any React/Vue/Svelte/Inertia frontend to Laravel controllers, routes, building end-to-end features with both frontend and backend, wiring up forms or links to backend endpoints, fixing route-related TypeScript errors, importing from @/actions or @/routes, or running wayfinder:generate. Use Wayfinder route functions instead of hardcoded URLs. Covers: wayfinder() vite plugin, .url()/.get()/.post()/.form(), query params, route model binding, tree-shaking. Do not use for backend-only task
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<!-- GSD:project-start source:PROJECT.md -->
## Project

**HDS-FRAS — Face Recognition Alert System**

A web-based Face Recognition Alert System that integrates with AI Intelligent IP Cameras over MQTT. Operators monitor camera locations on a map, receive real-time alerts when personnel are recognized, and manage personnel enrollment across cameras from a central admin interface. Built as an extension of an existing Laravel 13 + Vue 3 + Inertia v3 application for HDSystem (HyperDrive System), deployed at a single-site facility in Butuan City, Philippines.

**Core Value:** Operators see every matched-face recognition event in real time on a map-based dashboard with severity-classified alerts, so critical events (block-list matches) are never missed.

### Constraints

- **Camera protocol:** MQTT v3.1.1, QoS 0, JSON payloads. Max 1000 personnel per enrollment batch. Only one batch in-flight per camera.
- **Photo limits:** Enrollment photos must be <=1MB, <=1080p. Camera fetches photos via HTTP URL (picURI must be network-reachable from camera).
- **Network:** Camera subnet must reach the Laravel server for MQTT and photo download. No NAT translation awareness on cameras.
- **Concurrency:** WithoutOverlapping middleware required — one enrollment job per camera at a time.
- **Storage:** Face crops up to 1MB, scene images up to 2MB per event. Retention policy required to manage disk growth.
- **Map:** Mapbox GL JS with custom HelderDene account styles (dark + light). Free tier sufficient for single command center.
- **Stack additions:** php-mqtt/laravel-client, Intervention Image v3, Laravel Reverb, Mapbox GL JS v3, Laravel Echo with Pusher adapter.
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Languages
- PHP ^8.3 (runtime: 8.4.19) - Backend application code, controllers, models, migrations
- TypeScript ^5.2.2 - Frontend application code, Vue components, composables, types
- CSS (Tailwind CSS v4) - Styling via `resources/css/app.css`
## Runtime
- PHP 8.4.19 (CLI, NTS, clang 15.0.0) - Served by Laravel Herd
- Node.js v22.14.0 - Frontend build tooling and SSR
- Composer v2 - PHP dependencies
- npm - JavaScript dependencies
## Frameworks
- Laravel v13.0 (`laravel/framework`) - PHP web framework
- Vue.js v3.5.13 (`vue`) - Frontend reactive UI framework
- Inertia.js v3.0 (`@inertiajs/vue3` + `inertiajs/inertia-laravel`) - SPA bridge between Laravel and Vue
- Tailwind CSS v4.1.1 (`tailwindcss`) - Utility-first CSS framework
- Laravel Fortify v1.34 (`laravel/fortify`) - Backend authentication scaffolding (login, registration, password reset, email verification, 2FA)
- Pest v4.4 (`pestphp/pest`) - PHP testing framework
- Pest Laravel Plugin v4.1 (`pestphp/pest-plugin-laravel`) - Laravel-specific Pest helpers
- Vite v8.0.0 (`vite`) - Frontend build tool and dev server
- Laravel Vite Plugin v3.0.0 (`laravel-vite-plugin`) - Laravel/Vite integration
- Inertia Vite Plugin v3.0.0 (`@inertiajs/vite`) - SSR support for Inertia in Vite dev mode
- Laravel Pint v1.27 (`laravel/pint`) - PHP code formatter (PSR-12 / Laravel style)
- ESLint v9.17.0 (`eslint`) - JavaScript/TypeScript linting
- Prettier v3.4.2 (`prettier`) - JavaScript/TypeScript formatting
- vue-tsc v2.2.4 (`vue-tsc`) - Vue TypeScript type checking
## Key Dependencies
- `@inertiajs/vue3` ^3.0.0 - Inertia client-side adapter for Vue 3
- `@inertiajs/vite` ^3.0.0 - Inertia SSR via Vite (no separate Node server needed in dev)
- `laravel/wayfinder` ^0.1.14 - Auto-generates typed TypeScript functions for Laravel routes/controllers
- `@laravel/vite-plugin-wayfinder` ^0.1.3 - Vite plugin for Wayfinder code generation
- `reka-ui` ^2.6.1 - Headless UI component primitives (Vue 3)
- `lucide-vue-next` ^0.468.0 - Icon library for Vue
- `vue-sonner` ^2.0.0 - Toast notification component
- `vue-input-otp` ^0.3.2 - OTP input component (used for 2FA)
- `class-variance-authority` ^0.7.1 - Component variant management (CVA)
- `clsx` ^2.1.1 - Conditional className utility
- `tailwind-merge` ^3.2.0 - Merge Tailwind CSS classes intelligently
- `tw-animate-css` ^1.2.5 - Tailwind CSS animation utilities
- `@vueuse/core` ^12.8.2 - Collection of Vue composition utilities
- `laravel/boost` ^2.0 - MCP server for AI-assisted development (database queries, doc search, schema inspection)
- `laravel/pail` ^1.2.5 - Real-time log viewer for Laravel
- `laravel/sail` ^1.53 - Docker dev environment (available but not primary; Herd is used)
- `laravel/tinker` ^3.0 - REPL for Laravel
- `fakerphp/faker` ^1.24 - Test data generation
- `mockery/mockery` ^1.6 - PHP mocking framework
- `nunomaduro/collision` ^8.9 - Better error reporting in CLI
- `concurrently` ^9.0.1 - Run multiple dev processes simultaneously
## Configuration
- Config: `tsconfig.json`
- Target: ESNext, Module: ESNext, Module Resolution: bundler
- Strict mode enabled
- Path alias: `@/*` maps to `./resources/js/*`
- Types: `vite/client`
- JSX: preserve (Vue JSX support)
- Config: `vite.config.ts`
- Entry points: `resources/css/app.css`, `resources/js/app.ts`
- Plugins: laravel, inertia (SSR), tailwindcss, vue, wayfinder (with `formVariants: true`)
- Hot reload enabled (`refresh: true`)
- Config: `eslint.config.js`
- Vue + TypeScript flat config (`@vue/eslint-config-typescript`)
- Enforces consistent type imports (`prefer: 'type-imports'`)
- Enforces alphabetized import ordering by group
- 1TBS brace style, padding around control statements
- Prettier integration to avoid conflicts
- Ignores: `vendor`, `node_modules`, `public`, `bootstrap/ssr`, generated files (`resources/js/actions/**`, `resources/js/routes/**`, `resources/js/wayfinder/**`, `resources/js/components/ui/*`)
- Config: `.prettierrc`
- Semicolons: yes, Single quotes: yes, Tab width: 4, Print width: 80
- Plugin: `prettier-plugin-tailwindcss` (auto-sorts Tailwind classes)
- Tailwind functions recognized: `clsx`, `cn`, `cva`
- Tailwind stylesheet: `resources/css/app.css`
- Laravel Pint v1.27 (`vendor/bin/pint`)
- Run with `--dirty --format agent` before finalizing changes
- CI runs `composer lint` (which is `pint --parallel`)
- Enabled in `config/inertia.php` (`ssr.enabled: true`)
- SSR URL: `http://127.0.0.1:13714`
- Pages discovered from `resources/js/pages` with extensions: js, jsx, svelte, ts, tsx, vue
- `.env.example` present - copy to `.env` and generate key
- Key vars: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`, `DB_CONNECTION`
- Vite-exposed: `VITE_APP_NAME`
## Build & Dev Commands
## Database
- Database file: `database/database.sqlite`
- Session driver: database
- Cache store: database
- Queue connection: database
## Platform Requirements
- PHP >= 8.3 with extensions: pdo_sqlite (default), bcrypt
- Node.js 22+ (for Vite, SSR, frontend tooling)
- Laravel Herd (serves the application at `https://fras.test`)
- GitHub Actions (`.github/workflows/lint.yml`, `.github/workflows/tests.yml`)
- Tests run on PHP matrix: 8.3, 8.4, 8.5
- Node.js 22 in CI
- Coverage tool: xdebug
- Laravel Cloud recommended (per project guidelines)
- SSR enabled by default
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Naming Patterns
- Controllers: `PascalCase` + `Controller` suffix (e.g., `ProfileController`, `SecurityController`)
- Models: `PascalCase` singular (e.g., `User`)
- Form Requests: `PascalCase` + `Request` suffix (e.g., `ProfileUpdateRequest`, `PasswordUpdateRequest`)
- Actions: `PascalCase` verb-noun (e.g., `CreateNewUser`, `ResetUserPassword`)
- Concerns (Traits): `PascalCase` descriptive (e.g., `PasswordValidationRules`, `ProfileValidationRules`)
- Migrations: `YYYY_MM_DD_HHMMSS_snake_case_description` (e.g., `2025_08_14_170933_add_two_factor_columns_to_users_table.php`)
- Factories: `ModelNameFactory` (e.g., `UserFactory`)
- Seeders: `ModelNameSeeder` or `DatabaseSeeder` (e.g., `DatabaseSeeder`)
- Service Providers: `PascalCase` + `ServiceProvider` suffix (e.g., `AppServiceProvider`, `FortifyServiceProvider`)
- Pages: `PascalCase.vue` in lowercase subdirectories (e.g., `pages/auth/Login.vue`, `pages/settings/Profile.vue`)
- Components: `PascalCase.vue` (e.g., `Heading.vue`, `InputError.vue`, `DeleteUser.vue`)
- UI Components: `PascalCase.vue` in kebab-case directories (e.g., `components/ui/button/Button.vue`, `components/ui/input-otp/InputOTP.vue`)
- Layouts: `PascalCase.vue` (e.g., `AppLayout.vue`, `AuthLayout.vue`, `Layout.vue`)
- Composables: `camelCase` with `use` prefix (e.g., `useAppearance.ts`, `useCurrentUrl.ts`, `useTwoFactorAuth.ts`)
- Types: `camelCase.ts` or `PascalCase.d.ts` (e.g., `auth.ts`, `navigation.ts`, `global.d.ts`)
- Functions: `camelCase` (e.g., `getInitials`, `updateTheme`, `initializeFlashToast`)
- Types/Interfaces: `PascalCase` (e.g., `User`, `Auth`, `BreadcrumbItem`, `NavItem`, `FlashToast`)
- Constants: `camelCase` (e.g., `appName`, `sidebarNavItems`)
- Composable return types: `UsePascalCaseReturn` (e.g., `UseAppearanceReturn`, `UseInitialsReturn`)
- Methods: `camelCase` (e.g., `passwordRules`, `profileRules`, `configureDefaults`)
- Variables: `$camelCase` (e.g., `$throttleKey`, `$verificationUrl`)
- Named routes: `kebab-or-dot.notation` (e.g., `profile.edit`, `profile.update`, `security.edit`, `two-factor.login`, `user-password.update`)
- URL paths: `/kebab-case` (e.g., `/settings/profile`, `/settings/security`, `/settings/appearance`)
## Code Style
- Tool: Laravel Pint with `laravel` preset
- Config: `pint.json`
- Run: `vendor/bin/pint --dirty --format agent` (for modified files)
- Run: `vendor/bin/pint --parallel` (full codebase)
- Always run Pint before finalizing PHP changes
- Tool: Prettier v3
- Config: `.prettierrc`
- Key settings:
- Run: `npm run format` (write) or `npm run format:check` (verify)
- Tool: ESLint v9 with flat config
- Config: `eslint.config.js`
- Key rules enforced:
- Run: `npm run lint` (fix) or `npm run lint:check` (verify)
- Config: `tsconfig.json`
- Strict mode: enabled
- Target: ESNext
- Module resolution: bundler
- Path alias: `@/*` maps to `./resources/js/*`
- JSX: preserve (Vue JSX)
## Import Organization
- Order observed in controllers:
- Use full class imports, no glob imports
## Error Handling
- Form validation: Use Form Request classes in `app/Http/Requests/` for controller method validation
- Fortify Actions: Use `Validator::make($input, [...rules...])->validate()` for action-based validation
- Validation rules: Extract reusable rules into traits in `app/Concerns/` (e.g., `PasswordValidationRules`, `ProfileValidationRules`)
- Password validation: `Password::default()` with production-specific requirements set in `AppServiceProvider`
- Use `InputError` component (`@/components/InputError.vue`) to display per-field errors
- Access errors from Inertia Form's `v-slot="{ errors }"` destructure
- Pattern: `<InputError :message="errors.field_name" />`
- Use try/catch in composables with error state stored in `ref<string[]>`
- Pattern in `useTwoFactorAuth.ts`: catch errors, push to `errors` ref, set data to null
## Logging
## Comments
- Use PHPDoc blocks on class methods with single-line description
- Pattern: `/** Show the user's profile settings page. */`
- Use `@return` array shape annotations for type hints: `@return array<string, mixed>`
- Use `@param` array shape annotations: `@param array<string, string> $input`
- Minimal inline comments
- Comments used sparingly for context: `// This will set light / dark mode on page load...`
- End-of-line comments with `...` trailing style
## Function Design
- Single-responsibility methods: `edit`, `update`, `destroy`
- Use typed parameters and return types on all methods
- Use Form Request type-hints for request parameters
- Return `Inertia::render()` for page responses, `RedirectResponse` for mutations
- Use `to_route()` or `back()` for redirects after mutations
- Use `Inertia::flash()` for success toast messages: `Inertia::flash('toast', ['type' => 'success', 'message' => __('...')])` 
- Implement Fortify contract interfaces (e.g., `CreatesNewUsers`, `ResetsUserPasswords`)
- Use traits for shared validation rules
- Pattern: validate then perform action
- Split `boot()` into private `configure*()` methods for organization
- Pattern in `FortifyServiceProvider`: `configureActions()`, `configureViews()`, `configureRateLimiting()`
- Use `<script setup lang="ts">` exclusively
- Define props with `defineProps<{...}>()` using TypeScript type syntax
- For defaults, use `withDefaults(defineProps<Props>(), { ... })`
- Use `defineOptions()` for layout metadata (breadcrumbs, title, description)
- Use `type Props = { ... }` alias when props type is reused or complex
- Export both the composable function and its return type
- Name: `useFeatureName()` returning `UseFeatureNameReturn` type
- Export standalone utility functions alongside composable when useful (e.g., `getInitials` and `useInitials`)
## Module Design
- Controllers extend abstract `App\Http\Controllers\Controller`
- Middleware implements `HasMiddleware` interface for controller-level middleware
- Traits in `app/Concerns/` for shared validation and behavior
- UI components use barrel `index.ts` files exporting components and variant types
- Pattern in `components/ui/button/index.ts`:
- Types use barrel `types/index.ts` re-exporting from submodules
## Design Patterns
- Used for Fortify authentication actions: `CreateNewUser`, `ResetUserPassword`
- Implement Fortify contract interfaces
- Compose validation from traits
- PHP traits for reusable validation rule sets
- Used by both Form Requests and Actions
- `PasswordValidationRules`: `passwordRules()`, `currentPasswordRules()`
- `ProfileValidationRules`: `profileRules()`, `nameRules()`, `emailRules()`
- Organized in subdirectories matching controller groups (e.g., `Settings/`)
- Compose validation rules from Concerns traits
- All extend `Illuminate\Foundation\Http\FormRequest`
- Auto-generated typed route functions in `resources/js/actions/` (controller-based) and `resources/js/routes/` (route-name-based)
- Import controller actions: `import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController'`
- Import named routes: `import { edit } from '@/routes/profile'`
- Use `.form()` for Inertia Form bindings: `v-bind="ProfileController.update.form()"`
- Use function calls for URLs: `edit()`, `register()`, `dashboard()`
- NEVER hardcode URLs; always use Wayfinder-generated functions
- Automatic layout selection in `app.ts` based on page component name path
- `auth/*` pages use `AuthLayout`
- `settings/*` pages use `[AppLayout, SettingsLayout]` (nested)
- Default pages use `AppLayout`
- `Welcome` page has no layout (`null`)
- Layout props via `defineOptions({ layout: { breadcrumbs: [...] } })`
- shadcn-vue (new-york-v4 style) with Reka UI primitives
- Config: `components.json`
- Components in `resources/js/components/ui/` (auto-generated, ESLint-ignored)
- Use `class-variance-authority` (cva) for variant styling
- Use `cn()` from `@/lib/utils` for conditional class merging
- Icon library: `lucide-vue-next`
- Backend: `Inertia::flash('toast', ['type' => 'success', 'message' => __('...')])` 
- Frontend: `vue-sonner` initialized via `initializeFlashToast()` in `app.ts`
- Listens to Inertia `flash` events and displays toasts automatically
## Form Validation Patterns
- Use for all controller methods that accept user input
- Files: `app/Http/Requests/Settings/ProfileUpdateRequest.php`, `PasswordUpdateRequest.php`, `ProfileDeleteRequest.php`
- Compose rules from Concern traits
- Pattern:
- Used in Fortify Action classes that receive raw `$input` arrays
- Pattern: `Validator::make($input, [...rules...])->validate()`
- Files: `app/Actions/Fortify/CreateNewUser.php`, `ResetUserPassword.php`
## Database Patterns
- Use anonymous classes: `return new class extends Migration`
- Include both `up()` and `down()` methods
- Early migrations use `0001_01_01_HHMMSS_` prefix convention
- Later migrations use standard `YYYY_MM_DD_HHMMSS_` prefix
- Located in `database/factories/`
- Use `fake()` helper for generating data
- Cache expensive operations (e.g., password hashing): `static::$password ??= Hash::make('password')`
- Define meaningful states: `unverified()`, `withTwoFactor()`
- Use `@extends Factory<Model>` PHPDoc for type safety
- Located in `database/seeders/`
- `DatabaseSeeder` creates a single test user with known credentials
- Use PHP 8 attributes: `#[Fillable([...])]`, `#[Hidden([...])]`
- Use `HasFactory` trait with `@use HasFactory<FactoryClass>` PHPDoc
- Define `casts()` method for attribute casting
- Compose behavior with traits: `Notifiable`, `TwoFactorAuthenticatable`
## PHP Version Features
- Use PHP 8 constructor property promotion where applicable
- Use PHP 8 attributes for model metadata (`#[Fillable]`, `#[Hidden]`)
- Use spread operator for array merging: `...$this->profileRules()`
- Use `fn()` arrow functions for short closures
- Use named arguments: `route('dashboard', absolute: false)`
- Use null coalescing assignment: `static::$password ??= Hash::make('password')`
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## Pattern Overview
- Server-side routing with Inertia rendering (no separate API layer)
- Fortify handles all authentication routes and controllers (login, register, password reset, 2FA)
- Application controllers handle only settings/profile management
- Wayfinder auto-generates typed TypeScript functions for all Laravel routes
- Vue 3 Composition API with `<script setup lang="ts">` for all frontend components
- Layouts determined dynamically in `app.ts` based on page component name prefix
## Layers
- Purpose: Define URL endpoints and map them to controllers/Inertia pages
- Location: `routes/web.php`, `routes/settings.php`, `routes/console.php`
- Contains: Route definitions using `Route::inertia()` for simple pages, resource-style controller routes for settings
- Depends on: Controllers, Middleware, Fortify
- Used by: HTTP requests, Wayfinder-generated TypeScript route functions
- Purpose: Process requests before they reach controllers
- Location: `app/Http/Middleware/`
- Contains: `HandleInertiaRequests` (shares props to all pages), `HandleAppearance` (dark/light mode via cookie)
- Depends on: Inertia, Request, View facades
- Used by: All web routes (configured in `bootstrap/app.php`)
- Purpose: Handle HTTP requests, validate input, return Inertia responses
- Location: `app/Http/Controllers/Settings/`
- Contains: `ProfileController` (CRUD for user profile), `SecurityController` (password updates, 2FA management)
- Depends on: Form Requests, Models, Inertia, Fortify Features
- Used by: Routes
- Purpose: Validate and authorize incoming HTTP requests
- Location: `app/Http/Requests/Settings/`
- Contains: `ProfileUpdateRequest`, `ProfileDeleteRequest`, `PasswordUpdateRequest`, `TwoFactorAuthenticationRequest`
- Depends on: Concerns (validation rule traits)
- Used by: Controllers (type-hinted in controller method signatures)
- Purpose: Encapsulate business logic for Fortify authentication flows
- Location: `app/Actions/Fortify/`
- Contains: `CreateNewUser` (user registration), `ResetUserPassword` (password reset)
- Depends on: Models, Concerns (validation traits), Fortify contracts
- Used by: Fortify service provider (registered as implementations of Fortify contracts)
- Purpose: Shared validation rule traits reused across Form Requests and Actions
- Location: `app/Concerns/`
- Contains: `PasswordValidationRules` (password/current_password rules), `ProfileValidationRules` (name/email rules)
- Depends on: Laravel validation, User model (for unique rules)
- Used by: Form Requests, Fortify Actions
- Purpose: Eloquent models representing database entities
- Location: `app/Models/`
- Contains: `User` (the only model, uses attribute annotations `#[Fillable]`, `#[Hidden]`)
- Depends on: Eloquent, Fortify `TwoFactorAuthenticatable` trait
- Used by: Controllers, Actions, Form Requests
- Purpose: Bootstrap application services and configure framework behavior
- Location: `app/Providers/`
- Contains: `AppServiceProvider` (date immutability, destructive DB protection, password defaults), `FortifyServiceProvider` (auth actions, Inertia views for auth pages, rate limiting)
- Depends on: Actions, Inertia, Fortify, RateLimiter
- Used by: Laravel service container (auto-discovered)
- Purpose: Vue 3 page components rendered by Inertia
- Location: `resources/js/pages/`
- Contains: Auth pages (`auth/`), settings pages (`settings/`), `Dashboard.vue`, `Welcome.vue`
- Depends on: Layouts, Components, Wayfinder route functions, Inertia `Form`/`Head`/`Link`
- Used by: Inertia rendering via `Inertia::render()` or `Route::inertia()`
- Purpose: Wrap page components with consistent structure (navigation, sidebar, etc.)
- Location: `resources/js/layouts/`
- Contains: `AppLayout.vue` (delegates to `AppSidebarLayout`), `AuthLayout.vue` (delegates to `AuthSimpleLayout`), `settings/Layout.vue` (settings sidebar nav)
- Depends on: Components, Composables, Wayfinder route functions
- Used by: Page components (assigned dynamically in `app.ts`)
- Purpose: Reusable UI elements
- Location: `resources/js/components/` (app-specific), `resources/js/components/ui/` (shadcn-vue primitives)
- Contains: App components (sidebar, nav, forms), UI primitives (button, input, card, dialog, etc.)
- Depends on: reka-ui (headless primitives), lucide-vue-next (icons), class-variance-authority
- Used by: Pages, Layouts
## Data Flow
- Server-side state via Inertia shared props (auth user, app name, sidebar state)
- Client-side state via Vue `ref`/`computed` within composables
- Theme/appearance persisted in both `localStorage` (client reads) and cookie (server reads for SSR)
- Sidebar state persisted via cookie (`sidebar_state`), excluded from encryption in `bootstrap/app.php`
- Flash messages via `Inertia::flash()` caught by global `router.on('flash')` listener
## Key Abstractions
- Purpose: Type-safe route references from frontend to backend, replacing hardcoded URL strings
- Examples: `resources/js/routes/profile/index.ts`, `resources/js/actions/App/Http/Controllers/Settings/ProfileController.ts`
- Pattern: Auto-generated by `@laravel/vite-plugin-wayfinder`. Two import paths: `@/routes/` (by named route) and `@/actions/` (by controller)
- Usage: `import { edit } from '@/routes/profile'` then `edit()` returns `RouteDefinition`, `edit.url()` returns string, `edit.form()` returns `RouteFormDefinition` for `<Form v-bind>`
- Purpose: Declarative form submission with automatic CSRF, method spoofing, validation error binding
- Pattern: `<Form v-bind="ProfileController.update.form()" v-slot="{ errors, processing }">` -- the form binds Wayfinder route definition and exposes reactive errors/processing state
- Purpose: DRY validation rules shared between Form Requests and Fortify Actions
- Examples: `app/Concerns/PasswordValidationRules.php`, `app/Concerns/ProfileValidationRules.php`
- Pattern: Traits with methods returning rule arrays; used via `use PasswordValidationRules` in classes
- Purpose: Automatically assign layouts based on page component name
- Location: `resources/js/app.ts`
- Pattern: Switch on page name prefix: `auth/` -> `AuthLayout`, `settings/` -> `[AppLayout, SettingsLayout]` (nested), `Welcome` -> `null` (no layout), default -> `AppLayout`
## Entry Points
- Location: `public/index.php` (standard Laravel)
- Triggers: All HTTP requests
- Responsibilities: Bootstrap Laravel, dispatch to router
- Location: `bootstrap/app.php`
- Triggers: Every request
- Responsibilities: Configure routing (web routes, console routes, health check at `/up`), register middleware stack, configure exception handling
- Location: `resources/js/app.ts`
- Triggers: First page load (bundled by Vite)
- Responsibilities: Create Inertia app, configure dynamic layout resolution, initialize theme, initialize flash toast listener
- Location: `resources/views/app.blade.php`
- Triggers: First page visit (full HTML response)
- Responsibilities: HTML shell with dark mode detection inline script, Vite asset loading, `<x-inertia::app />` mount point
- Location: `resources/css/app.css`
- Triggers: Bundled by Vite
- Responsibilities: Tailwind CSS v4 import, tw-animate-css, shadcn-vue CSS custom properties (light/dark themes), custom font
## Error Handling
- Form validation errors returned automatically via Inertia (422 responses render back with `errors` prop)
- Controllers use Form Request classes for all validation (no manual validation in controllers)
- Production password rules enforced via `Password::defaults()` in `AppServiceProvider`
- Destructive database commands prohibited in production via `DB::prohibitDestructiveCommands()`
- Frontend composables use try/catch with error state refs (see `useTwoFactorAuth.ts`)
- Flash toasts for success feedback via `Inertia::flash('toast', ['type' => 'success', 'message' => ...])`
## Cross-Cutting Concerns
- Form Request classes in `app/Http/Requests/Settings/` for all controller inputs
- Shared validation rules via traits in `app/Concerns/`
- `Password::defaults()` in `AppServiceProvider` sets production password strength requirements
- Fortify Actions use `Validator::make()` directly (not Form Requests)
- Fortify provides all auth routes and controllers (login, register, password reset, email verification, 2FA)
- `FortifyServiceProvider` maps Fortify views to Inertia pages and registers custom user creation/password reset actions
- Middleware groups: `auth` (requires login), `verified` (requires email verification)
- Rate limiting: 5 requests/minute for login and 2FA challenge
- 2FA enabled with confirmation and password confirmation required
- Appearance (light/dark/system) stored in both `localStorage` and `appearance` cookie
- `HandleAppearance` middleware shares cookie value with Blade template
- `useAppearance` composable manages client-side theme switching
- `app.blade.php` contains inline script for flash-of-unstyled-content prevention
<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->
## Project Skills

| Skill | Description | Path |
|-------|-------------|------|
| fortify-development | 'ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.' | `.claude/skills/fortify-development/SKILL.md` |
| laravel-best-practices | "Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns." | `.claude/skills/laravel-best-practices/SKILL.md` |
| pest-testing | "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code." | `.claude/skills/pest-testing/SKILL.md` |
| wayfinder-development | "Use this skill for Laravel Wayfinder which auto-generates typed functions for Laravel controllers and routes. ALWAYS use this skill when frontend code needs to call backend routes or controller actions. Trigger when: connecting any React/Vue/Svelte/Inertia frontend to Laravel controllers, routes, building end-to-end features with both frontend and backend, wiring up forms or links to backend endpoints, fixing route-related TypeScript errors, importing from @/actions or @/routes, or running wayfinder:generate. Use Wayfinder route functions instead of hardcoded URLs. Covers: wayfinder() vite plugin, .url()/.get()/.post()/.form(), query params, route model binding, tree-shaking. Do not use for backend-only task" | `.claude/skills/wayfinder-development/SKILL.md` |
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->

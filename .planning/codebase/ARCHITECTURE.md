# Architecture

**Analysis Date:** 2026-04-10

## Pattern Overview

**Overall:** Laravel MVC + Inertia.js SPA (monolith with Vue 3 frontend)

**Key Characteristics:**
- Server-side routing with Inertia rendering (no separate API layer)
- Fortify handles all authentication routes and controllers (login, register, password reset, 2FA)
- Application controllers handle only settings/profile management
- Wayfinder auto-generates typed TypeScript functions for all Laravel routes
- Vue 3 Composition API with `<script setup lang="ts">` for all frontend components
- Layouts determined dynamically in `app.ts` based on page component name prefix

## Layers

**Routing Layer:**
- Purpose: Define URL endpoints and map them to controllers/Inertia pages
- Location: `routes/web.php`, `routes/settings.php`, `routes/console.php`
- Contains: Route definitions using `Route::inertia()` for simple pages, resource-style controller routes for settings
- Depends on: Controllers, Middleware, Fortify
- Used by: HTTP requests, Wayfinder-generated TypeScript route functions

**Middleware Layer:**
- Purpose: Process requests before they reach controllers
- Location: `app/Http/Middleware/`
- Contains: `HandleInertiaRequests` (shares props to all pages), `HandleAppearance` (dark/light mode via cookie)
- Depends on: Inertia, Request, View facades
- Used by: All web routes (configured in `bootstrap/app.php`)

**Controller Layer:**
- Purpose: Handle HTTP requests, validate input, return Inertia responses
- Location: `app/Http/Controllers/Settings/`
- Contains: `ProfileController` (CRUD for user profile), `SecurityController` (password updates, 2FA management)
- Depends on: Form Requests, Models, Inertia, Fortify Features
- Used by: Routes

**Form Request Layer:**
- Purpose: Validate and authorize incoming HTTP requests
- Location: `app/Http/Requests/Settings/`
- Contains: `ProfileUpdateRequest`, `ProfileDeleteRequest`, `PasswordUpdateRequest`, `TwoFactorAuthenticationRequest`
- Depends on: Concerns (validation rule traits)
- Used by: Controllers (type-hinted in controller method signatures)

**Action Layer:**
- Purpose: Encapsulate business logic for Fortify authentication flows
- Location: `app/Actions/Fortify/`
- Contains: `CreateNewUser` (user registration), `ResetUserPassword` (password reset)
- Depends on: Models, Concerns (validation traits), Fortify contracts
- Used by: Fortify service provider (registered as implementations of Fortify contracts)

**Concern Layer:**
- Purpose: Shared validation rule traits reused across Form Requests and Actions
- Location: `app/Concerns/`
- Contains: `PasswordValidationRules` (password/current_password rules), `ProfileValidationRules` (name/email rules)
- Depends on: Laravel validation, User model (for unique rules)
- Used by: Form Requests, Fortify Actions

**Model Layer:**
- Purpose: Eloquent models representing database entities
- Location: `app/Models/`
- Contains: `User` (the only model, uses attribute annotations `#[Fillable]`, `#[Hidden]`)
- Depends on: Eloquent, Fortify `TwoFactorAuthenticatable` trait
- Used by: Controllers, Actions, Form Requests

**Service Provider Layer:**
- Purpose: Bootstrap application services and configure framework behavior
- Location: `app/Providers/`
- Contains: `AppServiceProvider` (date immutability, destructive DB protection, password defaults), `FortifyServiceProvider` (auth actions, Inertia views for auth pages, rate limiting)
- Depends on: Actions, Inertia, Fortify, RateLimiter
- Used by: Laravel service container (auto-discovered)

**Frontend Page Layer:**
- Purpose: Vue 3 page components rendered by Inertia
- Location: `resources/js/pages/`
- Contains: Auth pages (`auth/`), settings pages (`settings/`), `Dashboard.vue`, `Welcome.vue`
- Depends on: Layouts, Components, Wayfinder route functions, Inertia `Form`/`Head`/`Link`
- Used by: Inertia rendering via `Inertia::render()` or `Route::inertia()`

**Frontend Layout Layer:**
- Purpose: Wrap page components with consistent structure (navigation, sidebar, etc.)
- Location: `resources/js/layouts/`
- Contains: `AppLayout.vue` (delegates to `AppSidebarLayout`), `AuthLayout.vue` (delegates to `AuthSimpleLayout`), `settings/Layout.vue` (settings sidebar nav)
- Depends on: Components, Composables, Wayfinder route functions
- Used by: Page components (assigned dynamically in `app.ts`)

**Frontend Component Layer:**
- Purpose: Reusable UI elements
- Location: `resources/js/components/` (app-specific), `resources/js/components/ui/` (shadcn-vue primitives)
- Contains: App components (sidebar, nav, forms), UI primitives (button, input, card, dialog, etc.)
- Depends on: reka-ui (headless primitives), lucide-vue-next (icons), class-variance-authority
- Used by: Pages, Layouts

## Data Flow

**Page Request (Inertia):**

1. Browser navigates to URL (e.g., `/settings/profile`)
2. Laravel routing matches the URL to a controller action in `routes/settings.php`
3. Middleware stack runs: `HandleAppearance` -> `HandleInertiaRequests` -> `AddLinkHeadersForPreloadedAssets`
4. Controller method executes, returns `Inertia::render('settings/Profile', [...props])`
5. `HandleInertiaRequests::share()` merges shared data: `auth.user`, `name`, `sidebarOpen`
6. Inertia responds with either full HTML (first visit) or JSON (subsequent SPA navigation)
7. Vue component at `resources/js/pages/settings/Profile.vue` renders with received props

**Form Submission (Inertia v3 Form component):**

1. Vue `<Form>` component uses Wayfinder-generated form definition (e.g., `ProfileController.update.form()`)
2. Inertia sends XHR request to the Laravel route with form data
3. Laravel Form Request validates input (e.g., `ProfileUpdateRequest`)
4. Controller processes validated data, updates model
5. Controller flashes toast via `Inertia::flash('toast', [...])` and redirects
6. Frontend `initializeFlashToast()` listener catches the flash event, shows `vue-sonner` toast
7. Inertia preserves SPA state, renders updated page with new props

**Authentication Flow (Fortify):**

1. `FortifyServiceProvider::configureViews()` maps auth routes to Inertia page components
2. Fortify handles login/register/password-reset/2FA controller logic internally
3. `FortifyServiceProvider::configureActions()` registers custom `CreateNewUser` and `ResetUserPassword` actions
4. Rate limiting configured per `FortifyServiceProvider::configureRateLimiting()` (5/min for login and 2FA)
5. After auth, Fortify redirects to `/dashboard` (configured in `config/fortify.php` as `home`)

**State Management:**
- Server-side state via Inertia shared props (auth user, app name, sidebar state)
- Client-side state via Vue `ref`/`computed` within composables
- Theme/appearance persisted in both `localStorage` (client reads) and cookie (server reads for SSR)
- Sidebar state persisted via cookie (`sidebar_state`), excluded from encryption in `bootstrap/app.php`
- Flash messages via `Inertia::flash()` caught by global `router.on('flash')` listener

## Key Abstractions

**Wayfinder Route Functions:**
- Purpose: Type-safe route references from frontend to backend, replacing hardcoded URL strings
- Examples: `resources/js/routes/profile/index.ts`, `resources/js/actions/App/Http/Controllers/Settings/ProfileController.ts`
- Pattern: Auto-generated by `@laravel/vite-plugin-wayfinder`. Two import paths: `@/routes/` (by named route) and `@/actions/` (by controller)
- Usage: `import { edit } from '@/routes/profile'` then `edit()` returns `RouteDefinition`, `edit.url()` returns string, `edit.form()` returns `RouteFormDefinition` for `<Form v-bind>`

**Inertia Form Component (v3):**
- Purpose: Declarative form submission with automatic CSRF, method spoofing, validation error binding
- Pattern: `<Form v-bind="ProfileController.update.form()" v-slot="{ errors, processing }">` -- the form binds Wayfinder route definition and exposes reactive errors/processing state

**Validation Rule Traits (Concerns):**
- Purpose: DRY validation rules shared between Form Requests and Fortify Actions
- Examples: `app/Concerns/PasswordValidationRules.php`, `app/Concerns/ProfileValidationRules.php`
- Pattern: Traits with methods returning rule arrays; used via `use PasswordValidationRules` in classes

**Dynamic Layout Resolution:**
- Purpose: Automatically assign layouts based on page component name
- Location: `resources/js/app.ts`
- Pattern: Switch on page name prefix: `auth/` -> `AuthLayout`, `settings/` -> `[AppLayout, SettingsLayout]` (nested), `Welcome` -> `null` (no layout), default -> `AppLayout`

## Entry Points

**HTTP Entry:**
- Location: `public/index.php` (standard Laravel)
- Triggers: All HTTP requests
- Responsibilities: Bootstrap Laravel, dispatch to router

**Application Bootstrap:**
- Location: `bootstrap/app.php`
- Triggers: Every request
- Responsibilities: Configure routing (web routes, console routes, health check at `/up`), register middleware stack, configure exception handling

**Frontend Entry:**
- Location: `resources/js/app.ts`
- Triggers: First page load (bundled by Vite)
- Responsibilities: Create Inertia app, configure dynamic layout resolution, initialize theme, initialize flash toast listener

**Blade Root Template:**
- Location: `resources/views/app.blade.php`
- Triggers: First page visit (full HTML response)
- Responsibilities: HTML shell with dark mode detection inline script, Vite asset loading, `<x-inertia::app />` mount point

**CSS Entry:**
- Location: `resources/css/app.css`
- Triggers: Bundled by Vite
- Responsibilities: Tailwind CSS v4 import, tw-animate-css, shadcn-vue CSS custom properties (light/dark themes), custom font

## Error Handling

**Strategy:** Laravel default exception handling with Fortify-managed auth errors

**Patterns:**
- Form validation errors returned automatically via Inertia (422 responses render back with `errors` prop)
- Controllers use Form Request classes for all validation (no manual validation in controllers)
- Production password rules enforced via `Password::defaults()` in `AppServiceProvider`
- Destructive database commands prohibited in production via `DB::prohibitDestructiveCommands()`
- Frontend composables use try/catch with error state refs (see `useTwoFactorAuth.ts`)
- Flash toasts for success feedback via `Inertia::flash('toast', ['type' => 'success', 'message' => ...])`

## Cross-Cutting Concerns

**Logging:** Not customized beyond Laravel defaults (`config/logging.php`). No application-level logging in controllers.

**Validation:**
- Form Request classes in `app/Http/Requests/Settings/` for all controller inputs
- Shared validation rules via traits in `app/Concerns/`
- `Password::defaults()` in `AppServiceProvider` sets production password strength requirements
- Fortify Actions use `Validator::make()` directly (not Form Requests)

**Authentication:**
- Fortify provides all auth routes and controllers (login, register, password reset, email verification, 2FA)
- `FortifyServiceProvider` maps Fortify views to Inertia pages and registers custom user creation/password reset actions
- Middleware groups: `auth` (requires login), `verified` (requires email verification)
- Rate limiting: 5 requests/minute for login and 2FA challenge
- 2FA enabled with confirmation and password confirmation required

**Authorization:** No policy or gate definitions. Authorization is purely middleware-based (`auth`, `verified`).

**Theming:**
- Appearance (light/dark/system) stored in both `localStorage` and `appearance` cookie
- `HandleAppearance` middleware shares cookie value with Blade template
- `useAppearance` composable manages client-side theme switching
- `app.blade.php` contains inline script for flash-of-unstyled-content prevention

---

*Architecture analysis: 2026-04-10*

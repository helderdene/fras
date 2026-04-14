# Coding Conventions

**Analysis Date:** 2026-04-10

## Naming Patterns

**PHP Files:**
- Controllers: `PascalCase` + `Controller` suffix (e.g., `ProfileController`, `SecurityController`)
- Models: `PascalCase` singular (e.g., `User`)
- Form Requests: `PascalCase` + `Request` suffix (e.g., `ProfileUpdateRequest`, `PasswordUpdateRequest`)
- Actions: `PascalCase` verb-noun (e.g., `CreateNewUser`, `ResetUserPassword`)
- Concerns (Traits): `PascalCase` descriptive (e.g., `PasswordValidationRules`, `ProfileValidationRules`)
- Migrations: `YYYY_MM_DD_HHMMSS_snake_case_description` (e.g., `2025_08_14_170933_add_two_factor_columns_to_users_table.php`)
- Factories: `ModelNameFactory` (e.g., `UserFactory`)
- Seeders: `ModelNameSeeder` or `DatabaseSeeder` (e.g., `DatabaseSeeder`)
- Service Providers: `PascalCase` + `ServiceProvider` suffix (e.g., `AppServiceProvider`, `FortifyServiceProvider`)

**Vue Files:**
- Pages: `PascalCase.vue` in lowercase subdirectories (e.g., `pages/auth/Login.vue`, `pages/settings/Profile.vue`)
- Components: `PascalCase.vue` (e.g., `Heading.vue`, `InputError.vue`, `DeleteUser.vue`)
- UI Components: `PascalCase.vue` in kebab-case directories (e.g., `components/ui/button/Button.vue`, `components/ui/input-otp/InputOTP.vue`)
- Layouts: `PascalCase.vue` (e.g., `AppLayout.vue`, `AuthLayout.vue`, `Layout.vue`)
- Composables: `camelCase` with `use` prefix (e.g., `useAppearance.ts`, `useCurrentUrl.ts`, `useTwoFactorAuth.ts`)
- Types: `camelCase.ts` or `PascalCase.d.ts` (e.g., `auth.ts`, `navigation.ts`, `global.d.ts`)

**TypeScript Functions/Variables:**
- Functions: `camelCase` (e.g., `getInitials`, `updateTheme`, `initializeFlashToast`)
- Types/Interfaces: `PascalCase` (e.g., `User`, `Auth`, `BreadcrumbItem`, `NavItem`, `FlashToast`)
- Constants: `camelCase` (e.g., `appName`, `sidebarNavItems`)
- Composable return types: `UsePascalCaseReturn` (e.g., `UseAppearanceReturn`, `UseInitialsReturn`)

**PHP Functions/Variables:**
- Methods: `camelCase` (e.g., `passwordRules`, `profileRules`, `configureDefaults`)
- Variables: `$camelCase` (e.g., `$throttleKey`, `$verificationUrl`)

**Routes:**
- Named routes: `kebab-or-dot.notation` (e.g., `profile.edit`, `profile.update`, `security.edit`, `two-factor.login`, `user-password.update`)
- URL paths: `/kebab-case` (e.g., `/settings/profile`, `/settings/security`, `/settings/appearance`)

## Code Style

**PHP Formatting:**
- Tool: Laravel Pint with `laravel` preset
- Config: `pint.json`
- Run: `vendor/bin/pint --dirty --format agent` (for modified files)
- Run: `vendor/bin/pint --parallel` (full codebase)
- Always run Pint before finalizing PHP changes

**Frontend Formatting:**
- Tool: Prettier v3
- Config: `.prettierrc`
- Key settings:
  - Semicolons: enabled
  - Single quotes: enabled
  - Print width: 80
  - Tab width: 4 (2 for YAML)
  - Single attribute per line: disabled
  - Tailwind CSS plugin: `prettier-plugin-tailwindcss` with `clsx`, `cn`, `cva` functions
- Run: `npm run format` (write) or `npm run format:check` (verify)

**Frontend Linting:**
- Tool: ESLint v9 with flat config
- Config: `eslint.config.js`
- Key rules enforced:
  - `@typescript-eslint/consistent-type-imports`: Use `type` imports via separate import statements
  - `import/order`: Alphabetized imports grouped by builtin > external > internal > parent > sibling > index
  - `curly`: Always require braces (no single-line bodies)
  - `@stylistic/brace-style`: 1TBS style, no single-line blocks
  - `@stylistic/padding-line-between-statements`: Blank lines around control statements (if, return, for, while, etc.)
  - `vue/multi-word-component-names`: disabled
  - `@typescript-eslint/no-explicit-any`: disabled
- Run: `npm run lint` (fix) or `npm run lint:check` (verify)

**TypeScript:**
- Config: `tsconfig.json`
- Strict mode: enabled
- Target: ESNext
- Module resolution: bundler
- Path alias: `@/*` maps to `./resources/js/*`
- JSX: preserve (Vue JSX)

## Import Organization

**PHP Imports:**
- Order observed in controllers:
  1. Own namespace classes (e.g., `App\Http\Controllers\Controller`)
  2. App classes (Form Requests, Models)
  3. Illuminate/Laravel framework classes
  4. Third-party packages (Inertia, Fortify)
- Use full class imports, no glob imports

**TypeScript/Vue Imports (enforced by ESLint):**
1. Builtin modules
2. External packages (`@inertiajs/vue3`, `vue`, `lucide-vue-next`, etc.)
3. Internal aliases (`@/actions/...`, `@/components/...`, `@/composables/...`, `@/lib/...`, `@/routes/...`, `@/types/...`)
4. Parent/sibling/index

**Type imports must use separate `import type` statements:**
```typescript
// CORRECT
import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

// WRONG
import { type ComputedRef, type Ref, computed } from 'vue';
```

## Error Handling

**PHP Backend:**
- Form validation: Use Form Request classes in `app/Http/Requests/` for controller method validation
- Fortify Actions: Use `Validator::make($input, [...rules...])->validate()` for action-based validation
- Validation rules: Extract reusable rules into traits in `app/Concerns/` (e.g., `PasswordValidationRules`, `ProfileValidationRules`)
- Password validation: `Password::default()` with production-specific requirements set in `AppServiceProvider`

**Frontend Error Display:**
- Use `InputError` component (`@/components/InputError.vue`) to display per-field errors
- Access errors from Inertia Form's `v-slot="{ errors }"` destructure
- Pattern: `<InputError :message="errors.field_name" />`

**Frontend Async Errors:**
- Use try/catch in composables with error state stored in `ref<string[]>`
- Pattern in `useTwoFactorAuth.ts`: catch errors, push to `errors` ref, set data to null

## Logging

**Framework:** Not explicitly configured beyond Laravel defaults
**Frontend:** No explicit logging pattern; uses `vue-sonner` toast notifications for user feedback

## Comments

**PHP:**
- Use PHPDoc blocks on class methods with single-line description
- Pattern: `/** Show the user's profile settings page. */`
- Use `@return` array shape annotations for type hints: `@return array<string, mixed>`
- Use `@param` array shape annotations: `@param array<string, string> $input`

**Vue/TypeScript:**
- Minimal inline comments
- Comments used sparingly for context: `// This will set light / dark mode on page load...`
- End-of-line comments with `...` trailing style

## Function Design

**PHP Controllers:**
- Single-responsibility methods: `edit`, `update`, `destroy`
- Use typed parameters and return types on all methods
- Use Form Request type-hints for request parameters
- Return `Inertia::render()` for page responses, `RedirectResponse` for mutations
- Use `to_route()` or `back()` for redirects after mutations
- Use `Inertia::flash()` for success toast messages: `Inertia::flash('toast', ['type' => 'success', 'message' => __('...')])` 

**PHP Actions:**
- Implement Fortify contract interfaces (e.g., `CreatesNewUsers`, `ResetsUserPasswords`)
- Use traits for shared validation rules
- Pattern: validate then perform action

**PHP Service Providers:**
- Split `boot()` into private `configure*()` methods for organization
- Pattern in `FortifyServiceProvider`: `configureActions()`, `configureViews()`, `configureRateLimiting()`

**Vue Components:**
- Use `<script setup lang="ts">` exclusively
- Define props with `defineProps<{...}>()` using TypeScript type syntax
- For defaults, use `withDefaults(defineProps<Props>(), { ... })`
- Use `defineOptions()` for layout metadata (breadcrumbs, title, description)
- Use `type Props = { ... }` alias when props type is reused or complex

**Vue Composables:**
- Export both the composable function and its return type
- Name: `useFeatureName()` returning `UseFeatureNameReturn` type
- Export standalone utility functions alongside composable when useful (e.g., `getInitials` and `useInitials`)

## Module Design

**PHP Exports/Organization:**
- Controllers extend abstract `App\Http\Controllers\Controller`
- Middleware implements `HasMiddleware` interface for controller-level middleware
- Traits in `app/Concerns/` for shared validation and behavior

**Vue/TypeScript Barrel Files:**
- UI components use barrel `index.ts` files exporting components and variant types
- Pattern in `components/ui/button/index.ts`:
  ```typescript
  export { default as Button } from "./Button.vue"
  export const buttonVariants = cva(...)
  export type ButtonVariants = VariantProps<typeof buttonVariants>
  ```
- Types use barrel `types/index.ts` re-exporting from submodules

## Design Patterns

**Actions Pattern (`app/Actions/`):**
- Used for Fortify authentication actions: `CreateNewUser`, `ResetUserPassword`
- Implement Fortify contract interfaces
- Compose validation from traits

**Concerns Pattern (`app/Concerns/`):**
- PHP traits for reusable validation rule sets
- Used by both Form Requests and Actions
- `PasswordValidationRules`: `passwordRules()`, `currentPasswordRules()`
- `ProfileValidationRules`: `profileRules()`, `nameRules()`, `emailRules()`

**Form Request Pattern (`app/Http/Requests/`):**
- Organized in subdirectories matching controller groups (e.g., `Settings/`)
- Compose validation rules from Concerns traits
- All extend `Illuminate\Foundation\Http\FormRequest`

**Wayfinder Pattern (Frontend Routing):**
- Auto-generated typed route functions in `resources/js/actions/` (controller-based) and `resources/js/routes/` (route-name-based)
- Import controller actions: `import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController'`
- Import named routes: `import { edit } from '@/routes/profile'`
- Use `.form()` for Inertia Form bindings: `v-bind="ProfileController.update.form()"`
- Use function calls for URLs: `edit()`, `register()`, `dashboard()`
- NEVER hardcode URLs; always use Wayfinder-generated functions

**Layout System:**
- Automatic layout selection in `app.ts` based on page component name path
- `auth/*` pages use `AuthLayout`
- `settings/*` pages use `[AppLayout, SettingsLayout]` (nested)
- Default pages use `AppLayout`
- `Welcome` page has no layout (`null`)
- Layout props via `defineOptions({ layout: { breadcrumbs: [...] } })`

**UI Component Library:**
- shadcn-vue (new-york-v4 style) with Reka UI primitives
- Config: `components.json`
- Components in `resources/js/components/ui/` (auto-generated, ESLint-ignored)
- Use `class-variance-authority` (cva) for variant styling
- Use `cn()` from `@/lib/utils` for conditional class merging
- Icon library: `lucide-vue-next`

**Toast Notifications:**
- Backend: `Inertia::flash('toast', ['type' => 'success', 'message' => __('...')])` 
- Frontend: `vue-sonner` initialized via `initializeFlashToast()` in `app.ts`
- Listens to Inertia `flash` events and displays toasts automatically

## Form Validation Patterns

**Controller-level (Form Requests):**
- Use for all controller methods that accept user input
- Files: `app/Http/Requests/Settings/ProfileUpdateRequest.php`, `PasswordUpdateRequest.php`, `ProfileDeleteRequest.php`
- Compose rules from Concern traits
- Pattern:
  ```php
  class ProfileUpdateRequest extends FormRequest
  {
      use ProfileValidationRules;

      public function rules(): array
      {
          return $this->profileRules($this->user()->id);
      }
  }
  ```

**Action-level (Validator facade):**
- Used in Fortify Action classes that receive raw `$input` arrays
- Pattern: `Validator::make($input, [...rules...])->validate()`
- Files: `app/Actions/Fortify/CreateNewUser.php`, `ResetUserPassword.php`

**No inline validation in controllers.** Always delegate to Form Requests or Action classes.

## Database Patterns

**Migrations:**
- Use anonymous classes: `return new class extends Migration`
- Include both `up()` and `down()` methods
- Early migrations use `0001_01_01_HHMMSS_` prefix convention
- Later migrations use standard `YYYY_MM_DD_HHMMSS_` prefix

**Factories:**
- Located in `database/factories/`
- Use `fake()` helper for generating data
- Cache expensive operations (e.g., password hashing): `static::$password ??= Hash::make('password')`
- Define meaningful states: `unverified()`, `withTwoFactor()`
- Use `@extends Factory<Model>` PHPDoc for type safety

**Seeders:**
- Located in `database/seeders/`
- `DatabaseSeeder` creates a single test user with known credentials

**Models:**
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

---

*Convention analysis: 2026-04-10*

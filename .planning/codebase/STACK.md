# Technology Stack

**Analysis Date:** 2026-04-10

## Languages

**Primary:**
- PHP ^8.3 (runtime: 8.4.19) - Backend application code, controllers, models, migrations
- TypeScript ^5.2.2 - Frontend application code, Vue components, composables, types

**Secondary:**
- CSS (Tailwind CSS v4) - Styling via `resources/css/app.css`

## Runtime

**Environment:**
- PHP 8.4.19 (CLI, NTS, clang 15.0.0) - Served by Laravel Herd
- Node.js v22.14.0 - Frontend build tooling and SSR

**Package Manager:**
- Composer v2 - PHP dependencies
  - Lockfile: `composer.lock` (present)
- npm - JavaScript dependencies
  - Lockfile: `package-lock.json` (present)

## Frameworks

**Core:**
- Laravel v13.0 (`laravel/framework`) - PHP web framework
- Vue.js v3.5.13 (`vue`) - Frontend reactive UI framework
- Inertia.js v3.0 (`@inertiajs/vue3` + `inertiajs/inertia-laravel`) - SPA bridge between Laravel and Vue
- Tailwind CSS v4.1.1 (`tailwindcss`) - Utility-first CSS framework

**Authentication:**
- Laravel Fortify v1.34 (`laravel/fortify`) - Backend authentication scaffolding (login, registration, password reset, email verification, 2FA)

**Testing:**
- Pest v4.4 (`pestphp/pest`) - PHP testing framework
- Pest Laravel Plugin v4.1 (`pestphp/pest-plugin-laravel`) - Laravel-specific Pest helpers

**Build/Dev:**
- Vite v8.0.0 (`vite`) - Frontend build tool and dev server
- Laravel Vite Plugin v3.0.0 (`laravel-vite-plugin`) - Laravel/Vite integration
- Inertia Vite Plugin v3.0.0 (`@inertiajs/vite`) - SSR support for Inertia in Vite dev mode
- Laravel Pint v1.27 (`laravel/pint`) - PHP code formatter (PSR-12 / Laravel style)
- ESLint v9.17.0 (`eslint`) - JavaScript/TypeScript linting
- Prettier v3.4.2 (`prettier`) - JavaScript/TypeScript formatting
- vue-tsc v2.2.4 (`vue-tsc`) - Vue TypeScript type checking

## Key Dependencies

**Critical (Production):**
- `@inertiajs/vue3` ^3.0.0 - Inertia client-side adapter for Vue 3
- `@inertiajs/vite` ^3.0.0 - Inertia SSR via Vite (no separate Node server needed in dev)
- `laravel/wayfinder` ^0.1.14 - Auto-generates typed TypeScript functions for Laravel routes/controllers
- `@laravel/vite-plugin-wayfinder` ^0.1.3 - Vite plugin for Wayfinder code generation
- `reka-ui` ^2.6.1 - Headless UI component primitives (Vue 3)
- `lucide-vue-next` ^0.468.0 - Icon library for Vue
- `vue-sonner` ^2.0.0 - Toast notification component
- `vue-input-otp` ^0.3.2 - OTP input component (used for 2FA)

**UI Utilities:**
- `class-variance-authority` ^0.7.1 - Component variant management (CVA)
- `clsx` ^2.1.1 - Conditional className utility
- `tailwind-merge` ^3.2.0 - Merge Tailwind CSS classes intelligently
- `tw-animate-css` ^1.2.5 - Tailwind CSS animation utilities

**Composables:**
- `@vueuse/core` ^12.8.2 - Collection of Vue composition utilities

**Infrastructure (Dev):**
- `laravel/boost` ^2.0 - MCP server for AI-assisted development (database queries, doc search, schema inspection)
- `laravel/pail` ^1.2.5 - Real-time log viewer for Laravel
- `laravel/sail` ^1.53 - Docker dev environment (available but not primary; Herd is used)
- `laravel/tinker` ^3.0 - REPL for Laravel
- `fakerphp/faker` ^1.24 - Test data generation
- `mockery/mockery` ^1.6 - PHP mocking framework
- `nunomaduro/collision` ^8.9 - Better error reporting in CLI
- `concurrently` ^9.0.1 - Run multiple dev processes simultaneously

## Configuration

**TypeScript:**
- Config: `tsconfig.json`
- Target: ESNext, Module: ESNext, Module Resolution: bundler
- Strict mode enabled
- Path alias: `@/*` maps to `./resources/js/*`
- Types: `vite/client`
- JSX: preserve (Vue JSX support)

**Vite:**
- Config: `vite.config.ts`
- Entry points: `resources/css/app.css`, `resources/js/app.ts`
- Plugins: laravel, inertia (SSR), tailwindcss, vue, wayfinder (with `formVariants: true`)
- Hot reload enabled (`refresh: true`)

**ESLint:**
- Config: `eslint.config.js`
- Vue + TypeScript flat config (`@vue/eslint-config-typescript`)
- Enforces consistent type imports (`prefer: 'type-imports'`)
- Enforces alphabetized import ordering by group
- 1TBS brace style, padding around control statements
- Prettier integration to avoid conflicts
- Ignores: `vendor`, `node_modules`, `public`, `bootstrap/ssr`, generated files (`resources/js/actions/**`, `resources/js/routes/**`, `resources/js/wayfinder/**`, `resources/js/components/ui/*`)

**Prettier:**
- Config: `.prettierrc`
- Semicolons: yes, Single quotes: yes, Tab width: 4, Print width: 80
- Plugin: `prettier-plugin-tailwindcss` (auto-sorts Tailwind classes)
- Tailwind functions recognized: `clsx`, `cn`, `cva`
- Tailwind stylesheet: `resources/css/app.css`

**PHP Formatting:**
- Laravel Pint v1.27 (`vendor/bin/pint`)
- Run with `--dirty --format agent` before finalizing changes
- CI runs `composer lint` (which is `pint --parallel`)

**Inertia SSR:**
- Enabled in `config/inertia.php` (`ssr.enabled: true`)
- SSR URL: `http://127.0.0.1:13714`
- Pages discovered from `resources/js/pages` with extensions: js, jsx, svelte, ts, tsx, vue

**Environment:**
- `.env.example` present - copy to `.env` and generate key
- Key vars: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`, `DB_CONNECTION`
- Vite-exposed: `VITE_APP_NAME`

## Build & Dev Commands

**Development:**
```bash
composer run dev          # Starts server, queue, logs (pail), and Vite concurrently
npm run dev               # Vite dev server only
```

**Build:**
```bash
npm run build             # Production build
npm run build:ssr         # Production build with SSR bundle
```

**Quality:**
```bash
npm run lint              # ESLint fix
npm run lint:check        # ESLint check only
npm run format            # Prettier fix
npm run format:check      # Prettier check only
npm run types:check       # vue-tsc type checking
composer lint             # Pint (PHP) formatting
composer ci:check         # Full CI check (lint + format + types + tests)
composer test             # Config clear + Pint check + Pest tests
```

**Setup:**
```bash
composer run setup        # Full project setup (install, env, key, migrate, npm install, build)
```

## Database

**Default:** SQLite (`DB_CONNECTION=sqlite`)
- Database file: `database/database.sqlite`
- Session driver: database
- Cache store: database
- Queue connection: database

**Supported (configured):** MySQL, MariaDB, PostgreSQL, SQL Server
**Redis:** Configured but not default; available for cache, queue, and sessions

## Platform Requirements

**Development:**
- PHP >= 8.3 with extensions: pdo_sqlite (default), bcrypt
- Node.js 22+ (for Vite, SSR, frontend tooling)
- Laravel Herd (serves the application at `https://fras.test`)

**CI/CD:**
- GitHub Actions (`.github/workflows/lint.yml`, `.github/workflows/tests.yml`)
- Tests run on PHP matrix: 8.3, 8.4, 8.5
- Node.js 22 in CI
- Coverage tool: xdebug

**Production:**
- Laravel Cloud recommended (per project guidelines)
- SSR enabled by default

---

*Stack analysis: 2026-04-10*

# External Integrations

**Analysis Date:** 2026-04-10

## APIs & External Services

**Currently Active:**
- No third-party API integrations are actively used. The application is a standard Laravel starter kit with authentication and settings management.

**Pre-configured (not active by default):**
- **Postmark** - Transactional email service
  - Config: `config/services.php` line 17-19
  - Auth: `POSTMARK_API_KEY` env var
  - Mailer config: `config/mail.php` line 57-61
- **Resend** - Email API service
  - Config: `config/services.php` line 21-23
  - Auth: `RESEND_API_KEY` env var
  - Mailer config: `config/mail.php` line 63-65
- **AWS SES** - Email sending via Amazon
  - Config: `config/services.php` line 25-29
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` env vars
  - Region: `AWS_DEFAULT_REGION` (default: us-east-1)
  - Mailer config: `config/mail.php` line 52-54
- **Slack** - Notifications
  - Config: `config/services.php` line 31-37
  - Auth: `SLACK_BOT_USER_OAUTH_TOKEN` env var
  - Channel: `SLACK_BOT_USER_DEFAULT_CHANNEL` env var
  - Also available as a log channel: `LOG_SLACK_WEBHOOK_URL` in `config/logging.php`

## Data Storage

**Database:**
- SQLite (default) - `database/database.sqlite`
  - Connection config: `config/database.php` line 33-45
  - Env var: `DB_CONNECTION=sqlite`
  - Also used for: sessions (`sessions` table), cache (`cache` table), queue jobs (`jobs` table), failed jobs (`failed_jobs` table), job batches (`job_batches` table)
- MySQL/MariaDB/PostgreSQL/SQL Server configured but not active by default

**File Storage:**
- Local filesystem (default) - `FILESYSTEM_DISK=local`
  - Private disk: `storage/app/private` (`config/filesystems.php` line 32-39)
  - Public disk: `storage/app/public` (`config/filesystems.php` line 41-48)
- AWS S3 pre-configured but not active
  - Config: `config/filesystems.php` line 50-61
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` env vars

**Caching:**
- Database cache (default) - `CACHE_STORE=database`
  - Config: `config/cache.php` line 42-48
  - Table: `cache` (configurable via `DB_CACHE_TABLE`)
- Redis configured but not default
  - Config: `config/cache.php` line 75-79, `config/database.php` line 146-182
  - Client: phpredis (`REDIS_CLIENT=phpredis`)
  - Host: `REDIS_HOST` (default: 127.0.0.1), Port: `REDIS_PORT` (default: 6379)

## Authentication & Identity

**Auth Provider:**
- Laravel Fortify (custom, session-based)
  - Config: `config/fortify.php`
  - Guard: `web` (session driver)
  - User model: `App\Models\User` (`app/Models/User.php`)
  - Provider: Eloquent

**Features Enabled:**
- Registration (`Features::registration()`)
- Password Reset (`Features::resetPasswords()`)
- Email Verification (`Features::emailVerification()`)
- Two-Factor Authentication (`Features::twoFactorAuthentication()`) with confirmation and password confirmation

**Auth Actions:**
- `app/Actions/Fortify/CreateNewUser.php` - User registration logic
- `app/Actions/Fortify/ResetUserPassword.php` - Password reset logic

**Auth Service Provider:**
- `app/Providers/FortifyServiceProvider.php` - Configures Fortify actions and views

**No external OAuth/social login providers** (no Socialite, no Passport, no third-party SSO).

## Monitoring & Observability

**Error Tracking:**
- None configured (no Sentry, Bugsnag, Flare, or similar)

**Logs:**
- Monolog via Laravel's logging system (`config/logging.php`)
- Default channel: `stack` (which delegates to `single`)
- Log file: `storage/logs/laravel.log`
- Laravel Pail (`laravel/pail`) for real-time CLI log tailing during development
- Pre-configured but inactive: Slack log channel, Papertrail, Syslog

**Real-time Monitoring:**
- None configured (no Pulse, Telescope, or Horizon)

## CI/CD & Deployment

**Hosting:**
- Development: Laravel Herd (macOS, serves at `https://fras.test`)
- Production: Laravel Cloud recommended (per project conventions)

**CI Pipeline:**
- GitHub Actions

**Lint Workflow** (`.github/workflows/lint.yml`):
  - Triggers: push/PR to develop, main, master, workos branches
  - Runs: Pint (PHP), Prettier (format), ESLint (lint)
  - PHP 8.4, ubuntu-latest

**Tests Workflow** (`.github/workflows/tests.yml`):
  - Triggers: push/PR to develop, main, master, workos branches
  - PHP matrix: 8.3, 8.4, 8.5
  - Node.js 22
  - Coverage: xdebug
  - Steps: install deps, copy .env.example, generate key, npm build, run Pest

## Queue & Background Jobs

**Queue Driver:** Database (default)
- Config: `config/queue.php` line 38-45
- Table: `jobs`
- Failed jobs table: `failed_jobs` (UUID driver)
- Job batches table: `job_batches`
- Dev command runs `php artisan queue:listen --tries=1 --timeout=0` via `composer run dev`

**Pre-configured alternatives:** Beanstalkd, SQS, Redis

## Broadcasting

**Driver:** Log (default) - `BROADCAST_CONNECTION=log`
- No real-time broadcasting configured (no Pusher, Ably, or Reverb)

## Mail

**Default Mailer:** Log (`MAIL_MAILER=log`) - Emails written to log in development
- SMTP configured: `MAIL_HOST`, `MAIL_PORT` (default 2525), `MAIL_USERNAME`, `MAIL_PASSWORD`
- From address: `MAIL_FROM_ADDRESS` (default: hello@example.com)
- Pre-configured transports: SMTP, SES, Postmark, Resend, Sendmail, Log

## MCP Servers

**Laravel Boost:**
- Config: `.mcp.json`
- Command: `php artisan boost:mcp`
- Provides: database schema access, read-only database queries, semantic documentation search, error logs, URL resolution

## Environment Configuration

**Required env vars (must be set for the app to function):**
- `APP_KEY` - Encryption key (generated via `php artisan key:generate`)
- `APP_URL` - Application URL

**Important env vars (have sensible defaults):**
- `APP_NAME` - Application name (default: "Laravel")
- `APP_ENV` - Environment (default: "local")
- `APP_DEBUG` - Debug mode (default: true)
- `DB_CONNECTION` - Database driver (default: "sqlite")
- `SESSION_DRIVER` - Session storage (default: "database")
- `CACHE_STORE` - Cache driver (default: "database")
- `QUEUE_CONNECTION` - Queue driver (default: "database")
- `MAIL_MAILER` - Mail transport (default: "log")
- `BROADCAST_CONNECTION` - Broadcast driver (default: "log")
- `FILESYSTEM_DISK` - File storage (default: "local")

**Optional integration env vars (enable when needed):**
- `POSTMARK_API_KEY` - Postmark email
- `RESEND_API_KEY` - Resend email
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` - AWS S3/SES
- `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` - Slack notifications
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` - Redis (for cache/queue/sessions)

**Secrets location:**
- `.env` file (local, gitignored)
- `.env.example` committed as template

## Webhooks & Callbacks

**Incoming:**
- None configured

**Outgoing:**
- None configured

---

*Integration audit: 2026-04-10*

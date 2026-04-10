# Stack Research

**Domain:** Face Recognition Alert System (MQTT camera integration, real-time broadcasting, image processing, map visualization)
**Researched:** 2026-04-10
**Confidence:** HIGH

## Existing Stack (Do Not Re-Research)

The application already runs Laravel 13.4 + Vue 3.5 + Inertia v3 + Tailwind v4 + shadcn-vue + Fortify + Wayfinder. This research covers only the NEW technologies required for the FRAS milestone.

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| php-mqtt/laravel-client | ^1.8 | MQTT broker communication (subscribe/publish) | Only maintained Laravel-native MQTT package. v1.8.0 (2026-03-27) supports Laravel 13, PHP 8.2+. Provides named connections, QoS 0/1/2, event loop with signal handling for long-running artisan commands. Wraps php-mqtt/client v2.3. |
| Laravel Reverb | ^1.10 | WebSocket server for real-time browser push | Official first-party Laravel WebSocket server. v1.10.0 (2026-03-29) supports Laravel 13. Uses Pusher protocol so it works with Laravel Echo out of the box. Horizontal scaling via Redis. No third-party service needed. |
| laravel-echo + @laravel/echo-vue | ^2.3 / ^2.3 | Client-side WebSocket listener with Vue composables | Official Laravel packages. `@laravel/echo-vue` provides `useEcho()` composable and `configureEcho()` with automatic channel cleanup on component unmount. Replaces manual Echo instance management. Requires `pusher-js` as transport. |
| pusher-js | ^8.4 | WebSocket transport protocol client | Required by Laravel Echo/Reverb (Pusher protocol). Not actually connecting to Pusher's service -- Reverb implements the protocol locally. |
| Intervention Image + Laravel integration | ^4.0 / ^4.0 | Photo resize, compress, format conversion | v4.0.0 (2026-03-28) requires PHP 8.3+ which matches our runtime (8.4.19). New libvips driver option for better performance. GD driver sufficient for our needs (resize to 1080p, compress to <1MB JPEG). Official `intervention/image-laravel` package provides service provider. |
| Mapbox GL JS | ^3.21 | Interactive map with camera markers and real-time overlays | v3.21.0 (2026-04-06). Built-in TypeScript types since v3.5 (no @types package needed). WebGL 2 rendering, custom styles support (HelderDene account styles). Project already committed to Mapbox per KEY DECISIONS. |
| Eclipse Mosquitto | ^2.0 | MQTT broker | Industry-standard, lightweight MQTT v3.1.1/v5 broker. Official Docker image available. Cameras connect to this broker; Laravel subscribes via php-mqtt. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| pusher-js | ^8.4 | Pusher protocol WebSocket transport | Always -- required by laravel-echo for Reverb connections |
| @laravel/echo-vue | ^2.3 | Vue composables for Echo channels | Always -- provides `useEcho()`, `useConnectionStatus()`, automatic cleanup |
| intervention/image-laravel | ^4.0 | Laravel service provider for Intervention Image | Always -- registers ImageManager in container, publishes config |
| ext-gd (PHP extension) | bundled | GD image processing driver | Default driver for Intervention Image. Available in standard PHP. Sufficient for resize/compress operations. |
| ext-pcntl (PHP extension) | bundled | Process control signals | Required for graceful MQTT subscriber shutdown via `pcntl_signal()` |
| Supervisor | ^4.x | Process manager for MQTT listener daemon | Production -- keeps `php artisan mqtt:listen` running, auto-restarts on crash |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| Mosquitto CLI (`mosquitto_pub`, `mosquitto_sub`) | Test MQTT message flow locally | Install via `brew install mosquitto`. Invaluable for simulating camera messages during development. |
| Laravel Herd Reverb service | Local WebSocket server | Herd Pro can manage Reverb as a service. Alternatively run `php artisan reverb:start --debug` manually. |
| MQTTX | GUI MQTT client for testing | Free desktop app. Useful for crafting complex JSON payloads that simulate camera RecPush events. |

## Installation

```bash
# PHP dependencies (backend)
composer require php-mqtt/laravel-client:^1.8 laravel/reverb:^1.10 intervention/image:^4.0 intervention/image-laravel:^4.0

# JavaScript dependencies (frontend)
npm install mapbox-gl@^3.21 @laravel/echo-vue@^2.3 laravel-echo@^2.3 pusher-js@^8.4

# Publish configs
php artisan vendor:publish --provider="PhpMqtt\Client\MqttClientServiceProvider"
php artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"

# Install broadcasting with Reverb
php artisan install:broadcasting --reverb

# Development tools (optional, macOS)
brew install mosquitto
```

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| php-mqtt/laravel-client | salmanzafar/laravel-mqtt | Never -- salmanzafar/laravel-mqtt is less maintained, smaller community, fewer features. php-mqtt is the standard. |
| Laravel Reverb | Soketi / Pusher / Ably | Only if you need a managed service. Reverb is first-party, free, no external dependency, and officially supported by Laravel 13. Soketi is abandoned. Pusher/Ably add cost and latency for no benefit in a single-site deployment. |
| @laravel/echo-vue | Manual laravel-echo setup | Never for Vue apps -- @laravel/echo-vue is the official Vue integration with composables and auto-cleanup. Manual setup requires boilerplate and risks memory leaks from unmanaged subscriptions. |
| Intervention Image v4 | Intervention Image v3 (^3.11) | If stuck on PHP <8.3. v3.11.7 is still maintained but v4 is the active development line. Our runtime is PHP 8.4 so v4 is the right choice. |
| Intervention Image (GD driver) | Intervention Image (libvips driver) | Use libvips if processing high volumes of images (100+/second). For our use case (enrollment photo preprocessing, <200 personnel), GD is sufficient and requires no additional system dependencies. |
| Mapbox GL JS (direct) | v-mapbox / vue-mapbox-gl wrappers | Never -- Mapbox officially recommends direct usage with Vue (not wrappers). Wrapper libraries are community-maintained, lag behind Mapbox releases, and add abstraction over a straightforward API. Use composables to manage map lifecycle. |
| Mosquitto | EMQX / HiveMQ | Only if you need clustering or >10K concurrent connections. Mosquitto is lightweight, battle-tested, and appropriate for 8 cameras on a single server. EMQX is overkill for this scale. |
| Supervisor | systemd service | Either works. Supervisor is Laravel's documented recommendation for queue workers and long-running processes. Use systemd if the deployment environment prefers it. |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| @types/mapbox-gl | Mapbox GL JS v3.5+ ships its own TypeScript types. Installing @types/mapbox-gl causes type conflicts. | mapbox-gl (types included) |
| vue-mapbox-gl / v-mapbox wrappers | Community-maintained, lag behind Mapbox v3 releases, incomplete TypeScript support, add unnecessary abstraction. | Direct mapbox-gl usage with Vue composables |
| laravel-websockets (beyondcode) | Abandoned. No Laravel 13 support. Reverb is its official successor. | Laravel Reverb |
| Soketi | Development has stalled. Was a Pusher-compatible server but Reverb supersedes it in the Laravel ecosystem. | Laravel Reverb |
| salmanzafar/laravel-mqtt | Less maintained than php-mqtt/laravel-client. Fewer GitHub stars, older codebase, no v1.8-equivalent. | php-mqtt/laravel-client |
| Inertia::lazy() | Removed in Inertia v3. Will throw errors. | Inertia::optional() |
| Axios for HTTP from Vue | Removed from Inertia v3. Bundled XHR client replaces it. | Inertia's built-in HTTP client or useHttp() hook |
| Intervention Image v2 | EOL. Incompatible API. Major breaking changes from v2 to v3/v4. | intervention/image ^4.0 |

## Stack Patterns by Variant

**If deploying with Docker (recommended for production):**
- Run Mosquitto as a Docker container (`eclipse-mosquitto:2`)
- Run Reverb as a separate process managed by Supervisor
- Run the MQTT listener (`php artisan mqtt:listen`) as a separate Supervisor process
- MySQL in a container or managed database service

**If deploying with Laravel Herd (local development):**
- Install Mosquitto via Homebrew (`brew install mosquitto`)
- Run Reverb via `php artisan reverb:start` or Herd Pro service
- Run MQTT listener via `php artisan mqtt:listen` in a terminal
- SQLite for development, MySQL for staging/production

**If deploying to Laravel Cloud:**
- Reverb is natively supported (managed WebSocket clusters)
- MQTT listener needs a worker process configuration
- Mosquitto broker must be provisioned separately (not included in Cloud)

## Version Compatibility

| Package A | Compatible With | Notes |
|-----------|-----------------|-------|
| php-mqtt/laravel-client ^1.8 | Laravel 10-13, PHP 8.2+ | Uses php-mqtt/client ^2.1 internally |
| laravel/reverb ^1.10 | Laravel 10-13, PHP 8.2+ | Requires ext-pcntl for production |
| intervention/image ^4.0 | PHP 8.3+ | Breaking change from v3 API (method renames) |
| intervention/image-laravel ^4.0 | Laravel 8-13, PHP 8.3+ | Must match intervention/image major version |
| mapbox-gl ^3.21 | Any modern browser with WebGL 2 | TypeScript types built-in since v3.5 |
| @laravel/echo-vue ^2.3 | laravel-echo ^2.x, pusher-js ^8.x, Vue 3 | Requires both laravel-echo and pusher-js as peer deps |
| laravel-echo ^2.3 | pusher-js ^8.x | Pusher protocol transport required for Reverb |
| pusher-js ^8.4 | laravel-echo ^2.x | WebSocket transport layer |

## Environment Variables (New)

```ini
# MQTT Broker
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_CLIENT_ID=fras-server
MQTT_USERNAME=
MQTT_PASSWORD=

# Laravel Reverb (auto-configured by install:broadcasting --reverb)
REVERB_APP_ID=fras
REVERB_APP_KEY=fras-key
REVERB_APP_SECRET=fras-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite-exposed for frontend
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Mapbox
VITE_MAPBOX_ACCESS_TOKEN=pk.your_token_here

# Database (switch from SQLite to MySQL for FRAS)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fras
DB_USERNAME=root
DB_PASSWORD=
```

## Process Architecture

The FRAS system requires **four long-running processes** beyond the standard Laravel web server:

1. **Reverb WebSocket server** -- `php artisan reverb:start`
2. **Queue worker** -- `php artisan queue:work` (processes broadcast events)
3. **MQTT listener** -- `php artisan mqtt:listen` (custom command, subscribes to camera topics)
4. **Scheduler** -- `php artisan schedule:work` (storage retention cleanup, camera offline detection)

All four should be managed by Supervisor in production. For development, `composer run dev` can orchestrate them via `concurrently` (already in the existing stack).

## Sources

- [php-mqtt/laravel-client GitHub](https://github.com/php-mqtt/laravel-client) -- v1.8.0 release, Laravel 13 support confirmed (HIGH confidence)
- [php-mqtt/laravel-client Packagist](https://packagist.org/packages/php-mqtt/laravel-client) -- version verification (HIGH confidence)
- [Laravel Reverb Packagist](https://packagist.org/packages/laravel/reverb) -- v1.10.0, Laravel 13 support confirmed (HIGH confidence)
- [Laravel 13.x Broadcasting Docs](https://laravel.com/docs/13.x/broadcasting) -- @laravel/echo-vue setup, Reverb configuration (HIGH confidence)
- [Laravel 13.x Reverb Docs](https://laravel.com/docs/13.x/reverb) -- installation, configuration, production tuning (HIGH confidence)
- [Intervention Image v4 Docs](https://image.intervention.io/v4) -- libvips driver, API changes (HIGH confidence)
- [Intervention Image v4 Upgrade Guide](https://image.intervention.io/v4/getting-started/upgrade) -- v3-to-v4 breaking changes (HIGH confidence)
- [intervention/image-laravel Packagist](https://packagist.org/packages/intervention/image-laravel) -- v4.0.0, PHP 8.3+ (HIGH confidence)
- [Mapbox GL JS npm](https://www.npmjs.com/package/mapbox-gl) -- v3.21.0 (HIGH confidence)
- [Mapbox GL JS TypeScript Migration](https://github.com/mapbox/mapbox-gl-js/issues/13203) -- built-in types since v3.5 (HIGH confidence)
- [Mapbox Vue Tutorial](https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/) -- direct usage recommended over wrappers (HIGH confidence)
- [@laravel/echo-vue npm](https://www.npmjs.com/package/@laravel/echo-vue) -- v2.3.0, useEcho composable (HIGH confidence)
- [laravel-echo npm](https://www.npmjs.com/package/laravel-echo) -- v2.3.3 (HIGH confidence)
- [pusher-js npm](https://www.npmjs.com/package/pusher-js) -- v8.4.3+ (HIGH confidence)
- [Eclipse Mosquitto Docker Hub](https://hub.docker.com/_/eclipse-mosquitto) -- official Docker image (HIGH confidence)

---
*Stack research for: FRAS MQTT + Real-time + Image Processing + Map Visualization*
*Researched: 2026-04-10*

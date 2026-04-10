---
phase: 1
slug: infrastructure-mqtt-foundation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-10
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 (PHP) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter={test}` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter={test}`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | INFRA-01 | — | N/A | feature | `php artisan test --compact --filter=MysqlConnection` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | INFRA-01 | — | N/A | feature | `php artisan test --compact --filter=FrasMigration` | ❌ W0 | ⬜ pending |
| 01-02-01 | 02 | 1 | INFRA-02 | — | N/A | feature | `php artisan test --compact --filter=MqttConfig` | ❌ W0 | ⬜ pending |
| 01-02-02 | 02 | 1 | INFRA-03 | — | N/A | feature | `php artisan test --compact --filter=ReverbConfig` | ❌ W0 | ⬜ pending |
| 01-03-01 | 03 | 2 | INFRA-04 | — | N/A | manual | N/A (Supervisor config review) | — | ⬜ pending |
| 01-03-02 | 03 | 2 | INFRA-05 | — | N/A | feature | `php artisan test --compact --filter=DevCommand` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Fras/MysqlConnectionTest.php` — stubs for INFRA-01 MySQL connectivity
- [ ] `tests/Feature/Fras/FrasMigrationTest.php` — stubs for INFRA-01 migration verification
- [ ] `tests/Feature/Fras/MqttConfigTest.php` — stubs for INFRA-02 MQTT configuration
- [ ] `tests/Feature/Fras/ReverbConfigTest.php` — stubs for INFRA-03 Reverb configuration
- [ ] `tests/Feature/Fras/DevCommandTest.php` — stubs for INFRA-05 dev orchestration

*Existing Pest infrastructure covers all phase requirements.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Supervisor starts and auto-restarts processes | INFRA-04 | Supervisor runs outside PHP process; requires system-level process management | 1. Copy deploy/supervisor/*.conf to /etc/supervisor/conf.d/ 2. Run `supervisorctl reread && supervisorctl update` 3. Verify processes running with `supervisorctl status` 4. Kill a process and verify auto-restart |
| MQTT broker accepts connections | INFRA-02 | Requires running Mosquitto broker on network | 1. Start Mosquitto 2. Run `php artisan tinker --execute 'app("mqtt")->publish("test/topic", "hello")'` 3. Verify message received |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

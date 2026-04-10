---
status: partial
phase: 01-infrastructure-mqtt-foundation
source: [01-VERIFICATION.md]
started: 2026-04-10T08:00:00Z
updated: 2026-04-10T08:00:00Z
---

## Current Test

[awaiting human testing]

## Tests

### 1. MQTT broker live connectivity
expected: Start Mosquitto MQTT broker and run: mosquitto_pub -t 'mqtt/face/heartbeat' -m '{}' then verify fras:mqtt-listen receives and routes the message. The HeartbeatHandler logs 'Heartbeat received (stub)' without error; no connection timeout.
result: [pending]

### 2. Reverb WebSocket browser round-trip
expected: Start php artisan reverb:start and open a browser tab pointing at the app; use browser console to check Echo WebSocket connected. WebSocket connection established to ws://localhost:8080 (or configured port); no connection errors in browser console.
result: [pending]

## Summary

total: 2
passed: 0
issues: 0
pending: 2
skipped: 0
blocked: 0

## Gaps

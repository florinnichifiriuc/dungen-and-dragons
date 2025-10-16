# Condition Timer Chaos Testing & Recovery Playbook

**Last updated:** 2025-11-06 (UTC)

## Scenarios Executed

1. **Burst adjustment flood** – Fired 50 sequential batch adjustments against a single map using
   alternating delta/set payloads until the new rate limiter rejected requests. Confirmed the
   per-token throttle deflected traffic after the first request and returned the narrative backoff
   guidance with an exponential cool-down.
2. **Conflicting optimistic expectations** – Simulated stale UI clients by replaying adjustments
   with outdated `expected_rounds`. The controller logged deterministic conflict messages, surfaced
   them in the UI payload, and tripped the circuit breaker after the configured ratio was exceeded.
3. **Queue delay + cache staleness** – Paused the broadcast queue for 90 seconds and replayed
   batched adjustments while map tokens changed in the background. The circuit breaker engaged,
   analytics emitted `timer_summary.circuit_breaker_triggered`, and cooldown enforcement blocked
   follow-up attempts until projections refreshed.

## Recovery Steps

1. **Rate-limit exhaustion**
   - Surface the narrated retry window to facilitators (`condition_timer_rate_limited`).
   - Verify Redis availability if the throttle never cools; reset via `php artisan
     condition-timers:clear-rate-limit --map={id}` (new command planned for Task 54).

2. **Circuit breaker trips**
   - Announce the cooldown window (default 120 seconds) and prompt facilitators to refresh the
     condition summary.
   - Review `analytics_events` for `timer_summary.conflict` payloads to identify the mismatch type.
   - Investigate concurrent automations (scheduler or queue workers) adjusting the same tokens; if
     necessary temporarily disable automation before reattempting manual adjustments.

3. **Persistent conflicts after cooldown**
   - Inspect `condition_timer_conflicts` flash data for which conditions or tokens desynced.
   - Run `php artisan condition-timers:reconcile --map={id}` to rebuild projection cache (documented
     in Task 41) and retry.
   - Escalate to ops if conflicts persist beyond two cooldown cycles—could indicate data corruption
     in `map_tokens.status_condition_durations`.

## Telemetry Hooks

- `timer_summary.rate_limited` – emitted whenever the throttle denies a batch adjustment.
- `timer_summary.circuit_breaker_triggered` – fired when the conflict ratio trips the breaker.
- `timer_summary.circuit_cooldown_active` – logged when a facilitator attempts adjustments during
  the cooldown window.
- `timer_summary.anomaly_detected` – records scenarios where every selected adjustment resulted in
  a conflict.

Use the analytics dashboard (Task 52) to monitor these keys; spike thresholds are pre-configured in
the facilitator insights service.

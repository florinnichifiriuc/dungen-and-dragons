# Task 38 – Token Condition Timer Batch Adjustments

**Status:** Planned
**Owner:** Engineering
**Dependencies:** Task 37

## Intent
Provide facilitators with the ability to select multiple timers and apply a shared adjustment (extend, reduce, or reset) so mass status updates between rounds are quick and consistent.

## Subtasks
- [ ] Audit current dashboard data contract and document the batch adjustment API payload, validation rules, and optimistic update expectations.
- [ ] Design lightweight multi-select affordances that work across factions, filtered states, and mobile breakpoints.
- [ ] Extend Form Requests and policies to validate multi-entity adjustments and guard against race conditions.
- [ ] Apply bulk adjustments optimistically while queueing a consolidated payload to the server and reconciling on acknowledgement.
- [ ] Surface summary feedback (e.g., "3 timers extended by 1 round") without overwhelming the dashboard, including confirmation banners and accessibility-friendly alerts.
- [ ] Instrument conflict/error telemetry and define alerts for repeated reconciliation failures.

## Notes
- Batch controls should respect maximum duration boundaries and avoid partial failures.
- Consider keyboard shortcuts or focus management for speed-running between turns.
- Coordinate with future player-facing summaries so mass updates remain transparent.
- Validate multi-select behavior against wireframes from Task 42 before development starts.
- Provide rollback guidance in case telemetry surfaces regression spikes.

## Log
- 2025-10-27 20:05 UTC – Identified the need for batch adjustments while defining quick clear flows.

# Task 38 – Token Condition Timer Batch Adjustments

**Status:** Planned
**Owner:** Engineering
**Dependencies:** Task 37

## Intent
Provide facilitators with the ability to select multiple timers and apply a shared adjustment (extend, reduce, or reset) so mass status updates between rounds are quick and consistent.

## Subtasks
- [ ] Design lightweight multi-select affordances that work across factions and filtered states.
- [ ] Apply bulk adjustments optimistically while queueing a consolidated payload to the server.
- [ ] Surface summary feedback (e.g., "3 timers extended by 1 round") without overwhelming the dashboard.

## Notes
- Batch controls should respect maximum duration boundaries and avoid partial failures.
- Consider keyboard shortcuts or focus management for speed-running between turns.
- Coordinate with future player-facing summaries so mass updates remain transparent.

## Log
- 2025-10-27 20:05 UTC – Identified the need for batch adjustments while defining quick clear flows.

# Task 35 – Token Condition Timer Quick Adjustments

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 34

## Intent
Let facilitators nudge condition timers up or down from the dashboard without opening each token editor. Quick adjustments should broadcast instantly, clamp to supported ranges, and clear timers gracefully when an effect becomes permanent.

## Subtasks
- [x] Add plus/minus controls to the timer dashboard with optimistic UI updates and realtime persistence.
- [x] Normalize timer adjustments on the backend so clearing a countdown retains the active preset and broadcasts empty durations.
- [x] Cover timer adjustment behaviour with feature tests and update planning artefacts to record the delivery.

## Notes
- Buttons should stay compact and respect the existing indigo styling so the dashboard remains glanceable.
- Decreasing a timer past zero clears the countdown but leaves the condition active for indefinite effects.
- Optimistic UI updates keep the dashboard responsive while the patch request resolves.

## Log
- 2025-10-27 14:50 UTC – Scoped adjustment UX, optimistic update needs, and backend normalization for cleared timers.
- 2025-10-27 15:35 UTC – Implemented quick adjust controls, normalization updates, feature coverage, and refreshed documentation.

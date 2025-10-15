# Task 31 – Token Condition Timers

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 30

## Intent
Track how many rounds remain on each preset condition so facilitators can see timers alongside the battlefield state. Durations should stay in sync across realtime broadcasts and vanish automatically when a preset clears.

## Subtasks
- [x] Extend map tokens with persistent condition duration storage, validation, and broadcast payload updates.
- [x] Layer duration inputs into the map workspace for placement and inline editing with badge readouts.
- [x] Cover normalization rules with feature tests and refresh planning artifacts with the new timer capability.

## Notes
- Durations clamp between 1 and 20 rounds; leaving an input blank removes the timer for that condition.
- Only active presets keep timers; clearing a preset clears its duration across clients.
- Badge labels show the remaining rounds (e.g., Poisoned (3r)) to keep encounter states legible at a glance.

## Log
- 2025-10-27 00:45 UTC – Scoped duration storage, validation ranges, and UI integration points.
- 2025-10-27 01:35 UTC – Implemented persistence, UI controls, realtime syncing, tests, and updated documentation.

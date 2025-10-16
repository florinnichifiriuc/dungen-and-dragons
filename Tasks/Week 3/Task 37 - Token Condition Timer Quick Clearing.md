# Task 37 – Token Condition Timer Quick Clearing

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 36

## Intent
Allow facilitators to clear expiring or mistakenly applied condition timers straight from the dashboard so the roster stays accurate without opening the token editor.

## Subtasks
- [x] Add a quick-clear action to each dashboard timer chip that removes the condition and its duration.
- [x] Maintain optimistic UI updates and broadcast payload parity when a timer is cleared.
- [x] Update planning artefacts to capture the new quick clearing capability and follow-up tasks.

## Notes
- Clearing a timer should remove the condition from the token entirely so badge clutter disappears immediately.
- Avoid disruptive confirmation modals; rely on accessible labels and the existing optimistic flow for speed.
- Ensure adjustments respect the existing maximum duration guard rails so reapplying a timer continues to clamp values.

## Log
- 2025-10-27 19:45 UTC – Scoped the need for dashboard-level clearing controls while reviewing Task 36 delivery.
- 2025-10-27 21:30 UTC – Implemented the dashboard clear action, optimistic syncing, and documentation updates.

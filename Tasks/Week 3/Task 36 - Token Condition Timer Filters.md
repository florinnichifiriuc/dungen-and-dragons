# Task 36 – Token Condition Timer Filters

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 35

## Intent
Provide facilitators with tools to narrow the condition timer dashboard to the effects that need attention. Filters should honor token faction selection, spotlight urgent timers, and make it easy to find timers by token or condition name.

## Subtasks
- [x] Add search controls that match against token names and condition labels on the timer dashboard.
- [x] Layer urgency toggles that highlight timers ending within a critical threshold while coexisting with faction filters.
- [x] Refresh planning artefacts to capture the new filtering capabilities and delivery status.

## Notes
- The dashboard should still communicate the total timers available so facilitators understand what is hidden by filters.
- Critical timers are defined as those with three or fewer rounds remaining for this iteration.
- Filters should not interfere with optimistic updates already powering quick adjustments.

## Log
- 2025-10-27 16:45 UTC – Scoped faction-aware filtering, urgency toggle, and search requirements for the dashboard.
- 2025-10-27 18:20 UTC – Implemented the filtering controls, wired them into the dashboard, and updated planning records.

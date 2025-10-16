# Task 34 – Token Condition Timer Dashboard

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 33

## Intent
Give facilitators a single glance view of all active condition timers so they can plan upcoming turns without cross-referencing each token. The dashboard should surface how many rounds remain for every timed preset, highlight soonest expirations, and stay synchronized with realtime token updates.

## Subtasks
- [x] Aggregate active condition timers for the current map, sorted by the soonest rounds remaining.
- [x] Present the countdown dashboard in the map workspace with faction styling and urgency highlights.
- [x] Document the new workflow and ensure progress tracking captures the milestone.

## Notes
- Use warm-to-hot accent colours as timers approach zero so impending expirations stand out at a glance.
- Clamp timers to the configured duration ceiling when displaying to keep the UI predictable even if future rules expand limits.
- The dashboard should gracefully disappear when no active timers remain to avoid empty chrome.

## Log
- 2025-10-27 11:05 UTC – Planned the aggregated timer layout and urgency styling.
- 2025-10-27 12:10 UTC – Implemented the dashboard, wired realtime updates, and refreshed documentation.

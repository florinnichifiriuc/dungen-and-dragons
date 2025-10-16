# Task 32 – Token Condition Countdown Automation

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 31

## Intent
Automatically step down active condition timers whenever a region turn processes so facilitators do not need to micro-manage badges. Expired timers should clear both their durations and related conditions while remaining durations broadcast to connected clients.

## Subtasks
- [x] Detect region turn processing and decrement each active condition timer on associated map tokens.
- [x] Clear conditions whose timers expire and broadcast refreshed token payloads to connected viewers.
- [x] Extend coverage and planning artifacts to capture the new automation milestone.

## Notes
- Only tokens within the processed region are adjusted; other regions keep their timers untouched.
- Conditions without timers stay in place. When a timer reaches zero, both the timer and condition fall away automatically.
- Broadcast payloads continue to use the canonical condition ordering defined on the model to avoid UI churn.

## Log
- 2025-10-27 04:20 UTC – Planned timer decrement workflow and identified broadcasting integration points.
- 2025-10-27 05:15 UTC – Implemented countdown automation, broadcasts, tests, and documentation updates.

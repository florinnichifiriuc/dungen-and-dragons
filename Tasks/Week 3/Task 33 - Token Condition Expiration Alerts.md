# Task 33 – Token Condition Expiration Alerts

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 32

## Intent
Surface clear notifications when condition timers lapse so facilitators never miss that a token has recovered. When the turn scheduler clears an expiring preset it should emit a realtime alert highlighting the affected token and preset names for everyone on the map.

## Subtasks
- [x] Track which conditions expire during automated turn processing and broadcast a dedicated event per token.
- [x] Display realtime alerts in the map workspace with condition names and quick dismissal controls.
- [x] Update documentation and coverage to record the new workflow.

## Notes
- Alerts should only fire when a timer actually hits zero; manual removals remain unchanged for now.
- Broadcast payloads include both the token id and display name so the UI can reference the correct badge styling.
- Client-side alerts persist briefly (90s) unless dismissed to keep the focus on current rounds.

## Log
- 2025-10-27 08:05 UTC – Planned alert broadcast payloads and UI surfacing strategy.
- 2025-10-27 09:10 UTC – Implemented expiration tracking, realtime alerts, tests, and documentation updates.

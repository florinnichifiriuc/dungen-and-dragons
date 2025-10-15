# Task 26 – Token Initiative Tracking

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 24, Task 25

## Intent
Give encounter facilitators lightweight initiative and condition tracking directly on the tactical map so they can keep pace without jumping between the workspace initiative tracker and token overlays. Tokens should expose sortable initiative values, visible badges, and editable status notes that broadcast in real time.

## Subtasks
- [x] Extend the map token schema, validation, and payloads with optional initiative and status metadata.
- [x] Update the map workspace UI to capture, display, and edit initiative values and condition notes with realtime ordering.
- [x] Ensure broadcasts and feature coverage assert creation, update, and clearing behaviours for the new metadata.
- [x] Document the work across the task plan, progress log, and README.

## Notes
- Initiative badges sort tokens descending so the most pressing combatants float to the top while non-combat props fall to the bottom.
- Clearing a field sends a null payload so hidden information is removed from both the UI and the database.
- Status notes use the same textarea styling as GM notes to keep editing consistent across the workspace.

## Log
- 2025-10-26 07:45 UTC – Scoped schema adjustments, realtime payload needs, and UI layout for initiative badges and status notes.
- 2025-10-26 09:20 UTC – Implemented backend fields, Inertia updates, realtime ordering, tests, and documentation refresh.

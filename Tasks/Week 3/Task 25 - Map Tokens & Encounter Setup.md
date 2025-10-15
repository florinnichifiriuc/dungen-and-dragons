# Task 25 – Map Tokens & Encounter Setup

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 9, Task 24

## Intent
Give Game Masters quick controls to drop, reposition, and hide encounter tokens so the modular map board can stage creatures, props, and objectives without external tools. Tokens should follow the same realtime contract as tiles while respecting group permissions.

## Subtasks
- [x] Add token persistence, validation, policies, and broadcasts to the Laravel backend.
- [x] Layer token creation and management UI into the map workspace with color, size, note, and visibility controls.
- [x] Stream token updates over the existing map channel so collaborators stay in sync.
- [x] Cover token CRUD, authorization, and broadcasting behaviour with feature tests.
- [x] Document the delivery across the task log, plan, and README.

## Notes
- Token visibility respects the same GM/DM roles as tile editing, letting party members observe results without modifying the board.
- Color pickers use browser-native inputs for quick palette tweaks, and hidden tokens retain their state when revealed.
- Broadcast payloads mirror Inertia props so the workspace can update without a full reload.

## Log
- 2025-10-25 14:40 UTC – Scoped map token schema, policy rules, and UI touchpoints alongside realtime payload design.
- 2025-10-25 17:30 UTC – Implemented token persistence, workspace controls, broadcasts, tests, and documentation updates.

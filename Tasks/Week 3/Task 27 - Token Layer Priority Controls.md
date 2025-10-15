# Task 27 – Token Layer Priority Controls

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 25, Task 26

## Intent
Give facilitators a lightweight way to control which miniatures appear on top when multiple creatures share the same space or lack initiative values. A dedicated layer priority keeps lairs, auras, and hidden props tucked underneath while spotlighting active combatants.

## Subtasks
- [x] Extend map tokens with a signed `z_index` column, validation, and broadcasting payload updates.
- [x] Surface layer priority inputs in the map workspace create/edit flows with ordering hints and live badge indicators.
- [x] Cover layer defaults, updates, and reset behaviour with feature tests.
- [x] Document the addition across planning artifacts.

## Notes
- The layer slider accepts -100 through 100 so fog overlays, lair actions, or environmental markers can sit below combatants.
- Initiative sorting still takes precedence; layer priority breaks ties or orders non-initiative props.
- Clearing the field normalises to layer zero to avoid negative drift from blank submissions.

## Log
- 2025-10-26 12:30 UTC – Scoped schema update, validation, ordering requirements, and UI layout for layer controls.
- 2025-10-26 13:55 UTC – Implemented migrations, backend normalisation, UI inputs/badges, realtime payloads, and Pest coverage.

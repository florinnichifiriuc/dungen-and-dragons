# Task 29 – Token Vitality Tracking

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 25, Task 26

## Intent
Give facilitators lightweight hit point tracking directly on map tokens so they can monitor combat pacing without leaving the encounter workspace. Support current, maximum, and temporary hit points with realtime updates so remote co-GMs stay aligned.

## Subtasks
- [x] Extend map tokens with hit point, maximum, and temporary health columns, validation, normalization, factories, and broadcast payload support.
- [x] Update the map workspace token forms, badges, and realtime handlers to surface vitality inputs alongside initiative, faction, and layer controls.
- [x] Cover vitality workflows with feature tests and refresh planning artifacts to log Task 29 completion.

## Notes
- Current hit points allow negatives so facilitators can signal dying states; temporary hit points clamp to zero or above.
- Leaving any vitality field blank clears the stored value to avoid stale data after a creature heals or leaves the map.
- Vitality badges appear with initiative and faction chips so the primary combat context is immediately visible.

## Log
- 2025-10-26 19:15 UTC – Scoped hit point ranges, blank-normalization behaviour, and UI badge placement.
- 2025-10-26 20:10 UTC – Implemented schema update, workspace controls, realtime syncing, tests, and documentation refresh.

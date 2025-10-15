# Task 30 – Token Condition Presets

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 29

## Intent
Layer quick-select condition presets onto map tokens so facilitators can flag crowd-control effects without typing each turn. Structured condition data should broadcast in real time, render as badges, and sync across players to keep the battlefield state tight.

## Subtasks
- [x] Extend map tokens with structured `status_conditions` storage, validation, and normalization utilities.
- [x] Update the map workspace token forms, chips, and realtime payloads to manage preset conditions alongside freeform status notes.
- [x] Cover condition workflows with feature tests and refresh planning artifacts to log Task 30 completion.

## Notes
- Presets should cover core 5e conditions (Blinded, Charmed, Deafened, Frightened, Grappled, Incapacitated, Invisible, Paralyzed, Petrified, Poisoned, Prone, Restrained, Stunned, Unconscious, Exhaustion).
- Empty selections should clear stored presets rather than retain stale values; order them consistently for quick scanning.
- Badges can share styling with faction/initiative chips but lean amber to differentiate from vitality readouts.

## Log
- 2025-10-26 21:15 UTC – Scoped condition preset requirements, validation flow, and UI placement.
- 2025-10-26 22:40 UTC – Implemented structured condition storage, UI toggles, realtime syncing, tests, and documentation updates.

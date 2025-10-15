# Task 24 – Map Fog of War Controls

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 9, Task 23

## Intent
Allow Dungeon Masters to shroud specific map tiles from players and reveal them over time without juggling raw JSON. The map workspace should surface fog status at a glance and provide quick actions to toggle visibility as scenes unfold.

## Subtasks
- [x] Add validation and routing to persist per-tile fog state on maps.
- [x] Expose fog metadata to the Inertia map workspace payload.
- [x] Layer visibility toggles, hidden badges, and reveal-all controls into the map UI.
- [x] Ensure fog updates respect permissions and only target tiles on the active map.
- [x] Cover fog state flows with feature tests and document the rollout.

## Notes
- Hidden tiles are tracked by ID so future realtime syncing or API consumers can share the same contract.
- Reveal all clears the fog payload entirely, keeping legacy JSON overrides intact.
- Buttons are disabled while a fog update is pending to avoid racing requests and to keep the UI deterministic.

## Log
- 2025-10-25 08:45 UTC – Scoped fog controller, request validation, and UI affordances.
- 2025-10-25 11:05 UTC – Implemented fog persistence, Inertia payload updates, UI toggles, and feature coverage.

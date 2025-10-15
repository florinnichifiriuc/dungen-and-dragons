# Task 8 – Worlds & Regions CRUD

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 3, Task 7
**Related Backlog Items:** Build world + region CRUD with configurable turn durations and DM assignments

## Intent
Empower groups to sketch entire realms by managing named worlds and the regions that live within them. Each world should carry a narrative summary plus a default turn cadence that guides fresh regions. Region assignment must now respect world ownership while keeping the existing dungeon master workflow intact.

## Subtasks
- [x] Scaffold worlds domain: migration, model, policy bindings, and factories tied to groups.
- [x] Provide world management flows (create, edit, archive) within the group dashboard using Inertia.
- [x] Update region CRUD to require a world selection, inherit default turn cadence, and support flexible scheduling windows.
- [x] Ensure authorization and validation guardrails prevent cross-group access or orphaned worlds.
- [x] Expand Pest coverage for world creation, edits, deletions, and region scheduling against world defaults.
- [x] Refresh docs (task plan, progress log) once work wraps.

## Notes
- Limit turn duration inputs between 1–168 hours to cover both rapid-fire play and week-long strategy pacing.
- Only group owners should be able to delete a world; dungeon masters may update metadata and assign regions.
- Future tasks will hook worlds into shared map tooling, so keep the schema extensible (summaries, long-form lore fields).

## Log
- 2025-10-15 18:30 UTC – Kickoff. Reviewed existing region flows and outlined world schema plus UI hooks for integration.
- 2025-10-15 20:30 UTC – Delivered world CRUD, tied regions to worlds with pacing defaults, refreshed group dashboards, and added Pest coverage.

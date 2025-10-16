# Task 39 – Condition Timer Player Summaries

**Status:** Planned
**Owner:** Product & Narrative Design
**Dependencies:** Task 38

## Intent
Expose a read-only, lore-friendly summary of active condition timers to players so they understand looming effects without leaking GM-only information or disrupting surprise mechanics.

## Subtasks
- [ ] Define which timer details are safe for players and how to redact hidden factions or GM-only notes, feeding into a projection cache schema and invalidation rules.
- [ ] Implement projection service and broadcast pipeline that emits player-safe JSON distinct from GM payloads, with logging for cache misses and rebuild attempts.
- [ ] Implement a player-facing panel (session workspace + optional shareable link) with responsive styling and offline-friendly hydration.
- [ ] Coordinate alert copy with narrative tone, leveraging curated condition templates for immersive summaries.
- [ ] Add automated tests covering faction redaction, localization hooks, and stale cache recovery scenarios.
- [ ] Document QA checklist covering manual scenarios (hidden enemies, simultaneous expirations) for Task 41 reuse.

## Notes
- Player view should update in realtime alongside GM adjustments but avoid showing exact rounds for hidden enemies unless flagged.
- Consider integrating with the session recap log for historical context.
- Ensure localization hooks exist for future translation work.
- Reference narrative copy deck outputs from Task 43 during implementation.
- Prepare analytics emitters defined in Task 44 for launch readiness.

## Log
- 2025-10-27 20:20 UTC – Logged follow-up to extend timer visibility to players after batch tooling.

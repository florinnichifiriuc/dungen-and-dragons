# Task 28 – Token Factions & Filters

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 25, Task 27

## Intent
Empower encounter leads to tag each map token with a faction so they can instantly distinguish allies, hostiles, neutral parties, and environmental hazards. Surface badge styling and a quick filter strip in the map workspace so large skirmishes stay readable without sacrificing existing initiative or layering controls.

## Subtasks
- [x] Extend map tokens with a `faction` attribute, validation, defaults, factories, and broadcast payload support.
- [x] Update the map workspace token forms and realtime listener to capture, display, and edit faction metadata with badges.
- [x] Add GM-facing faction filters, counts, and empty-state messaging in the workspace UI.
- [x] Cover faction workflows with feature tests and document progress across planning artifacts.

## Notes
- Faction values are constrained to Allied, Hostile, Neutral, or Hazard to match common combat groupings.
- Blank submissions normalize back to Neutral so accidental clears never leave stale values.
- Filters show total counts per faction and honour realtime updates so remote facilitators stay in sync.

## Log
- 2025-10-26 16:45 UTC – Scoped faction taxonomy, validation matrix, UI placement, and filter interactions.
- 2025-10-26 17:45 UTC – Implemented schema update, workspace badges/filters, broadcast payloads, coverage, and documentation refresh.

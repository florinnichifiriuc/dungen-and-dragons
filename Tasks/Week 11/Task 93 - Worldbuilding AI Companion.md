# Task 93 – Worldbuilding AI Companion

**Status:** Planned
**Owner:** Narrative Design & Engineering
**Dependencies:** Task 8, Task 13

## Intent
Lower the barrier to creating worlds, regions, and templates by embedding an AI-driven ideation panel into the world management hub so facilitators can prompt quick lore scaffolding, structure proposals, and starter templates from short phrases.

## Subtasks
- [ ] Add a collapsible "Worldbuilding Companion" chat panel to world, region, and template screens that streams suggestions via the existing AI service layer.
- [ ] Implement prompt presets tailored to world overview, regional points of interest, and reusable tile template outlines, with the ability to accept and materialize results into draft records.
- [ ] Capture interaction telemetry and feedback toggles so designers can refine prompt engineering after launch.
- [ ] Update onboarding guides with examples demonstrating how short prompts expand into structured campaign assets.

## Notes
- Reuse the Ollama-backed AI abstraction and ensure responses are clearly marked as drafts pending facilitator review.
- Provide quick actions that reference existing groups to keep generated content grounded in the current campaign context.
- Pair with UX to ensure the companion does not obstruct existing CRUD forms on smaller screens.

## Log
- 2025-11-26 09:25 UTC – Documented request for AI assistance within world and region management to simplify onboarding.

# Task 13 – AI Services

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 5 (Turn Scheduler API), Task 6 (Session Workspace), Task 11 (Realtime Collaboration)
**Related Backlog Items:** Implement AI services (Ollama Gemma3) for NPC and DM takeover workflows.

## Objective
Wire the campaign platform into the planned Ollama Gemma3 service so AI support can cover turn summaries, NPC portrayals, and temporary DM delegation when a human facilitator steps away.

## Deliverables
- `ai_requests` log with metadata, response payloads, and status tracking for every AI hand-off
- AI content service that talks to Ollama Gemma3 for turn summaries, NPC dialogue, and region delegation directives
- Group dashboard controls for requesting an AI DM with optional briefing plus surfaced AI directives
- Session workspace panel for NPC chat plus continued AI fallback in the turn processor
- Configuration, documentation, and tests covering the new flows

## Implementation Checklist
- [x] Add `ai_requests` table, model, factory, and relationships on regions and sessions
- [x] Ship `AiContentService` with Ollama chat integration, failure handling, and prompt helpers
- [x] Enhance turn processing to persist AI request records and consume Gemma3 responses when narrating
- [x] Add region AI delegation endpoint, UI controls, and policy gate, storing the generated directive on the region
- [x] Expose an API endpoint for NPC dialogue, render the NPC guide panel in the session workspace, and surface history
- [x] Update `.env` defaults, README, task plan, and progress log with AI configuration details and milestone notes

## Log
- 2025-10-19 08:40 UTC – Scoped AI request persistence, Ollama prompts, and UI hooks for delegation + NPC chat.
- 2025-10-19 10:20 UTC – Delivered Gemma3 integration, region delegation UX, NPC guide, tests, and documentation updates.

# Task Plan

## Backlog
- [x] Create group management (create/join/roles) and policies.
- [x] Establish milestone demo flow automation that runs at a human reading pace with verbose console narration to showcase completed work for each milestone.
- [x] Develop turn scheduler service and API (process turn, AI fallback).
- [x] Integrate Laravel Reverb for realtime initiative, chat, map tokens.
- [x] Build task board with turn-based due dates and Kanban UI.
- [x] Implement AI services (Ollama Gemma3) for NPC and DM takeover workflows.
- [x] Add search/filter infrastructure across entities, tasks, notes.
- [x] Implement exports (Markdown/PDF) and session recording storage.
- [x] Finalize localization, accessibility, theming, and docs.

## In Progress
- _None_

## Completed
- Initial architectural plan updated for multi-group, turn-based world.
- Task 1 – Project Bootstrap (Laravel + Inertia React scaffolding, tooling)
- Task 2 – Authentication Foundations (Sanctum controllers, Inertia auth UI)
- Task 3 – Group & World Foundations (group onboarding, world CRUD prep) – baseline groups/regions CRUD and scheduler stub ready for QA
- Task 4 – Campaign Management Foundations (campaign CRUD, invitations, role assignments)
- Task 5 – Turn Scheduler API (region turn processing, AI fallback summaries)
- Task 6 – Session Workspace (session scheduling, collaborative notes/dice, initiative tracker)
- Task 7 – Group Management (join codes, membership admin UI, policies)
- Task 8 – Worlds & Regions CRUD (world hierarchies, DM pacing defaults)
- Task 9 – Modular Tile Maps (tile templates, map editor, axial tile CRUD)
- Task 10 – Milestone Demo Flow Automation (`demo:milestones` pacing command, docs, and tests)
- Task 11 – Realtime Collaboration (Laravel Reverb broadcasting with Echo-powered session and map syncing)
- Task 12 – Task Board Workflow (campaign Kanban lanes, turn due dates, assignments, and ordering controls)
- Task 13 – AI Services (Ollama Gemma3 region delegation, NPC guide, and turn narration)
- Task 14 – Global Search (campaign/session/task/note discovery with scoped filters)
- Task 15 – Session Exports & Recording Vault (Markdown/PDF exports, recording storage UX)
- Task 16 – Accessibility, Localization & Theming Polish (user preference middleware, localized UI shell, accessibility pass)
- Task 17 – Lore Codex (campaign entity CRUD, codex pages, dashboard integration)
- Task 18 – Lore Tags & Quick Reference (tagging, filters, codex summary highlights)
- Task 19 – Quest Log & Progress Tracking (quest CRUD, progress updates, dashboard + search integration)
- Task 20 – Campaign Invitation Acceptance (shareable invitation links, acceptance UI, policy enforcement)
- Task 21 – Session RSVP & Attendance Roster (session attendance tracking, RSVP UI, export integration)
- Task 22 – Session Chronicle & Recap Log (session recap journal, workspace UI, export integration)
- Task 23 – Session Rewards & Loot Ledger (reward tracking ledger, workspace UI, export integration)
- Task 24 – Map Fog of War Controls (tile hiding, reveal toggles, persistence, and tests)
- Task 25 – Map Tokens & Encounter Setup (token persistence, realtime sync, GM visibility tools)
- Task 26 – Token Initiative Tracking (initiative badges, status notes, and editor controls on map tokens)
- Task 27 – Token Layer Priority Controls (z-index layering controls for tokens, UI badges, and coverage)
- Task 28 – Token Factions & Filters (faction badges, GM filters, and coverage for encounter clarity)
- Task 29 – Token Vitality Tracking (hit point, max, and temporary health badges with realtime syncing)
- Task 30 – Token Condition Presets (structured condition toggles, realtime badges, and validation)
- Task 31 – Token Condition Timers (preset duration inputs, realtime syncing, and badge readouts)
- Task 32 – Token Condition Countdown Automation (auto-decrement timers on region turns, clearing expired conditions and broadcasting updates)
- Task 33 – Token Condition Expiration Alerts (broadcast alert payloads and realtime UI notices when presets lapse)

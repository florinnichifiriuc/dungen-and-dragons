# Task Plan

## Backlog
- [x] Create group management (create/join/roles) and policies.
- [x] Establish milestone demo flow automation that runs at a human reading pace with verbose console narration to showcase completed work for each milestone.
- [ ] Implement campaign CRUD with group invitations and role assignments.
- [x] Develop turn scheduler service and API (process turn, AI fallback).
- [x] Integrate Laravel Reverb for realtime initiative, chat, map tokens.
- [x] Build task board with turn-based due dates and Kanban UI.
- [x] Implement AI services (Ollama Gemma3) for NPC and DM takeover workflows.
- [ ] Add search/filter infrastructure across entities, tasks, notes.
- [ ] Implement exports (Markdown/PDF) and session recording storage.
- [ ] Finalize localization, accessibility, theming, and docs.

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

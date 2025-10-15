# Task 23 – Session Rewards & Loot Ledger

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 6 (Session Workspace), Task 22 (Session Recaps)

## Objective
Give parties a dedicated ledger inside each session workspace to record loot, experience awards, and story boons so the table can keep track of who earned what without leaving the chronicle.

## Deliverables
- Database table and model for session rewards tied to campaigns, sessions, and recorders.
- Validation + controller endpoints that let any member log rewards while allowing managers to moderate removals.
- Inertia UI for capturing rewards and browsing the running ledger with type badges and notes.
- Export updates (Markdown/PDF) so logged rewards appear alongside the recap, attendance, and dice data.
- Automated feature coverage for reward logging, moderation, and export visibility.

## Implementation Checklist
- [x] Add `session_rewards` migration, model, factory, and request validation.
- [x] Create controller actions + routes for storing and deleting rewards with policy hooks.
- [x] Extend the session workspace UI with a reward form, ledger timeline, and permission-aware controls.
- [x] Surface rewards through the export service, markdown generator, and PDF template.
- [x] Cover reward workflows with Pest feature specs and update progress documentation.

## Log
- 2025-10-24 15:10 UTC – Planned reward ledger scope, data fields, and moderation rules.
- 2025-10-24 17:55 UTC – Delivered reward persistence, UI ledger, export integration, tests, and docs updates.

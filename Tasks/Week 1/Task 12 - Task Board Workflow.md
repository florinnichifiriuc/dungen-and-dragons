# Task 12 – Task Board Workflow

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 4 (Campaign Management Foundations)
**Related Backlog Items:** Build task board with turn-based due dates and Kanban UI.

## Objective
Deliver a Kanban-style campaign task board that anchors work to specific turns, supports assignments, and keeps column ordering synchronized for distributed parties.

## Deliverables
- Campaign task data model, policies, and CRUD routes with turn-based due dates and assignments
- Inertia Task Board page with Kanban columns, move controls, and turn pacing badges
- Authorization so managers and assignees can collaborate without over-exposing controls
- Pest feature coverage for creation, progression, and reordering flows
- Documentation and progress trackers updated to reflect the new workflow

## Implementation Checklist
- [x] Create `campaign_tasks` table with turn cadence, assignment, and ordering fields
- [x] Add `CampaignTask` model, policy, factories, and register relationships on Campaign
- [x] Ship controller + form requests for listing, creating, updating, and reordering tasks
- [x] Build Task Board Inertia page with Kanban layout, assignee and due turn controls, and move buttons
- [x] Cover creation, update, and reorder behavior with Pest feature tests
- [x] Update README, task plan, and logs with the completed task board workflow

## Log
- 2025-10-18 12:40 UTC – Scoped data model, policies, and UI requirements for campaign Kanban board.
- 2025-10-18 13:10 UTC – Delivered task board UI, backend endpoints, tests, and documentation updates.
- 2025-11-24 19:30 UTC – Added AI steward drafting panel, layout refresh, and fallback coverage for campaign task ideas.

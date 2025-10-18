# Task 19 – Quest Log & Progress Tracking

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 4 (Campaign Management), Task 6 (Session Workspace), Task 12 (Task Board Workflow)
**Related Backlog Items:** Expand campaign narrative tooling with quest tracking and progress journals

## Objective
Give campaign leads a dedicated quest log to track story arcs, tie them to regions, and capture progress reports from any party member. The feature should mirror the existing Inertia workflows, respect campaign policies, and surface quick links from the campaign dashboard.

## Deliverables
- Quest and quest update migrations/models tied to campaigns, regions, and creators
- Authorization, validation, and controllers for quest CRUD plus progress updates
- Inertia pages for quest index/show/create/edit with progress journaling UI
- Global search integration and campaign dashboard summaries for active quests
- Pest feature coverage around quest lifecycle, journaling, and filtering rules
- Documentation and project trackers refreshed to reflect the new quest log

## Implementation Checklist
- [x] Add quest + progress update tables, models, factories, and policies
- [x] Implement controllers, validation requests, and routes for quests & updates
- [x] Build Inertia screens (index/show/create/edit) with status/priority filters and progress feed
- [x] Extend global search & campaign dashboard data to include quest insights
- [x] Cover quest CRUD, progress updates, and filtering permissions with Pest
- [x] Update README/task plan/progress log with quest log availability

## Log
- 2025-10-23 08:30 UTC – Planned quest log schema, policies, and UI composition informed by existing task board patterns.
- 2025-10-23 11:45 UTC – Implemented migrations, controllers, Inertia pages, tests, and docs for the campaign quest log.
- 2025-11-24 19:30 UTC – Added AI quest mentor panel, preset quest sparks, and fallback endpoint coverage.

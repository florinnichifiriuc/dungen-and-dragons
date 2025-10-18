# Task 86 – Bug Reporting Intake Experience

**Status:** Completed
**Owner:** Product & Engineering
**Dependencies:** Task 65, Task 71

## Intent
Deliver an in-app bug reporting workflow for facilitators and players that captures reproduction details, environment metadata, and AI prompt context so issues can be triaged quickly during the launch window.

## Subtasks
- [x] Design and implement Inertia forms for bug submission across facilitator dashboards and player recap views.
- [x] Capture logs, browser metadata, and recent AI interactions (via mocks in tests) alongside user-provided notes.
- [x] Add acknowledgement and tracking references so users can follow bug status post-submission.

## Notes
- Ensure accessibility and localization compliance to align with prior transparency work.
- Submissions must emit analytics events and integrate with the new admin triage platform.

## Log
- 2025-11-22 10:40 UTC – Scoped player and facilitator intake needs for launch bug reporting.
- 2025-11-22 17:20 UTC – Delivered facilitator and share-link bug intake pages, reusable form component, and localization updates; analytics + tracking follow-up pending.
- 2025-11-23 17:10 UTC – Added player-facing reference copy with clipboard actions and ensured submissions link to real-time status pages.

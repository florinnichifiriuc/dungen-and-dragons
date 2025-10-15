# Task 21 – Session RSVP & Attendance Roster

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 4 (Campaign Management), Task 6 (Session Workspace)

## Objective
Give every campaign session an attendance roster so party members can RSVP, share arrival notes, and view who plans to join. The feature should keep the session workspace collaborative, provide quick counts for planning, and surface the roster in exports for record keeping.

## Deliverables
- Database support for session attendance responses tied to campaign members.
- Policy-backed endpoints to create/update and clear RSVP responses.
- Inertia UI on the session workspace showing RSVP buttons, optional notes, and response lists.
- Export updates that include attendance counts and individual responses.
- Feature tests covering happy paths and unauthorized scenarios.

## Implementation Checklist
- [x] Create `session_attendances` table, model, and factory.
- [x] Authorize attendance responses via session policy and register routes/controllers.
- [x] Extend `SessionController` and Inertia page with attendance summary and RSVP form.
- [x] Update session exports with attendance context.
- [x] Add Pest feature coverage for creating and clearing RSVPs.
- [x] Refresh documentation, task plan, and progress logs.

## Log
- 2025-10-23 17:40 UTC – Planned attendance roster scope, UX, and data model.
- 2025-10-23 19:05 UTC – Delivered RSVP endpoints, UI, export enhancements, and automated tests.

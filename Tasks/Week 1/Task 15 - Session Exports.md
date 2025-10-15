# Task 15 – Session Exports & Recording Vault

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 6 (Session Workspace), Task 10 (Milestone Demo Flow)
**Related Backlog Items:** Implement exports (Markdown/PDF) and session recording storage.

## Intent
Deliver archival tooling for campaign sessions so facilitators can preserve complete recaps, share transcripts, and stash audiovisual recordings. Ship Markdown/PDF exports that respect visibility rules, and add first-party storage for uploaded recordings alongside existing external links.

## Subtasks
- [x] Outline export data contract, visibility behavior, and storage UX updates.
- [x] Implement backend service + routes that generate Markdown and PDF exports for authorized viewers.
- [x] Extend Inertia session workspace UI with export actions and recording management controls.
- [x] Add recording upload/removal endpoints with durable storage and validation.
- [x] Cover exports and recording flows with Pest feature tests.
- [x] Update progress artifacts after delivery.

## Notes
- Exports must stay in UTC and include agenda, summary, notes (filtered), dice rolls, initiative order, and AI dialogue highlights.
- GM-only notes only appear for managers; player exports omit them automatically.
- Store recordings on the `public` disk under `session-recordings/` using generated filenames; purge prior uploads on replacement.

## Log
- 2025-10-20 16:55 UTC – Finalized export payload contract and recording UX, aligning Inertia controls with authorization rules.
- 2025-10-20 17:35 UTC – Implemented Markdown/PDF exports, recording vault endpoints, UI hooks, and automated coverage.

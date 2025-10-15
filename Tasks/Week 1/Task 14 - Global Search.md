# Task 14 – Global Search & Filters

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 4, Task 6, Task 12
**Related Backlog Items:** Add search/filter infrastructure across entities, tasks, notes.

## Intent
Deliver a unified search experience so storytellers can quickly find campaigns, sessions, tasks, and notes they have access to.
Search must respect campaign membership and role assignments, expose scoped filters, and surface enough context for users to
decide where to jump next.

## Subtasks
- [x] Draft authorization-aware queries spanning campaigns, sessions, notes, and tasks.
- [x] Build a dedicated `GlobalSearchService` with scope normalization and GM-note protections.
- [x] Expose a new Inertia search page with scope filters, contextual metadata, and navigation links.
- [x] Wire up form request validation and routing within the authenticated shell.
- [x] Cover service behavior for accessible resources and GM-only visibility via Pest tests.
- [x] Update progress artifacts to reflect delivery.

## Notes
- Scopes default to all record types but persist selections through Inertia state to encourage iterative filtering.
- GM-only notes only display to campaign managers (owners/DMs/assigned GMs) so secrets stay hidden from players.
- Result cards include direct navigation buttons to preserve pacing when moving between sessions and task boards mid-play.

## Log
- 2025-10-19 18:45 UTC – Planned global search aggregation strategy, identified visibility constraints, and outlined service API.
- 2025-10-20 09:15 UTC – Implemented search service, controller, Inertia UI, and Pest coverage; refreshed docs and logs.

# Task 54 – Offline Sync Reliability

**Status:** Planned
**Owner:** Engineering & Mobile UX
**Dependencies:** Tasks 40, 46, 53

## Intent
Improve offline acknowledgement flows so players can safely review and respond to timers while disconnected, with reliable reconciliation once connectivity returns. The experience should gracefully indicate pending sync states and prevent duplicate or lost acknowledgements.

## Subtasks
- [ ] Audit current offline caching for timers, acknowledgements, and summaries; document gaps.
- [ ] Design offline-first UI states (pending, synced, conflict) for desktop/mobile recap views.
- [ ] Implement background sync workers using service workers/Inertia adapters with exponential retry.
- [ ] Add conflict resolution messaging when offline actions clash with live updates.
- [ ] Expand tests covering offline/online transitions, including browser storage fallbacks.
- [ ] Instrument analytics for offline usage, retries, and failures to inform future improvements.

## Notes
- Ensure data remains encrypted at rest in IndexedDB/local storage per privacy requirements.
- Consider gentle narrative copy for offline banners to keep immersion intact (e.g., "Your sending stone searches for a signal...").
- Coordinate with Task 57 to respect consent settings even when syncing later.

## Log
- 2025-11-05 16:57 UTC – Elevated after focus group reported lost acknowledgements during low-connectivity session.

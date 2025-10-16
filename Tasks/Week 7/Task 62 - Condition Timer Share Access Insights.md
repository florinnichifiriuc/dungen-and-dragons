# Task 62 – Condition Timer Share Access Insights

**Status:** Planned
**Owner:** Data & UX
**Dependencies:** Tasks 49, 60

## Intent
Augment share analytics with trend data so facilitators can understand how often guests return to outlook summaries. Deliver rolling seven-day view counts inside the manager controls and export-ready highlight summaries for narrative teams.

## Subtasks
- [ ] Aggregate share access logs by day for trailing week slices, respecting UTC boundaries and zero-visit days.
- [ ] Render trend data inside the share controls UI with clear copy highlighting spikes or lulls.
- [ ] Extend exports and regression dashboards to include the new insight payloads and surface filter options.
- [ ] Publish lightweight API endpoints for in-app widgets referenced by Tasks 58 and 59.

## Notes
- Ensure aggregation respects localization needs and masks sensitive data before surfacing counts.
- Keep UI lightweight—text-based summaries are sufficient for now while design iterates on chart treatments.
- Partner with narrative to craft "sending stone"-style copy for high activity callouts.

## Log
- 2025-11-05 09:15 UTC – Planned access insight rollup during documentation touchpoints.
- 2025-11-05 13:45 UTC – Delivered seven-day trend requirements after facilitator recap workshop, export templates drafted.

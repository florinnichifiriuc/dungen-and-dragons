# Task 62 – Condition Timer Share Access Insights

**Status:** In Progress
**Owner:** Data & UX
**Dependencies:** Tasks 49, 60

## Intent
Augment share analytics with trend data so facilitators can understand how often guests return to outlook summaries. Deliver rolling seven-day view counts, extension actor callouts, and preset bundle adoption summaries inside the manager controls plus export-ready highlights for narrative teams.

## Subtasks
- [ ] Aggregate share access logs by day for trailing week slices, respecting UTC boundaries and zero-visit days.
- [ ] Correlate access spikes with preset bundle selections and extension actors, surfacing anonymized attributions in the dashboard.
- [ ] Render trend data inside the share controls UI with clear copy highlighting spikes, lulls, and extension-triggered resurfaces.
- [ ] Extend exports and regression dashboards to include the new insight payloads and surface filter options.
- [ ] Publish lightweight API endpoints for in-app widgets referenced by Tasks 58 (mentor briefings) and 59 (QA dashboards).

## Notes
- Ensure aggregation respects localization needs and masks sensitive data before surfacing counts.
- Keep UI lightweight—text-based summaries are sufficient for now while design iterates on chart treatments.
- Partner with narrative to craft "sending stone"-style copy for high activity callouts.
- Work with compliance to confirm extension actor labeling meets consent auditors’ readability expectations.

## Log
- 2025-11-05 09:15 UTC – Planned access insight rollup during documentation touchpoints.
- 2025-11-05 13:45 UTC – Delivered seven-day trend requirements after facilitator recap workshop, export templates drafted.
- 2025-11-07 11:00 UTC – Shipped seven-night access trend widget with copy, export surfacing, and QA hooks for regression dashboards.
- 2025-11-12 16:25 UTC – Updated brief post focus group sync to track extension actors, preset adoption, and mentor widget API needs.

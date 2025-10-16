# Task 52 – Facilitator Insights Dashboard

**Status:** Completed
**Owner:** Engineering & Analytics
**Dependencies:** Tasks 47, 48, 51

## Intent
Empower facilitators with an analytics dashboard that surfaces condition timer health, acknowledgement funnels, and at-risk players across campaigns. The dashboard should highlight trends, anomalies, and recommended follow-ups while reinforcing the immersive aesthetic.

## Subtasks
- [x] Define metrics (e.g., timers nearing expiration without acknowledgement, repeat offenders, average response time).
- [x] Design dashboard wireframes with filtering by campaign, faction, and urgency tier.
- [x] Build projection queries leveraging existing chronicles and acknowledgement logs, with caching strategy.
- [x] Implement Inertia dashboard components with drill-down navigation to timers, sessions, and share links.
- [x] Add role-based access policies ensuring only authorized facilitators view analytics.
- [x] Instrument telemetry for dashboard usage and integrate with research KPIs.

## Notes
- Provide export hooks so facilitators can share insights during DM standups.
- Align color language with existing urgency gradients to maintain continuity.
- Consider "story seed" callouts from narrative team to translate data spikes into in-world cues.

## Log
- 2025-11-05 16:52 UTC – Added after facilitators requested clearer visibility into acknowledgement gaps.
- 2025-11-07 09:20 UTC – Finalized insights service metrics and telemetry hooks using acknowledgement/adjustment data.
- 2025-11-07 12:10 UTC – Shipped facilitator dashboard UI with filters, Markdown exports, and policy-protected access.

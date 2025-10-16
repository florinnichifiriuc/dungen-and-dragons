# Task 40 – Condition Timer Mobile Recap Widgets

**Status:** Planned
**Owner:** UI/Frontend
**Dependencies:** Task 39

## Intent
Deliver a mobile-first recap experience that surfaces player-safe timer summaries within the Inertia session workspace, ensuring tables using tablets or phones stay informed without overwhelming the main dashboard.

## Subtasks
- [ ] Prototype responsive panels or bottom sheets that integrate with existing session layout patterns and Task 42 wireframes.
- [ ] Implement offline-friendly caching so the view remains available during connectivity hiccups.
- [ ] Sync urgency iconography and typography with existing Tailwind design tokens.
- [ ] Validate accessibility (focus order, screen reader labels) for mobile interactions.
- [ ] Run Lighthouse/performance audits against baseline budgets and document results.
- [ ] Integrate analytics/tracking events defined in Task 44 without impacting load times.

## Notes
- Coordinate with localization to keep copy expandable without breaking layouts.
- Ensure the component degrades gracefully on narrow widths and landscape orientations.
- Consider hooking into recap exports for long-term archiving.
- Keep offline cache payloads consistent with projection invalidation rules from Task 39.
- Provide QA scenarios for gesture interactions introduced in Task 42.

## Log
- 2025-10-28 09:40 UTC – Added after strategic sync to phase player transparency rollout.

# Task 40 – Condition Timer Mobile Recap Widgets

**Status:** Completed
**Owner:** Product & Narrative Design
**Dependencies:** Task 39

## Intent
Deliver a lightweight, mobile-first condition recap experience that keeps players informed during travel sessions without overloading limited screen real estate or exposing GM-only insights.

## Subtasks
- [x] Audit existing session/shareable panels and identify opportunities for condensed breakpoints and cached hydration.
- [x] Implement reusable summary cache hook shared between session workspace and shareable recap view for offline resilience.
- [x] Build a mobile recap widget that surfaces the most urgent conditions, includes offline indicators, and deep-links to the full summary.
- [x] Ensure responsive styling across breakpoints and verify minimal bundle impact to respect mobile performance budgets.
- [x] Update task trackers and progress log once the widget ships and integrates with realtime broadcasts.

## Notes
- Widget should prioritize critical timers but still communicate calm states to reinforce preparedness.
- Offline banner doubles as reassurance that cached data is being served; revisit copy after telemetry from Task 44.
- Future iteration may add haptic cues when timers enter critical windows for mobile app shells.
- Monitor bundle diff to keep under the agreed Lighthouse budget (<10 KB gzip increase for this feature).

## Log
- 2025-10-30 16:10 UTC – Scoped widget requirements, confirmed reuse of projection payloads, and drafted responsive layout sketches.
- 2025-10-30 18:40 UTC – Implemented shared cache hook, mobile recap widget, and shareable page/session workspace integrations with offline indicators.

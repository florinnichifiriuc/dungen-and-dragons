# Task 50 – Condition Timer Escalation Notifications

**Status:** Completed
**Owner:** Engineering & UI
**Dependencies:** Tasks 39, 40, 47

## Intent
Deliver configurable escalation notifications that alert facilitators and players when condition timers cross urgency thresholds, without overwhelming them during live play or async catch-up. Notifications should respect personal preferences, queue capacity, and accessibility guidelines while maintaining the D&D-inspired tone established in earlier work.

## Subtasks
- [x] Define notification journeys (urgent, cautionary, cleared) and map them to timer states plus acknowledgement signals.
- [x] Extend user preference settings for push/in-app/email notifications, including quiet hours and digest routing.
- [x] Implement queue-friendly notification dispatch leveraging existing broadcast events with autoscaling benchmarks.
- [x] Add Inertia UI for notification center, badge counts, and contextual quick actions.
- [x] Write Pest coverage for preference enforcement, throttling, and UI rendering across desktop/mobile.
- [x] Instrument telemetry for send outcomes, opt-outs, and fallback channels.

## Notes
- Coordinate with DevOps on autoscaling adjustments before enabling production dispatch.
- Ensure notifications link back into the appropriate timer context with signed URLs for guest/DM flows.
- Tone should feel like an in-world sending stone ping—brief, flavorful, and respectful of urgency.

## Log
- 2025-11-05 16:45 UTC – Drafted scope during cross-discipline sync after reviewing focus group appetite for proactive nudges.
- 2025-11-05 21:10 UTC – Delivered escalation pipeline with preference-aware dispatch, quiet-hour suppression, telemetry hooks, and notification center UI ready for beta feedback.
- 2025-11-24 12:40 UTC – Performed final verification of notification journeys, telemetry, and preferences; task formally completed.

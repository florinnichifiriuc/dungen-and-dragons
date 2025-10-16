# Task 46 – Condition Timer Acknowledgement Trails

## Intent
Extend the player-facing condition timer transparency by letting players mark the conditions they have reviewed, surfacing receipt counts to facilitators, and streaming acknowledgement state changes to all connected clients.

## Background & Alignment
- Builds directly on Tasks 38–45 (batch adjust, summaries, mobile widgets, documentation, copy, telemetry).
- Supports the transparency initiative’s focus on trust by giving DMs feedback about which players have seen urgent effects.
- Must respect privacy guardrails: acknowledgements only reference condition keys already exposed to the viewer.

## Success Criteria
- Players can acknowledge individual condition entries from the summary panel without leaving the session or group summary views.
- Facilitators (owners/DMs) can see aggregate acknowledgement counts per condition.
- Realtime updates propagate acknowledgements to other viewers without requiring a full page reload.
- Analytics event `timer_summary.acknowledged` records actor, group, condition key, and urgency context when an acknowledgement is logged.
- Automated feature coverage verifies acknowledgement storage rules and summary hydration.

## Proposed Implementation
1. **Persistence** – Add a `condition_timer_acknowledgements` table keyed by group, token, condition key, and user with timestamps for acknowledgement and the summary generation timestamp they correspond to.
2. **Domain Layer** – Introduce a `ConditionTimerAcknowledgement` model and `ConditionTimerAcknowledgementService` responsible for hydrating summaries with acknowledgement metadata and recording updates.
3. **API & Validation** – Provide a Form Request + controller endpoint to accept acknowledgement submissions, verifying the token belongs to the group and the condition is still active.
4. **Realtime Broadcast** – Emit a dedicated broadcast event when acknowledgements are recorded so other viewers update counts immediately.
5. **UI** – Enhance the player summary panel (and supporting hooks) with acknowledgement toggles, optimistic UX, and DM aggregate badges while preserving the mobile recap experience.
6. **Analytics** – Hook into the acknowledgement controller to log the analytics event with timer urgency context.
7. **Tests** – Feature tests covering acknowledgement storage, duplicate avoidance, and summary hydration plus unit coverage for the acknowledgement service.

## Milestones
- [x] Schema & model scaffolding
- [x] Service + analytics + broadcast wiring
- [x] Controller endpoint & validation
- [x] React acknowledgement controls + realtime listeners
- [x] Feature & unit tests
- [x] Documentation updates

## Status
Completed

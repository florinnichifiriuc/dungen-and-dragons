# Task 47 – Condition Timer Adjustment Chronicle

## Intent
Give facilitators and players a trustworthy record of how condition timers evolve by persisting every adjustment, presenting a privacy-aware timeline alongside the player summary, and piping the chronicle into session exports.

## Background & Alignment
- Builds directly on Tasks 38–46 that introduced timer adjustments, summaries, acknowledgements, and telemetry.
- Supports transparency goals by showing when timers were extended, reduced, or cleared without exposing GM-only secrets to players.
- Must respect privacy rules for obscured tokens and continue logging analytics to quantify usage.

## Success Criteria
- Timer adjustments (manual batches, token edits, automated turn ticks) persist to a chronicle table with reason, actor, and delta metadata.
- Player summary conditions show a condensed adjustment timeline; facilitators receive enriched actor/context detail without leaking hidden info to players.
- Session exports include a condition timer chronicle section filtered by viewer permissions.
- Analytics event `timer_summary.adjusted` fires with change context for manual and automated adjustments.
- Feature tests cover chronicle persistence, privacy filters, and summary hydration.

## Proposed Implementation
1. **Persistence Layer** – Create a `condition_timer_adjustments` table, model, and factory capturing group/token/condition, previous & new rounds, delta, reason, actor role, context, and recorded timestamp.
2. **Chronicle Service** – Add a `ConditionTimerChronicleService` with helpers to record adjustments from batch controllers, token CRUD, and the turn scheduler, log analytics, and fetch timelines with privacy filters.
3. **Projection & Hydration** – Extend the summary projector to append sanitized timelines to each condition; update controllers to enrich timelines per viewer and surface them in exports.
4. **UI** – Update the player summary panel to render the timeline with relative timestamps and facilitator-only detail while keeping mobile recap lightweight.
5. **Docs & Telemetry** – Update roadmap/task docs plus PROGRESS_LOG, and ensure analytics captures `timer_summary.adjusted` with reason/delta context.
6. **Tests** – Write feature coverage to assert recorded adjustments and timeline visibility differences between players and facilitators.

## Milestones
- [x] Schema, model, and factory scaffolding
- [x] Chronicle service with recording + analytics hooks
- [x] Projector, controller, and export integration
- [x] React timeline UI & accessibility polish
- [x] Feature and unit tests
- [x] Documentation updates

## Status
Completed

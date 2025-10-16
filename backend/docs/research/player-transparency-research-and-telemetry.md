# Player Transparency Research & Telemetry Plan

This document defines the qualitative and quantitative feedback loops needed to evaluate player-visible condition summaries. Follow the steps below to instrument analytics, run research sprints, and report insights to the broader team.

## Research Brief
- **Goals:** Measure trust, immersion, and tactical confidence after exposing condition timers to players. Identify friction points that break secrecy expectations or overload the UI.
- **Target Cohorts:**
  - Existing campaign groups who already use condition timers (GM + 2–5 players).
  - New player cohorts onboarding via community invite links.
  - Internal QA cells simulating mixed-experience parties.
- **Method Mix:**
  - Moderated playtest (60 min) observing reactions to timer adjustments and summary panels.
  - Async diary study over two sessions capturing perceived usefulness and confusion moments.
  - Post-session survey (Likert + open-ended) to quantify trust/immersion shifts.

## Interview & Playtest Script
1. Warm-up: Ask players how they currently track status effects and what frustrates them.
2. Scenario walkthrough: Present the dashboard, summaries, and mobile widget while participants narrate actions.
3. Probe trust moments: “Did knowing this timer change your decision?” “Did anything feel spoiler-heavy?”
4. Conflict simulation: Trigger a batch adjustment conflict; observe reactions to error copy and telemetry prompts.
5. Wrap-up: Collect ratings for clarity, immersion, and perceived fairness (1–7 scale) plus open feedback.

## Survey Outline
| Theme | Question | Scale |
| --- | --- | --- |
| Trust | “I trust the condition summaries to reflect what my character senses.” | 1 (Strongly Disagree) – 7 (Strongly Agree) |
| Immersion | “The summaries enhanced my sense of being in the world.” | 1 – 7 |
| Overload | “I felt overwhelmed by the amount of status information presented.” | 1 – 7 (reverse scored) |
| Agency | “Knowing these timers helped me make better tactical choices.” | 1 – 7 |
| Spoilers | Open: “Describe any moment where the summary revealed too much.” | Free text |
| Quality | “How satisfied are you with the narrative tone of the summaries?” | 1 – 5 (Poor → Excellent) |

## Analytics Instrumentation
| Event Key | Trigger | Payload | Notes |
| --- | --- | --- | --- |
| `timer_summary.viewed` | Projection payload renders in dashboard/summary/mobile widget. | `group_id`, `user_role`, `source`, `entries_count`, `staleness_ms`. | Fire on initial load and after refresh; tie into projector guide recommendations. |
| `timer_summary.refreshed` | Projector completes refresh. | `group_id`, `trigger`, `entries_count`, `duration_ms`. | Queue via listener; mark `trigger` as `token_mutation`, `batch_adjustment`, `turn_process`, `manual`. |
| `timer_summary.dismissed` | Player closes summary panel or hides widget. | `group_id`, `source`, `reason` (`temporary`, `permanent`, `obscured`). | Use to prioritize UX polish. |
| `timer_summary.conflict` | Batch adjustment fails. | `group_id`, `conflict_type`, `selection_count`, `resolved` (bool). | Sync with conflict banners for follow-up QA. |
| `timer_summary.copy_variant` | Narrative snippet rendered. | `condition_key`, `urgency`, `variant_id`. | Supports A/B testing of copy deck (Task 43). |

### Implementation Notes
- Instrument analytics through Laravel events + queued listeners (`AnalyticsEventDispatched`).
- Ensure payloads exclude GM-only lore; rely on redacted projector output.
- Respect existing privacy settings—skip logging for campaigns flagged “private telemetry”.
- Batch events client-side using the existing Inertia analytics helper to minimize network chatter.

## Success Metrics & Reporting
- **Activation:** ≥75% of active campaigns trigger `timer_summary.viewed` within first session post-launch.
- **Trust Delta:** Average trust score ≥5.5/7; <10% respondents report spoilers.
- **Engagement:** Repeat views (same user within a session) average ≥3 when urgent timers present.
- **Conflict Resolution:** ≥90% of conflicts resolved within two attempts; track via `resolved=true` ratio.
- **Reporting Cadence:** Weekly dashboard updates posted to the leadership Notion doc every Monday (UTC). Include charts for trust, immersion, and event counts, plus top qualitative quotes.

## QA & Edge Cases
- Validate analytics events in staging using browser dev tools (network tab) and server logs.
- Simulate private campaigns to confirm opt-out logic removes telemetry calls.
- Verify dismiss events respect offline mode (queue until reconnect).
- Coordinate with QA to add manual test cases covering:
  - Timer view in GM vs Player role.
  - Conflict handling with telemetry assertions.
  - Mobile offline recap interactions.

## Beta Feedback Plan
1. Recruit 3–4 campaigns for a two-week beta; schedule kickoff call and weekly debriefs.
2. Provide survey links within 12 hours of each beta session; send reminders at 48 hours.
3. Log qualitative findings in `PROGRESS_LOG.md` summary section for transparency.
4. Run follow-up adjustments (copy, UX) based on trust/immersion thresholds before general release.

## Integration Checklist
- [x] Deploy analytics listeners alongside projector refresh flow.
- [ ] Seed survey templates into Typeform (or Qualtrics) with placeholder tokens ready.
- [ ] Update onboarding docs to reference research plan and analytics events (see README update).
- [ ] Coordinate with Product Ops to add questions to the bi-weekly player satisfaction pulse.

# PO & Focus Group Sync – Transparency Next Steps
- **Date:** 2025-11-12 15:00 UTC
- **Facilitator:** Product Owner
- **Attendees & Roles:**
  - Product Owner – backlog stewardship, consent policy alignment
  - Engineering Lead – implementation planning and timeline validation
  - QA Lead – coverage strategy and beta readiness
  - Narrative & AI Lead – mentor briefings tone and localization
  - D&D Focus Group Representatives – table-play feedback, session reports

## Agenda
1. Review outcomes from Tasks 57–58 and identify open acceptance items.
2. Gather tabletop feedback on share link consent flows and mentor briefings.
3. Prioritize improvements for Tasks 59–63 before beta graduation.
4. Confirm ownership and sequencing for the next two-week sprint.

## Highlights & Feedback
- Share link consent toggles were praised for clarity, but facilitators want **preset bundles** ("one-shot preview", "extended ally access") to avoid manual reconfiguration during live play.
- Focus group players reported anxiety when mentor briefings surfaced while they were offline; they requested **asynchronous catch-up prompts** in the recap feed instead of surprise push alerts.
- QA reiterated that the synthetic monitoring spikes during load testing did not mirror real party cadence; they need **scenario scripts** based on actual encounter pacing from the focus group logs.
- Consent auditors flagged that guest acknowledgements should call out **who unlocked a link extension** to keep the audit trail readable.
- D&D reps asked for **ritual-style narrative variants** for long-duration conditions so mentor briefings feel distinct from combat scenarios.

## Decisions
- Introduce share link preset bundles as part of Task 61 so facilitators can rapidly select expiry + visibility combinations that match party expectations.
- Task 58’s outstanding moderation queue will ship with an **AI playback digest** that summarizes any suppressed mentor briefings for facilitators.
- Task 59 will anchor the structured beta playtest using the focus group’s encounter scripts and require a sign-off form from each table.
- Task 62 will absorb telemetry work for tracking who extends or revokes share links, exposing that data in the insights dashboard.
- Task 63 will extend the guest experience polish scope to add recap-feed catch-up prompts for players who missed mentor briefings.

## Action Items
1. **Engineering Lead (2025-11-13):** Draft API contract for share link preset bundles and audit trail metadata. Document under Task 61.
2. **QA Lead (2025-11-14):** Convert focus group combat logs into repeatable load/perf scripts; attach to Task 59 and CI notes.
3. **Narrative & AI Lead (2025-11-15):** Produce ritual-style mentor copy variants and update Task 58 moderation queue acceptance criteria.
4. **Product Owner (2025-11-16):** Update Tasks 59–63 briefs to capture new acceptance criteria and telemetry expectations.
5. **Focus Group Coordinators (2025-11-18):** Deliver signed beta playtest commitment forms; archive in project drive and reference in Task 59 log.

## Risks & Follow-Ups
- **Risk:** Preset bundles could create inconsistent privacy defaults if custom overrides persist. *Mitigation:* enforce explicit confirmation modal showing final consent states before saving.
- **Risk:** Additional telemetry for extension tracking might impact analytics performance. *Mitigation:* reuse existing projection cache write pipeline with batched inserts.
- **Risk:** Catch-up prompts may spam players who intentionally muted mentor briefings. *Mitigation:* respect per-channel opt-outs and surface prompts only within recap feed, never via push notifications.

## Next Checkpoint
- Schedule follow-up on 2025-11-19 15:00 UTC to demo preset bundles, review AI moderation queue UX, and validate updated QA scripts.

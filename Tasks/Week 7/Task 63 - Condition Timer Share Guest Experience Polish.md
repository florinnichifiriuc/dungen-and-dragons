# Task 63 – Condition Timer Share Guest Experience Polish

**Status:** In Progress
**Owner:** UX & Narrative
**Dependencies:** Tasks 49, 57, 61

## Intent
Refresh the guest-facing share page with narrative framing, staleness cues, and context guidance so party members understand how current the condition outlook is when they receive the link.

## Subtasks
- [ ] Reframe hero messaging with last-updated callouts, facilitator contact hints, and a clear share etiquette reminder.
- [ ] Ensure layout adapts gracefully across mobile/desktop with recap widget ordering that prioritizes urgent timers.
- [ ] Reflect guest copy improvements inside spoiler-safe recap mode and ensure digest emails reuse the refreshed language.
- [ ] Add recap-feed catch-up prompts for mentor briefings that triggered while a guest was offline, respecting notification opt-outs.
- [ ] Run regression coverage on share recap rendering to guard against layout regressions introduced by new copy blocks and catch-up prompts.

## Notes
- Reuse existing summary metadata where possible to avoid redundant queries and keep the page lightweight.
- Provide guest copy in immersive but spoiler-safe tone, consistent with Task 43 narrative standards.
- Schedule a quick focus group review once copy is in staging to validate clarity for new players.
- Coordinate with Task 58’s moderation queue so suppressed mentor briefings appear in the catch-up digest with facilitator notes.

## Log
- 2025-11-05 09:20 UTC – Outlined guest experience improvements and messaging goals.
- 2025-11-05 13:50 UTC – Logged narrative, visual, and QA touchpoints after focus group review surfaced guidance gaps.
- 2025-11-07 11:05 UTC – Refreshed guest outlook copy with staleness cues, redaction messaging, and responsive layout tweaks.
- 2025-11-12 16:35 UTC – Extended scope per focus group sync to add mentor catch-up prompts and moderation-aligned digest states.

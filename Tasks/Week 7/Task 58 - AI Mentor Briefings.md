# Task 58 – AI Mentor Briefings

**Status:** Completed
**Owner:** Narrative & AI Engineering
**Dependencies:** Tasks 13, 39, 51

## Intent
Leverage existing AI services to generate spoiler-safe mentor briefings that explain recurring conditions, suggest countermeasures, and celebrate progress. Briefings should enhance player understanding without revealing hidden GM plans.

## Subtasks
- [x] Define briefing triggers (e.g., recurring condition on same player, unacknowledged timers, severe escalations).
- [x] Draft prompt templates with narrative guardrails and spoiler filters, including localized variants.
- [x] Implement AI request pipeline with caching, redaction of GM-only data, and human override controls.
- [x] Surface briefings within digests, notifications, and facilitator dashboards with clear labeling.
- [x] Add moderation review queue for AI outputs with approval/feedback loops plus facilitator playback digest for suppressed messages.
- [x] Cover AI error handling and fallback messaging with automated tests.

## Notes
- Provide toggles for groups that prefer purely human narration.
- Work with focus group to validate tone—mentor voice should feel like a seasoned adventurer, not a lecture.
- Ensure analytics capture uptake and satisfaction to inform future iterations.
- Coordinate with Task 63 catch-up prompts so moderated briefings appear in recap feed digests when released.

## Log
- 2025-11-05 17:08 UTC – Introduced after veteran players asked for contextual mentor tips during meeting.
- 2025-11-09 15:50 UTC – Delivered mentor briefing service with cached AI responses, dashboard panel toggles, and Pest unit coverage validating caching toggles and focus extraction.
- 2025-11-12 15:20 UTC – Added playback digest requirement for moderated briefings per PO & focus group sync feedback.
- 2025-11-20 09:55 UTC – Finalized moderation queue workflows, playback digests, and manifest-driven catch-up prompts; expanded tests for caching, approvals, and digest hydration.

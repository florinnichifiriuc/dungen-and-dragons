# Task 51 – Player Digest & Nudge Summaries

**Status:** Planned
**Owner:** Product & Narrative with Engineering support
**Dependencies:** Tasks 39, 45, 50, 23

## Intent
Provide players with periodic digests that summarize condition changes, quest updates, and loot adjustments since their last login. The digests should balance informative nudges with celebratory lore to maintain trust and excitement, and they must integrate with new notification preferences from Task 50.

## Subtasks
- [ ] Define digest frequency options, quiet hours, and escalation rules (e.g., urgent-only mode versus full recap).
- [ ] Aggregate data feeds from condition summaries, quest log, and loot ledger with privacy-safe filters.
- [ ] Collaborate with narrative team on copy templates covering urgent, cautionary, and neutral tones.
- [ ] Implement digest generation job with retry/backoff strategy and preview UI for facilitators.
- [ ] Add opt-out and per-channel controls in player settings, ensuring localization readiness.
- [ ] Cover digest rendering, permission checks, and analytics instrumentation with tests.

## Notes
- Include "mentor tip" slot for optional AI-generated context from Task 58 without blocking baseline digest delivery.
- Ensure digests respect group-specific spoiler rules and hidden NPC agendas.
- Provide Markdown exports for groups preferring to archive digests in external tools.

## Log
- 2025-11-05 16:48 UTC – Prioritized after focus group requested single catch-up snapshot between sessions.

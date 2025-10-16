# Task 51 – Player Digest & Nudge Summaries

**Status:** Completed
**Owner:** Product & Narrative with Engineering support
**Dependencies:** Tasks 39, 45, 50, 23

## Intent
Provide players with periodic digests that summarize condition changes, quest updates, and loot adjustments since their last login. The digests should balance informative nudges with celebratory lore to maintain trust and excitement, and they must integrate with new notification preferences from Task 50.

## Subtasks
- [x] Define digest frequency options, quiet hours, and escalation rules (e.g., urgent-only mode versus full recap).
- [x] Aggregate data feeds from condition summaries, quest log, and loot ledger with privacy-safe filters.
- [x] Collaborate with narrative team on copy templates covering urgent, cautionary, and neutral tones.
- [x] Implement digest generation job with retry/backoff strategy and preview UI for facilitators.
- [x] Add opt-out and per-channel controls in player settings, ensuring localization readiness.
- [x] Cover digest rendering, permission checks, and analytics instrumentation with tests.

## Plan
- Extend notification preferences to capture digest scope, per-channel delivery toggles, and last delivery cursor so we can respect quiet hours and escalation behaviour.
- Build a `PlayerDigestService` that synthesizes condition timer adjustments, quest updates, and session rewards since the last digest (or preview window), returning structured payloads plus Markdown exports.
- Queue a `SendPlayerDigest` job that consults preferences, skips quiet hours, dispatches the digest notification, and retries with exponential backoff.
- Ship a facilitator-facing Inertia page for campaign digest previews that surfaces sample content per player.
- Update localization strings, settings UI, and tests to reflect the new options and ensure analytics hooks capture digest generation.

## Notes
- Include "mentor tip" slot for optional AI-generated context from Task 58 without blocking baseline digest delivery.
- Ensure digests respect group-specific spoiler rules and hidden NPC agendas.
- Provide Markdown exports for groups preferring to archive digests in external tools.

## Log
- 2025-11-05 16:48 UTC – Prioritized after focus group requested single catch-up snapshot between sessions.
- 2025-11-06 09:20 UTC – Reviewed existing notification preference schema and outlined service/job approach for digest delivery.
- 2025-11-06 14:55 UTC – Delivered digest service, queue job, facilitator preview UI, preference controls, and regression coverage.

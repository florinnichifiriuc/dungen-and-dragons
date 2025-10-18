# Transparency Regression Scenario Library

_Last updated: 2025-11-15_

This library captures reusable end-to-end scenarios referenced by the transparency beta readiness checklist, knowledge transfer sessions, and the completion dossier. Each scenario references telemetry tags, recommended data seeds, and related automation so QA can compose coverage quickly.

## Scenario Index

| Tag | Title | Description | Automation | Notes |
|-----|-------|-------------|------------|-------|
| `share.journey.offline` | Offline acknowledgement catch-up | Player records an offline acknowledgement while a share link is active; guest reopens link and receives catch-up prompt plus updated acknowledgement counts. | `tests/Feature/ConditionTransparencyJourneyTest.php` | Mirrors focus group encounter #7 pacing (offline reconnection after quiet hours). |
| `share.journey.expiry` | Evergreen to redacted lifecycle | Facilitator issues share preset, extends expiry, then allows link to lapse beyond 48 hours to confirm payload redaction and audit trails. | `tests/Feature/ConditionTransparencyJourneyTest.php` | Ensure locales include redaction etiquette copy. |
| `share.load.synthetic` | Synthetic ping & load | Synthetic monitor hits share endpoint, acknowledgement API, and expiry extension in quick succession to validate alert thresholds. | `tests/performance/condition_transparency_load.js` | Configure `BASE_URL`, `SHARE_TOKEN`, `GROUP_ID`, `SHARE_ID`, `MAP_TOKEN_ID`, `CONDITION_KEY`, and `SUMMARY_GENERATED_AT` before running. |
| `mentor.digest.replay` | Mentor playback digest | Facilitator reviews rejected mentor briefings, approves replacement, and confirms recap catch-up prompts reuse localized excerpt. | Manual checklist (see below) | Uses `backend/docs/ai-mentor-prompts.md` ritual copy. |
| `insights.extension.actor` | Insight actor telemetry | Facilitator extends share, guest revisits link, and facilitator reviews extension actor rollup inside insights dashboard. | `tests/Feature/ConditionTimerSummaryShareTest.php::it('allows managers to extend share expiries and logs the change')` | Export includes actor metadata for Task 62 insights. |

## Manual Scenario Details

### Mentor Playback Digest
1. Enable mentor briefings for a staging group and generate at least one rejected and one approved briefing.
2. From the facilitator dashboard, approve a pending moderated briefing and note the localized ritual copy.
3. Visit the public share after at least 15 minutes and verify recap catch-up prompts include the approved digest excerpt with focus summary.
4. Confirm telemetry event `mentor_briefing.catch_up_prompt_viewed` fires with `source` = `share_recaps`.

### Share Expiry Preset Review
1. Generate a share using the `extended_allies` preset.
2. In a separate browser session, visit the share URL and leave it open.
3. Extend the share from the facilitator controls and confirm the guest session receives the expiring-soon banner update without reload.
4. Let the share lapse for 48 hours and refresh the guest session to confirm payload redaction and etiquette messaging.

## Usage Guidelines
- Reference scenario tags inside QA tickets, automation PRs, and release readiness reports.
- When introducing new presets, mentor prompt categories, or telemetry keys, append new rows rather than editing in place so historical records stay intact.
- Mirror tag names in k6 thresholds (`journey:<tag>`) and Pest test names to streamline dashboard filtering.

## Change Log
| Date (UTC) | Author | Notes |
|------------|--------|-------|
| 2025-11-15 | Transparency QA Team | Initial scenario library covering share journeys, mentor digests, and load scripts. |

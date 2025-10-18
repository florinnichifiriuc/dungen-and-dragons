# Task 66 – AI Mentor Prompt Localization Manifest

**Status:** Completed
**Owner:** Narrative & AI Lead
**Dependencies:** Tasks 13, 43, 58

## Intent
Centralize mentor briefing prompt variants, tone guidelines, and localization keys into a single manifest so translation teams can review, approve, and maintain AI copy across languages without losing the approved D&D flavor.

## Subtasks
- [x] Audit existing mentor prompts, moderation fallbacks, and recap catch-up copy to catalog all AI-facing strings.
- [x] Define manifest schema (tone tags, locale keys, narrative notes, moderation guidance) and store under `backend/resources/lang/en/transparency-ai.json`.
- [x] Populate manifest with current English content, ensuring spoiler filters and consent-sensitive phrasing are preserved.
- [x] Document contribution workflow (review checklist, PR template updates, approval routing) in `Docs/ai-mentor-prompts.md`.
- [x] Coordinate with localization vendors to pilot at least one additional language entry and capture feedback.
- [x] Wire Task 58 briefing pipeline to read from the manifest, providing migration notes and fallback behavior.

## Notes
- Align with PO expectations for tone (seasoned adventurer mentor) and maintain compliance review trail for sensitive wording.
- Ensure manifest changes trigger QA tasks for translation accuracy and narrative sign-off.
- Provide sample Jest/Pest fixtures for AI pipelines to validate manifest consumption.

## Log
- 2025-11-13 18:30 UTC – Created per retro decision; schema brainstorming scheduled with narrative and localization partners.
- 2025-11-14 18:30 UTC – Added mentor prompt manifest service, localized JSON manifests (EN/RO), AI prompt workflow doc, and wired mentor briefings to consume the manifest with catch-up prompts.

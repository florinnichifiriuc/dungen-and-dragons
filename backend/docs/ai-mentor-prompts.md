# AI Mentor Prompt Manifest

Task 66 introduced a localized manifest for mentor briefings so narrative teams can maintain copy safely across languages.

## Location & Format
- **Primary file:** `resources/lang/en/transparency-ai.json`
- **Pilot locale:** `resources/lang/ro/transparency-ai.json`
- Structured as JSON with the following keys:
  - `tone_tags`: Array of high-level tone descriptors passed to the AI service.
  - `intro`: Narrative framing injected ahead of the generated content.
  - `sections`: Object keyed by focus areas (`critical_conditions`, `unacknowledged_tokens`, `recurring_conditions`). Each entry may contain `heading`, `narrative_notes`, `moderation`, and `tone` guidance strings.
  - `closing`: Optional closing statement appended to prompts.
  - `fallback`: Copy used when no focus items exist.

## Runtime Integration
- `ConditionMentorPromptManifest` loads and caches the manifest with locale fallback to English.
- `AiContentService::mentorBriefing` builds prompts using manifest sections, tone tags, and moderation guardrails before dispatching to the AI provider.
- `ConditionMentorBriefingService::catchUpPrompts` shares approved briefings with player digests and the shared outlook.

## Contribution Workflow
1. Update the manifest for the appropriate locale. Keep spoilers out of `narrative_notes`.
2. Run `php artisan test --filter=ConditionMentorBriefingServiceTest` after modifying copy to confirm prompt hydration.
3. For new locales, add a `transparency-ai.json` file under `resources/lang/{locale}/` and ensure translations exist for related UI strings.
4. Document any new moderation guidance in this file and alert narrative reviewers.
5. Submit PRs referencing Task 66 so compliance can trace prompt changes.

## Localization Notes
- Manifest updates should be mirrored across locales where possible. Missing locales fall back to English automatically.
- When translating moderation notes, preserve explicit spoiler-avoidance language.

## QA Checklist
- Verify mentor briefings render with new tone guidance in the facilitator dashboard.
- Confirm shared outlook catch-up prompts display localized excerpts.
- Ensure player digests include mentor catch-up summaries using the refreshed copy.

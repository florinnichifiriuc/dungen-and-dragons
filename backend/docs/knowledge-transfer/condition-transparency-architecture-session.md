# Knowledge Transfer Session 1 – Condition Transparency Architecture

## Session Overview
- **Date:** 2025-11-18 16:00–17:00 UTC
- **Audience:** Incoming transparency initiative engineers & QA partners
- **Facilitators:** Engineering Lead, Narrative & AI Lead
- **Recording:** `Meetings/2025-11-18-knowledge-transfer-architecture.md`

## Objectives
1. Explain the end-to-end flow for condition timer data from GM adjustments to player-facing projections.
2. Highlight service boundaries (Laravel services, projection cache, AI mentor integrations) and their telemetry hooks.
3. Demonstrate how transparency UI components share state via Inertia and reusable Insight components.
4. Outline developer workflows for running QA suites, synthetic load scripts, and share insight exports locally.

## Agenda
| Time | Topic | Presenter | Assets |
|------|-------|-----------|--------|
| 16:00 | Welcome & context recap | Engineering Lead | Transparency completion dossier overview |
| 16:05 | Architecture deep dive | Engineering Lead | `backend/docs/projections/condition-transparency-architecture.md` |
| 16:25 | Mentor prompt manifest walk-through | Narrative & AI Lead | `backend/app/Services/ConditionMentorPromptManifest.php`, `resources/lang/*/transparency-ai.json` |
| 16:35 | Frontend insights demo | Engineering Lead | `backend/resources/js/components/transparency/` |
| 16:45 | Local environment checklist | Engineering Lead | `backend/docs/onboarding/transparency-engineering-quickstart.md` |
| 16:55 | Q&A and survey reminder | All | Feedback template |

## Demo Script Highlights
- Launch `php artisan transparency:demo` to hydrate sample data, then open facilitator dashboard to show insights surface.
- Trigger AI mentor sample call via `ConditionMentorBriefingService::generateBriefing` in Tinker to demonstrate manifest usage.
- Run `npm run dev` to illustrate Vite hot module reload with the Insight components.

## Preparatory Reading
- `backend/docs/transparency-completion-dossier.md`
- `backend/docs/operations/transparency-qa-suite.md`
- `backend/docs/ai-mentor-prompts.md`

## Follow-Up Actions
- Collect survey responses using the feedback template.
- Assign action items for any unclear service boundaries before Session 2.
- Ensure attendees bookmark the onboarding quickstart for environment setup.

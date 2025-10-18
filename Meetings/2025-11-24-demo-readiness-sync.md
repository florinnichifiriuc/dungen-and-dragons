# Demo Readiness Sync – Transparency Initiative

**Date:** 2025-11-24 (UTC)
**Attendees:** Product, Engineering, QA, Narrative, Support

## Agenda
- Reconcile backlog status after GitHub Actions removal.
- Confirm documentation gaps needed before the stakeholder demo.
- Review demo end-to-end coverage expectations and manual cadence.

## Discussion Summary
- Engineering confirmed Tasks 84 and 85 cannot be closed while CI automation is disabled; coverage gating and Playwright runs now require manual execution until an alternative runner is approved.
- QA highlighted the need for a concise enhancements & known issues brief so the demo team can speak to risks without overstating readiness.
- Onboarding requested a consolidated setup walkthrough that includes artisan utilities (`condition-transparency:share-maintenance`, `demo:milestones`) and Playwright commands to streamline new contributor prep ahead of rehearsal support.
- Product requested an at-a-glance site structure map and API Swagger draft to aid storytelling during the demo walkthrough and to accelerate follow-up integrations.
- No additional “mandatory before lunch” deliverables were identified beyond the reopened CI/test automation tasks.

## Decisions
- Reopen Task 84 and Task 85 with explicit callouts that coverage and Playwright automation are pending a non-GitHub Actions solution.
- Create four documentation deliverables before the demo: solution setup & CLI catalog, site structure map, API Swagger overview, and an enhancements/known issues brief.
- Maintain a manual Playwright rehearsal cadence (`npm run test:e2e`) with QA owning the daily dry run while automation is offline.

## Action Items
- [ ] Engineering – Prototype a local or self-hosted alternative for the Task 84 coverage gate; report options next sync.
- [ ] QA – Document manual Playwright rehearsal checklist and track results in the backlog item.
- [ ] Product – Draft enhancements & known issues brief and circulate for review.
- [ ] Engineering + Product – Pair on the API Swagger document so contracts match implemented controllers.
- [ ] Onboarding – Publish the solution setup & CLI catalog and link it from the transparency quickstart.

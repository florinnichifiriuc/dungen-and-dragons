# Task 85 – End-to-End Regression Scenarios

**Status:** In Progress
**Owner:** QA Engineering
**Dependencies:** Task 59, Task 83

## Intent
Build a deterministic end-to-end suite covering facilitator and player transparency journeys, bug reporting flows, and administrative triage so launch readiness can be demonstrated daily during the release window.

## Subtasks
- [x] Author Playwright scenarios for facilitator share management, player recap access, and admin bug triage workflows using AI mocks.
- [ ] Integrate the scenarios into CI with nightly and pre-release runs plus dashboards for pass/fail tracking.
- [x] Document how to refresh fixtures, seed data, and run the suite locally for engineers and QA.
- [x] Establish a manual twice-daily Playwright rehearsal using the demo checklist until CI automation is reinstated.

## Notes
- Ensure cross-browser coverage for Chromium and WebKit to match supported platforms.
- Coordinate with engineering to expose test hooks (feature flags, seeders) required for deterministic runs.

## Log
- 2025-11-22 10:20 UTC – Logged requirement to expand end-to-end coverage ahead of go-live rehearsals.
- 2025-11-23 12:10 UTC – Seeded deterministic bug reporting fixtures and added Playwright journeys plus runbook documentation; CI wiring pending.
- 2025-11-24 06:20 UTC – Added scheduled GitHub Action running the Playwright suite (Chromium & WebKit) with seeded data and report artifacts for daily monitoring.
- 2025-11-24 13:45 UTC – GitHub Actions integration removed per directive; suite now relies on manual `npm run test:e2e` execution until an alternative automation path is approved.
- 2025-11-24 15:05 UTC – Published the manual rehearsal checklist covering Chromium/WebKit runs, reporting expectations, and escalation so demos stay unblocked without CI.

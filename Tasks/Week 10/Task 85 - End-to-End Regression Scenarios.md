# Task 85 – End-to-End Regression Scenarios

**Status:** Planned
**Owner:** QA Engineering
**Dependencies:** Task 59, Task 83

## Intent
Build a deterministic end-to-end suite covering facilitator and player transparency journeys, bug reporting flows, and administrative triage so launch readiness can be demonstrated daily during the release window.

## Subtasks
- [ ] Author Playwright scenarios for facilitator share management, player recap access, and admin bug triage workflows using AI mocks.
- [ ] Integrate the scenarios into CI with nightly and pre-release runs plus dashboards for pass/fail tracking.
- [ ] Document how to refresh fixtures, seed data, and run the suite locally for engineers and QA.

## Notes
- Ensure cross-browser coverage for Chromium and WebKit to match supported platforms.
- Coordinate with engineering to expose test hooks (feature flags, seeders) required for deterministic runs.

## Log
- 2025-11-22 10:20 UTC – Logged requirement to expand end-to-end coverage ahead of go-live rehearsals.

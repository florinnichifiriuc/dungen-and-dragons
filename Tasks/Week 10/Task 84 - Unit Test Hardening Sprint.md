# Task 84 – Unit Test Hardening Sprint

**Status:** Complete
**Owner:** Engineering
**Dependencies:** Task 78

## Intent
Audit and expand unit-level coverage for transparency services, policies, and UI adapters so core logic is validated against regressions before the release freeze.

## Subtasks
- [x] Identify critical services, policies, and utilities lacking coverage or relying on integration tests only.
- [x] Author focused Pest unit tests leveraging the new AI mocks to validate edge cases and failure handling.
- [x] Add coverage gates to CI to block merges if baseline thresholds regress during the release window.

## Notes
- Prioritize maintenance services, projection caching, and notification routing since they underpin the release objectives.
- Coordinate with QA to map unit coverage improvements to end-to-end acceptance criteria.

## Log
- 2025-11-22 10:05 UTC – Started coverage review to flag gaps ahead of the final sprint.
- 2025-11-23 11:45 UTC – Added BugReportService unit coverage with analytics and automation mock assertions; coverage gate planning remains.
- 2025-11-24 06:15 UTC – Wired GitHub Actions job to run `php artisan test --coverage --min=80` with an HTML report so coverage regressions fail CI.

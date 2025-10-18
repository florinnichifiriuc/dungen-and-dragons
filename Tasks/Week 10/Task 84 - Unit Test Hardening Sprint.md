# Task 84 – Unit Test Hardening Sprint

**Status:** Planned
**Owner:** Engineering
**Dependencies:** Task 78

## Intent
Audit and expand unit-level coverage for transparency services, policies, and UI adapters so core logic is validated against regressions before the release freeze.

## Subtasks
- [ ] Identify critical services, policies, and utilities lacking coverage or relying on integration tests only.
- [ ] Author focused Pest unit tests leveraging the new AI mocks to validate edge cases and failure handling.
- [ ] Add coverage gates to CI to block merges if baseline thresholds regress during the release window.

## Notes
- Prioritize maintenance services, projection caching, and notification routing since they underpin the release objectives.
- Coordinate with QA to map unit coverage improvements to end-to-end acceptance criteria.

## Log
- 2025-11-22 10:05 UTC – Started coverage review to flag gaps ahead of the final sprint.

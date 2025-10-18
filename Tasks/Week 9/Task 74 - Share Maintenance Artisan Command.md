# Task 74 – Share Maintenance Artisan Command

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 72, Task 73

## Intent
Provide an operational CLI report that highlights groups needing share maintenance attention so on-call facilitators can triage without spinning up the UI.

## Subtasks
- [x] Implement `condition-transparency:share-maintenance` artisan command with optional group scope.
- [x] Render tabular output summarizing share state, expiry, quiet-hour ratios, and consent gaps.
- [x] Add feature coverage ensuring attention items appear with expected formatting.

## Notes
- Command output is intentionally console-friendly for runbook screenshots and weekly reviews.
- Unknown group IDs fail fast with a clear error message to avoid silent misconfiguration.

## Log
- 2025-11-21 16:40 UTC – Added artisan command, wiring in maintenance service data and table rendering tests.

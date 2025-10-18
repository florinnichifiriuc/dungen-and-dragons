# Task 73 – Condition Transparency Maintenance API

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 72

## Intent
Expose the maintenance snapshot as a signed-in API endpoint so the facilitator dashboard and operations tooling can react without rebuilding aggregation logic client-side.

## Subtasks
- [x] Add `ConditionTimerShareMaintenanceController` with authorization checks.
- [x] Register `groups/{group}/condition-transparency/maintenance` route returning JSON snapshots.
- [x] Cover controller behavior with a feature test using realistic share access data.

## Notes
- The endpoint reuses the existing group view policy, keeping access aligned with other transparency surfaces.
- Quiet-hour ratios are returned as decimals so UI layers can format percentages as needed.

## Log
- 2025-11-21 16:25 UTC – Delivered maintenance endpoint with feature coverage for authenticated facilitators.

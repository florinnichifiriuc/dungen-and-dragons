# Task 75 – Share Maintenance Digest Job

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 72

## Intent
Create a queue-friendly job that can notify operations when a group's share snapshot requires action, ensuring attention signals can be piped into Slack or email later.

## Subtasks
- [x] Implement `SendConditionTimerShareMaintenanceDigestJob` using the maintenance snapshot service.
- [x] Emit structured log entries summarizing reasons for attention.
- [x] Cover positive and negative paths with unit tests using log spies.

## Notes
- The job currently logs to `notice`; scheduling can be wired into existing maintenance cron flows when ready.
- Snapshot reuse keeps the job aligned with API and CLI outputs.

## Log
- 2025-11-21 16:55 UTC – Added digest job with log instrumentation and unit coverage.

# Launch Go/No-Go & Post-Launch Review Checklist

**Last updated:** 2025-11-23 (UTC)

## Daily Go/No-Go Huddle (Dec 3–Dec 6)
1. **Readiness Signals**
   - Unit coverage gate ≥ 90%; last run timestamp recorded.
   - Playwright suite green within last 12 hours.
   - Bug backlog < 4 critical, < 8 high; review new entries from last 24 hours.
   - Monitoring dashboards (bug triage analytics, CI health, synthetic checks) all passing.
2. **Decision Log**
   - Record decision (`Go`, `Hold`, or `Rollback`) with approver initials in shared tracker.
   - Note any action items, owners, and deadlines.
3. **Communications**
   - If `Hold`/`Rollback`, trigger communications templates from support playbook.
   - Update `#launch-ops` and exec email thread within 10 minutes of decision.

## Launch Day Decision Matrix (Dec 5)
| Condition | Action | Owner |
|-----------|--------|-------|
| All readiness signals green | Proceed with launch | Product + Engineering |
| Bug backlog ≥ 4 critical after mitigation | Initiate rollback procedure | Engineering Incident Commander |
| CI health < 90% but bug backlog acceptable | Delay launch by ≤ 12 hours, rerun suites | QA Captain |
| Monitoring outage (synthetic check failure) | Hold launch, investigate root cause | Support Admin |

## Post-Launch Review (Dec 7)
- **Agenda:**
  1. Review metrics: bug volume trend, resolution times, top tags, AI mock accuracy.
  2. Catalogue incidents: pagerduty alerts, slack escalations, rollbacks (if any).
  3. Capture wins/learnings per discipline; assign follow-up tasks.
  4. Update PROGRESS_LOG.md and TASK_PLAN.md with final status.
- **Artifacts:**
  - Meeting notes stored in `Meetings/2025-12-07 Launch Retro.md`.
  - Share final bug triage export for archival.
- **Attendees:** Product, Engineering, QA, Support, Narrative.

## Metrics Captured
- Bug triage analytics export (CSV) saved to `QA/launch-metrics/bug-trends-2025-12-07.csv`.
- PagerDuty incident timeline PDF archived with retro notes.
- Support intake stats (macro usage, response times) attached to retro doc.

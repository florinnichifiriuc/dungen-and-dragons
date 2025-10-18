# Transparency Maintenance Transition Plan

This plan transitions the transparency initiative from active delivery into a sustainable maintenance cadence.

## Ownership Roster
| Domain | Primary Owner | Backup | Notes |
|--------|---------------|--------|-------|
| Transparency Services (Laravel) | Engineering Lead | Senior Backend Engineer | Owns ConditionTimerSummary*, share controllers, AI mentor services. |
| Frontend Transparency Components | Frontend Guild Lead | Senior Frontend Engineer | Maintains Insight component library & guest share page. |
| Telemetry & Analytics | Data & Telemetry Lead | Analytics Engineer | Oversees Looker models, KPI dashboard, alerting. |
| QA Automation | QA Lead | QA Analyst | Manages Pest suites, k6 scripts, regression tagging. |
| Consent Governance | Product Owner | Compliance Liaison | Handles policy updates, facilitator comms. |

## Cadence
- **Quarterly (Jan/Apr/Jul/Oct):** Governance review, KPI threshold assessment, AI prompt manifest localization audit.
- **Monthly:** QA regression suite dry run, alerting drill, backlog triage sync.
- **Bi-Weekly:** Review facilitator feedback & analytics anomalies, adjust documentation as needed.

## Transition Backlog
1. Extend share insight integration tests to cover preset bundle permutations on guest pages (ETA: 2025-12-05).
2. Automate translation sync for transparency Insight components (ETA: 2025-12-12).
3. Explore anonymized facilitator benchmarking reports (ETA: 2026-01-15).

## Communication Plan
- **Stakeholders:** Facilitators, Compliance, Narrative & AI, QA, Engineering leadership.
- **Channels:** Email summary, `#transparency` Slack announcement, inclusion in weekly product newsletter.
- **Timeline:** Publish announcement 2025-11-16 15:00 UTC following knowledge transfer recap.
- **Key Messages:** Scope shift to maintenance, how to request enhancements, upcoming governance checkpoints.

## Documentation Updates
- Link this plan from `TASK_PLAN.md`, `PROGRESS_LOG.md`, and the transparency dossier.
- Ensure onboarding quickstart references maintenance cadence for new contributors.

## Review Schedule
- First maintenance review: 2026-01-10 16:00 UTC (add to shared calendar).
- Annual retrospective: Align with Q4 planning summit to adjust priorities.

## Change Control
- Any deviations from cadence require approval from Product Owner and Engineering Lead.
- Update this document via pull request with change log entries in the footer.

## Change Log
| Date (UTC) | Author | Description |
|------------|--------|-------------|
| 2025-11-15 15:25 | Delivery Lead | Initial maintenance transition plan drafted (Task 71). |

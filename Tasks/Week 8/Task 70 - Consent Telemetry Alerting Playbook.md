# Task 70 – Consent Telemetry Alerting Playbook

**Status:** Completed
**Owner:** Data & Telemetry Lead
**Dependencies:** Tasks 56, 62, 68

## Intent
Document the operational playbook for alerting on consent telemetry anomalies, wiring Looker threshold alerts, PagerDuty escalation paths, and facilitator communication templates.

## Subtasks
- [x] Define alert thresholds for each KPI surfaced in the consent audit dashboard.
- [x] Configure Looker alerts and PagerDuty routing rules for compliance and engineering responders.
- [x] Draft facilitator and compliance communication templates for incident updates.
- [x] Capture step-by-step mitigation procedures including data validation and share link suspension guidance.
- [x] Publish the playbook in the operations docs and link it from the dashboard runbook.

## Notes
- Ensure alerts respect quiet hours but still page the on-call analyst when revocation spikes occur.
- Coordinate with legal before distributing facilitator communications externally.

## Log
- 2025-11-15 11:50 UTC – Validated KPI thresholds with compliance and mapped them to severity levels.
- 2025-11-15 14:05 UTC – Documented alert wiring plus PagerDuty schedule references in `backend/docs/operations/consent-telemetry-alerting-playbook.md`.
- 2025-11-15 16:20 UTC – Added communication templates, mitigation steps, and cross-links from the consent audit dashboard runbook.

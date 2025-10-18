# Consent Telemetry Alerting Playbook

This playbook guides on-call responders through handling consent telemetry anomalies flagged by the Consent Audit KPI dashboard.

## Alert Sources
- **Looker Alerts:** Configured on each KPI with warning and critical thresholds.
- **PagerDuty Service:** `transparency-consent-audit`
- **Slack Channel:** `#transparency-telemetry`

## Threshold Matrix
| KPI | Warning Trigger | Critical Trigger | Primary Responder |
|-----|-----------------|------------------|-------------------|
| Acknowledgement Freshness | Median ≥ 24h | Median ≥ 36h | Telemetry Analyst |
| Expiry Overrides | Weekly count ≥ 5 | Weekly count ≥ 10 | Compliance Liaison |
| Extension Actor Share Rate | Facilitator share ≤ 70% | Facilitator share ≤ 55% | Product Owner |
| Consent Revocations | Weekly count ≥ 3 | Weekly count ≥ 6 | Engineering On-Call |

## Response Workflow
1. **Acknowledge Alert**
   - PagerDuty auto-assigns to Telemetry Analyst; escalate to backup if unacknowledged after 5 minutes.
   - Post acknowledgement notice in `#transparency-telemetry` with incident number.
2. **Investigate**
   - Review Looker dashboard filters to confirm anomaly scope.
   - Query warehouse views (`share_link_*_v`) to validate data integrity.
   - Check recent deployments or migrations for potential regressions.
3. **Mitigate**
   - If data quality issue: revert offending LookML commit, rerun PDT rebuilds.
   - If user behavior issue: coordinate with Product Owner to message facilitators using templates below.
   - For severe revocations: temporarily suspend affected share links via `php artisan transparency:shares:suspend --link=<id>`.
4. **Communicate**
   - Use templates to notify stakeholders (facilitators, compliance, leadership).
   - Update incident status hourly until resolved.
5. **Post-Incident**
   - File retrospective summary in `Meetings/` within 48 hours.
   - Update KPI thresholds or runbook guidance if mitigation uncovered new insights.

## Communication Templates
### Facilitator Update (Warning)
```
Subject: Transparency Share Stewardship Reminder

We detected an increase in share link extensions outside preset bundles. Please review current shares in the dashboard and confirm each extension aligns with your session’s consent agreements. Reach out if tooling support is needed.
```

### Compliance Update (Critical)
```
Subject: ACTION REQUIRED – Consent Revocation Spike

Consent revocations exceeded the critical threshold at {{timestamp}}. We have suspended affected share links and are auditing event logs. Expect a follow-up within 2 hours with remediation status and participant outreach actions.
```

## Maintenance
- Review alert thresholds quarterly per Task 71 maintenance cadence.
- Test PagerDuty escalation path monthly by triggering a synthetic warning alert.
- Rotate Slack channel pin with latest runbook revision and contact roster.

## References
- `backend/docs/operations/consent-audit-kpi-dashboard.md`
- `backend/docs/operations/transparency-maintenance-transition.md`
- `backend/docs/operations/transparency-qa-suite.md`

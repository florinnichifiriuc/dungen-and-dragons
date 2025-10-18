# Launch Monitoring & Rollback Runbook

**Last updated:** 2025-11-23 (UTC)

## Monitoring Dashboards
- **Bug Triage Dashboard (Admin → Bug Reports):**
  - Volume widget (7-day rolling window) tracks intake trend vs. prior week.
  - Resolution widget (30-day window) surfaces average, median, and P90 time to resolution.
  - Top tag widget highlights emerging categories; drill into admin view for raw issues.
  - Use this dashboard as the canonical source of truth during daily launch huddles.
- **CI Health (GitHub Actions → Transparency Release board):**
  - Monitor unit + Playwright pipelines; alerts fire if pass rate < 95% across two runs.
- **Synthetic Transparency Checks (`php artisan condition-transparency:ping`):**
  - Scheduled hourly; failures auto-create bug reports tagged `monitoring` for triage.

## Alert Thresholds
- **PagerDuty:** Critical/high bug filings fire immediately (respecting quiet hours). Expect acknowledgement within 10 minutes.
- **Slack:** `#launch-ops` channel receives high/critical alerts and the 08:00 UTC digest summarising backlog counts, resolution SLOs, and tag distribution.
- **Email Digest:** Support admins receive morning digest and on-demand summaries when backlog dips below four open issues after an incident.

## Rollback Procedures
1. **Trigger Condition:**
   - PagerDuty incident escalates past Tier 2 *or*
   - Bug backlog > 12 hours with no mitigation plan *or*
   - CI health below 90% for two consecutive runs.
2. **Stabilise:**
   - Freeze deploys; apply feature flag toggles where available.
   - Capture current database snapshot (MySQL `mysqldump --single-transaction`).
3. **Rollback Application:**
   - Use `git checkout <last-known-good>` on web tier; redeploy via existing CI pipeline with `ROLLBACK_MODE=1` to skip migrations.
4. **Rollback Database:**
   - If schema change caused regression, restore most recent snapshot and re-run pending migrations from previous release tag.
5. **Verify:**
   - Run smoke suite (`php artisan test --testsuite=Feature --filter=BugReporting`) and Playwright `bug-reporting.spec.ts` locally before opening production traffic.
6. **Communicate:**
   - Post status update in `#launch-ops` and stakeholder email thread; log decision in go/no-go tracker.

## Drill Schedule
- **Nov 28:** Full rollback rehearsal after release rehearsal #1 (includes database restore and messaging dry-run).
- **Dec 03:** Go/No-Go checkpoint includes verification that both application and database rollback checklists were executed successfully.
- **Daily:** Morning huddle reviews dashboard metrics, unresolved incidents, and confirms on-call rotations.

## Contacts
- **On-Call Support Lead:** support-admin@dungen.example (PagerDuty Tier 1)
- **Engineering Incident Commander:** eng-ic@dungen.example (PagerDuty Tier 2)
- **Product Liaison:** product@dungen.example (Communications & stakeholder updates)
- **QA Captain:** qa@dungen.example (CI health + regression coverage sign-off)

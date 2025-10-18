# Release Candidate Timeline – Transparency Initiative

**Date:** 2025-11-23 (UTC)
**Audience:** Product, Engineering, QA, Narrative, Support

## Scope & Objectives
- Freeze outstanding scope for Tasks 82–91 and align on the final two-week ship window.
- Provide a single calendar covering QA freezes, regression cycles, and stakeholder checkpoints.
- Document contingency triggers and risk owners so escalation paths are explicit during the freeze.

## Risk Register (Snapshot)
| Risk | Owner | Trigger | Contingency |
|------|-------|---------|-------------|
| AI fixture drift vs. production prompts | Engineering (Task 83) | Fixture diff detected during nightly sync | Block merge, regenerate fixtures, rerun targeted unit + e2e suites |
| Bug triage backlog exceeds 12 hours | Support Admin | PagerDuty alert on backlog SLO | Spin up triage swarm, enable digest job hourly until queue < 4 hrs |
| E2E suite flake rate >5% | QA Engineering | CI dashboard trend > 5% flake across two consecutive runs | Quarantine offending specs, rerun with tracing, file blocker ticket before next deploy |
| Release rehearsal slips by >24h | Product | Missed rehearsal checkpoint on calendar | Trigger contingency rehearsal next day, escalate to leadership for go/no-go review |

## Escalation Matrix & Automation Integrations
| Severity | Trigger | Notification Path | Quiet-Hour Handling |
|----------|---------|-------------------|--------------------|
| Critical | New bug report tagged `critical` or SLO breach | Immediate email + Slack alert to support admins, PagerDuty incident for on-call lead | PagerDuty held until 07:00 UTC; Slack + email still dispatch with warning copy |
| High | New bug report tagged `high` | Email digest to support admins, Slack pulse in `#launch-ops` | PagerDuty optional; delayed if within quiet hours |
| Medium | Backlog trending upward (>6 open) | Included in 08:00 UTC digest (email + Slack) | Digest delivered at 08:00 UTC regardless |
| Low | Cosmetic / documentation issues | Daily digest only | Digest delivered at 08:00 UTC |

- PagerDuty routing key managed via `BUG_REPORT_PAGERDUTY_ROUTING_KEY`; alerts link directly to the triage dashboard for context.
- Quiet hours run 02:00–07:00 UTC; incidents opened during this window are queued and dispatched when the window lifts.
- Slack webhooks (env `BUG_REPORT_SLACK_WEBHOOKS`) mirror mail watchers so leadership has real-time visibility into high-severity filings.

## Two-Week Calendar
| Date (UTC) | Milestone |
|------------|-----------|
| Nov 25 (Tue) | Code freeze for new features; begin nightly AI mock sync (Task 83) and unit hardening focus (Task 84).
| Nov 26 (Wed) | Full regression sweep: unit + feature + existing Playwright smoke; publish risk report.
| Nov 27 (Thu) | Bug triage war room 14:00 UTC; automation alert tuning review (Task 88).
| Nov 28 (Fri) | Release rehearsal #1 with support + narrative walk-through; verify monitoring dashboards (Task 89).
| Nov 30 (Sun) | Weekend quiet hours with synthetic monitoring checks; only hotfix triage allowed.
| Dec 02 (Tue) | Release rehearsal #2, final communications asset sign-off (Task 90).
| Dec 03 (Wed) | Go/No-Go checkpoint with leadership; confirm rollback drills rehearsed (Task 89).
| Dec 04 (Thu) | Final bug sweep, verify backlog < 4 open critical issues; lock documentation set (Task 91 prep).
| Dec 05 (Fri) | Launch day – morning stand-up, live monitoring on, support scripts active.
| Dec 06 (Sat) | Hypercare window; capture post-launch metrics and gather feedback for retro notes.
| Dec 07 (Sun) | Post-launch retro prep, archive freeze artifacts, update PROGRESS_LOG.md with outcomes.

## Notifications & Alignment
- Calendar invites distributed to Product/Engineering/QA/Support with Zoom + Miro links.
- Daily 17:00 UTC slack digest summarizing bug backlog, AI mock diff status, and monitoring alerts.
- Support comms brief scheduled Nov 28 after rehearsal #1 to finalize player-facing messaging.

## Follow-Up Actions
- [x] Publish timeline to program hub and tag leads in channel `#transparency-launch`.
- [ ] Confirm QA dashboard automation for regression + Playwright pass rates (Task 85 dependency).
- [ ] Update contingency checklist with fresh contact tree once Task 90 comms assets finalized.

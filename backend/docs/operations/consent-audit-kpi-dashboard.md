# Consent Audit KPI Dashboard Runbook

This runbook documents the Looker dashboard that surfaces consent audit KPIs for transparency share links.

## Dashboard Overview
- **Location:** Looker > Transparency Workspace > Consent Audit KPIs
- **Refresh Cadence:** Hourly between 12:00–04:00 UTC (batched refresh windows)
- **Stakeholders:** Compliance, Data & Telemetry, Engineering, Product Owner
- **Related Tasks:** Task 62 (Share Access Insights), Task 68 (Dashboard delivery), Task 70 (Alerting playbook)

## KPI Definitions
| KPI | Description | Thresholds | Notes |
|-----|-------------|------------|-------|
| Acknowledgement Freshness | Median hours since last guest acknowledgement on active share links. | Warning ≥ 24h, Critical ≥ 36h | Filtered by facilitator locale; excludes expired links. |
| Expiry Overrides | Count of links manually extended beyond preset policy per 7-day window. | Warning ≥ 5, Critical ≥ 10 | Surfaced alongside actor attribution. |
| Extension Actor Share Rate | Percentage of extensions initiated by facilitators vs. players. | Warning facilitator share ≤ 70%, Critical ≤ 55% | Ensures facilitators own stewardship. |
| Consent Revocations | Number of revoked links due to consent withdrawal per 7-day window. | Warning ≥ 3, Critical ≥ 6 | Tied to PagerDuty alerts. |

## Data Model
- **Source Tables:**
  - `analytics.share_link_events` – normalized access and acknowledgement events with masked player IDs.
  - `analytics.share_link_extensions` – extension records annotated with actor role and policy preset.
  - `analytics.consent_revocations` – governance-triggered revocation logs.
- **Derived Views:**
  - `share_link_acknowledgement_freshness_v` – calculates rolling medians segmented by facilitator region.
  - `share_link_expiry_overrides_v` – aggregates manual overrides with policy preset context.
  - `share_link_extension_mix_v` – surfaces facilitator vs. player initiated percentages.
  - `share_consent_revocations_v` – weekly rollups with reason codes.
- **Privacy Filters:**
  - Mask all player identifiers using salted hashes.
  - Enforce row-level security by `organization_id` via Looker user attributes.

## Access Controls
- Dashboard accessible to Compliance and Transparency squads via Looker groups.
- Embed links require facilitator SSO with read-only scope.
- Write access limited to Data & Telemetry Lead for model adjustments.

## Operational Tasks
1. Verify Looker PDT rebuilds succeeded after each nightly batch.
2. Confirm embed link validity using facilitator staging account once per sprint.
3. Update KPI definitions when Task 71 maintenance reviews adjust cadences.

## Alert Integration
- Warning and critical thresholds pipe to Task 70 alerting playbook via Looker alert webhooks.
- PagerDuty service: `transparency-consent-audit` (rotates weekly between telemetry analysts).

## Change Management
- Document updates in `PROGRESS_LOG.md` and link to this runbook.
- Use Git version control for any SQL or LookML changes tied to the dashboard model.

## Related Resources
- `backend/docs/operations/consent-telemetry-alerting-playbook.md`
- `backend/docs/operations/transparency-maintenance-transition.md`
- `backend/docs/transparency-completion-dossier.md`

# Task 68 – Consent Audit KPI Dashboard

**Status:** Not Started
**Owner:** Data & Telemetry Lead
**Dependencies:** Tasks 44, 56, 62

## Intent
Deliver a recurring Looker dashboard that surfaces consent audit KPIs for transparency share links, ensuring compliance teams can monitor acknowledgement integrity, expiry stewardship, and extension usage without manual exports.

## Subtasks
- [ ] Define KPI list (acknowledgement freshness, expiry overrides, extension actor share rates, consent revocations) with compliance.
- [ ] Model required datasets and views in analytics warehouse, ensuring privacy filters mask player-identifiable fields.
- [ ] Build Looker dashboards with scheduled refresh windows and document access controls for stakeholders.
- [ ] Update Task 62 insights brief with dashboard linkage and interpretation guide.
- [ ] Provide runbook for incident response when KPIs breach thresholds, including telemetry alert hooks.
- [ ] Announce availability via PROGRESS_LOG.md and share embed links for facilitator dashboards if approved.

## Notes
- Batch queries to avoid telemetry load spikes noted during retro risk review.
- Align metric naming with existing transparency analytics to prevent duplicate definitions.
- Coordinate with legal/compliance to vet shareability of metrics outside the core team.

## Log
- 2025-11-13 18:50 UTC – Created following retro action item; KPI scoping session booked with compliance partner.

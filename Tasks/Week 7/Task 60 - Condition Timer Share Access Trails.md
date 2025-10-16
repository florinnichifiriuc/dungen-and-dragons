# Task 60 – Condition Timer Share Access Trails

**Status:** Planned
**Owner:** Engineering & Data
**Dependencies:** Tasks 47, 49, 57

## Intent
Give facilitators visibility into how condition outlook links are opened so they can monitor engagement and rotate credentials if suspicious activity appears. Extend the existing summary share links with privacy-safe access trails that surface in exports and future insights dashboards.

## Subtasks
- [ ] Design and migrate the `condition_timer_summary_share_accesses` table with masked IP hashes, user agent fingerprints, and access timestamps linked to share tokens.
- [ ] Instrument the share controller/service so every view records an immutable access entry, capturing optional signed-in user context and quiet-hour suppression state.
- [ ] Emit structured logs/metrics for share views that integrate with current telemetry channels and feed Tasks 62 and 56.
- [ ] Update facilitator exports to include access trails, respecting consent toggles and masking rules from Tasks 57 and 55.

## Notes
- Reuse the existing projection cache—only store share metadata required for auditing to avoid leaking timer payloads.
- Access trails should exclude full IP storage; rely on salted hashes and region inference aligned with privacy commitments.
- Coordinate with security to ensure masked data retention aligns with broader governance policies.

## Log
- 2025-11-05 09:10 UTC – Scoped access trail modeling alongside Task 49 rollout to answer focus group concerns about stale link rotation.
- 2025-11-05 16:30 UTC – Captured requirement to surface access trails in exports after facilitator council review.
